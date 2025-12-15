<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * List purchases with basic summary.
     *
     * Supports optional date range:
     * GET /api/purchases?from=2025-12-01&to=2025-12-04
     */
    public function index(Request $request)
    {
        $query = Purchase::query();

        if ($from = $request->input('from')) {
            $query->whereDate('purchase_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('purchase_date', '<=', $to);
        }

        // Load purchases with items_count
        $purchases = $query
            ->withCount('items') // => items_count
            ->orderBy('purchase_date', 'desc')
            ->get();

        // Manually compute total_items (sum of quantity) for each purchase
        $purchases->each(function ($purchase) {
            $purchase->total_items = $purchase->items()->sum('quantity');
        });

        return response()->json($purchases);
    }


    /**
     * Store a new purchase and update stock.
     *
     * Expected JSON body:
     * {
     *   "purchase_date": "2025-12-04",
     *   "supplier": "Some Supplier",
     *   "items": [
     *     { "product_id": 1, "quantity": 10, "unit_price": 150 },
     *     { "product_id": 2, "quantity": 5,  "unit_price": 200 }
     *   ]
     * }
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'purchase_date'        => 'required|date',
        'supplier'             => 'nullable|string|max:255',
        'items'                => 'required|array|min:1',
        'items.*.product_id'   => 'required|exists:products,id',
        'items.*.quantity'     => 'required|integer|min:1',
        'items.*.unit_price'   => 'required|numeric|min:0',
    ]);

    $purchase = null;

    DB::transaction(function () use (&$purchase, $validated) {
        // 1) Create purchase with temporary total_amount = 0
        $purchase = Purchase::create([
            'purchase_date' => $validated['purchase_date'],
            'supplier'      => $validated['supplier'] ?? null,
            'total_amount'  => 0,
            'user_id'       => auth()->id() ?? 1, // 👈 ADD THIS LINE
        ]);

        $totalAmount = 0;

        // 2) Create each line item and update product stock
        foreach ($validated['items'] as $itemData) {
            $product = Product::findOrFail($itemData['product_id']);

            $quantity   = (int) $itemData['quantity'];
            $unitPrice  = (float) $itemData['unit_price'];
            $lineTotal  = $quantity * $unitPrice;

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id'  => $product->id,
                'quantity'    => $quantity,
                'unit_price'  => $unitPrice,
                'line_total'  => $lineTotal,
            ]);

            // 3) Update product stock and last buy price
            $product->current_stock += $quantity;
            $product->buy_price      = $unitPrice; // last cost
            $product->save();

            $totalAmount += $lineTotal;
        }

        // 4) Update total_amount
        $purchase->total_amount = $totalAmount;
        $purchase->save();
    });

    // Load items + products for response
    $purchase->load(['items.product']);

    return response()->json($purchase, Response::HTTP_CREATED);
}


    /**
     * Show one purchase with items.
     *
     * GET /api/purchases/{id}
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['items.product']);

        return response()->json($purchase);
    }

    // For now we do NOT support updating/deleting purchases
    // to avoid complex stock reversal logic.
}
