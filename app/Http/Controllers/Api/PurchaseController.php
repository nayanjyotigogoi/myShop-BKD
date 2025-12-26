<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // ✅ ADDED

class PurchaseController extends Controller
{
    /**
     * List purchases with summary.
     */
 public function index(Request $request)
    {
        Log::debug('Purchase index called', [
            'query' => $request->all(),
        ]);

        $query = Purchase::query();

        if ($from = $request->input('from')) {
            $query->whereDate('purchase_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('purchase_date', '<=', $to);
        }

        $purchases = $query
            ->withCount('items')
            ->orderBy('purchase_date', 'desc')
            ->get();

        $purchases->each(function ($purchase) {
            $purchase->total_items = $purchase->items()->sum('quantity');
        });

        return response()->json($purchases);
    }

    /**
     * Store a new purchase and update stock.
     */
public function store(Request $request)
    {
        Log::debug('Purchase store request received', [
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'supplier'      => 'nullable|string|max:255',
            'items'         => 'required|array|min:1',

            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.sell_price' => 'nullable|numeric|min:0', // ✅ ADDED

            'items.*.product.code'       => 'nullable|string|max:50',
            'items.*.product.name'       => 'nullable|string|max:255',
            'items.*.product.category'   => 'nullable|string|max:50',
            'items.*.product.gender'     => 'nullable|string|in:male,female,boys,girls,unisex',
            'items.*.product.size'       => 'nullable|string|max:50',
            'items.*.product.color'      => 'nullable|string|max:50',
            'items.*.product.sell_price' => 'nullable|numeric|min:0',

            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use (&$purchase, $validated) {

                $purchase = Purchase::create([
                    'purchase_date' => $validated['purchase_date'],
                    'supplier'      => $validated['supplier'] ?? null,
                    'total_amount'  => 0,
                    'user_id'       => auth()->id() ?? 1,
                ]);

                $totalAmount = 0;
                $usedProductIds = [];

                foreach ($validated['items'] as $item) {

                    if (!empty($item['product_id'])) {
                        $product = Product::lockForUpdate()
                            ->findOrFail($item['product_id']);
                    } else {
                        if (empty($item['product'])) {
                            throw new \Exception(
                                'Product details missing. Please select or create a product.'
                            );
                        }

                        $p = $item['product'];

                        if (Product::where('code', $p['code'])->exists()) {
                            throw new \Exception(
                                "Product code '{$p['code']}' already exists."
                            );
                        }

                        $product = Product::create([
                            'code'          => $p['code'],
                            'name'          => $p['name'],
                            'category'      => $p['category'] ?? 'General',
                            'gender'        => $p['gender'] ?? 'unisex',
                            'size'          => $p['size'] ?? null,
                            'color'         => $p['color'] ?? null,
                            'buy_price'     => $item['unit_price'],
                            'sell_price'    => $p['sell_price'] ?? 0,
                            'current_stock' => 0,
                        ]);
                    }

                    if (in_array($product->id, $usedProductIds, true)) {
                        throw new \Exception(
                            'Same product added multiple times in purchase.'
                        );
                    }

                    $usedProductIds[] = $product->id;

                    $quantity  = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $lineTotal = $quantity * $unitPrice;

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id'  => $product->id,
                        'quantity'    => $quantity,
                        'unit_price'  => $unitPrice,
                        'line_total'  => $lineTotal,
                    ]);

                    $product->increment('current_stock', $quantity);

                    // ✅ UPDATED PRICE LOGIC
$product->buy_price = $unitPrice;

$sellPrice =
    $item['sell_price']
    ?? $item['product']['sell_price']
    ?? null;

if ($sellPrice !== null) {
    $product->sell_price = (float) $sellPrice;
}

$product->save();


                    $totalAmount += $lineTotal;
                }

                $purchase->update([
                    'total_amount' => $totalAmount,
                ]);
            });

            $purchase->load(['items.product']);

            return response()->json([
                'message' => 'Purchase saved successfully',
                'data'    => $purchase,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {

            Log::error('Purchase creation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to save purchase',
                'errors'  => [
                    'general' => [$e->getMessage()],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
    /**
     * Show one purchase with items.
     */
    public function show(Purchase $purchase)
    {
        Log::debug('Purchase show called', [
            'purchase_id' => $purchase->id,
        ]);

        $purchase->load(['items.product']);

        return response()->json($purchase);
    }

    /* ================= UPDATE (NEW) ================= */

/**
     * Update purchase.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'supplier'      => 'nullable|string|max:255',
            'items'         => 'required|array|min:1',

            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.sell_price' => 'nullable|numeric|min:0', // ✅ ADDED
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($purchase, $validated) {

                foreach ($purchase->items as $oldItem) {
                    $product = Product::lockForUpdate()
                        ->findOrFail($oldItem->product_id);

                    $product->decrement('current_stock', $oldItem->quantity);
                }

                $purchase->items()->delete();

                $purchase->update([
                    'purchase_date' => $validated['purchase_date'],
                    'supplier'      => $validated['supplier'] ?? null,
                    'total_amount'  => 0,
                ]);

                $totalAmount = 0;

                foreach ($validated['items'] as $item) {

                    $product = Product::lockForUpdate()
                        ->findOrFail($item['product_id']);

                    $quantity  = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $lineTotal = $quantity * $unitPrice;

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id'  => $product->id,
                        'quantity'    => $quantity,
                        'unit_price'  => $unitPrice,
                        'line_total'  => $lineTotal,
                    ]);

                    $product->increment('current_stock', $quantity);

                    // ✅ UPDATED PRICE LOGIC
$product->buy_price = $unitPrice;

$sellPrice =
    $item['sell_price']
    ?? $item['product']['sell_price']
    ?? null;

if ($sellPrice !== null) {
    $product->sell_price = (float) $sellPrice;
}

$product->save();


                    $totalAmount += $lineTotal;
                }

                $purchase->update([
                    'total_amount' => $totalAmount,
                ]);
            });

            $purchase->load(['items.product']);

            return response()->json([
                'message' => 'Purchase updated successfully',
                'data'    => $purchase,
            ]);

        } catch (\Throwable $e) {

            Log::error('Purchase update failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update purchase',
                'errors'  => [
                    'general' => [$e->getMessage()],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

}
