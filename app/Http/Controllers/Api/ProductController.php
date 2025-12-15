<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * List products (with optional filters: search, category, gender/target_group, size).
     *
     * - category: product type (e.g. T-Shirt, Jeans)
     * - gender: target group (male, female, boys, girls, unisex)
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Optional search by name or code
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Optional filters
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender); // male / female / boys / girls / unisex
        }

        if ($size = $request->input('size')) {
            $query->where('size', $size);
        }

        // For now, just return all (small shop).
        // Later you can change to paginate() if needed.
        $products = $query->orderBy('name')->get();

        return response()->json($products);
    }

    /**
     * Create a new product.
     * Expects opening_stock from UI, but stores it in current_stock.
     *
     * - category: product type (e.g. T-Shirt, Jeans, Dress)
     * - gender:   target group (male, female, boys, girls, unisex)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'         => 'required|string|max:50|unique:products,code',
            'name'         => 'required|string|max:255',

            // Product type, keep it flexible for now
            'category'     => 'required|string|max:50',

            // Target group: enforce allowed values
            'gender'       => 'required|string|in:male,female,boys,girls,unisex',

            'size'         => 'nullable|string|max:50',
            'color'        => 'nullable|string|max:50',

            'buy_price'    => 'required|numeric|min:0',
            'sell_price'   => 'required|numeric|min:0',

            // Comes from UI as "Opening Stock"
            'opening_stock' => 'nullable|integer|min:0',
        ]);

        $openingStock = $validated['opening_stock'] ?? 0;
        unset($validated['opening_stock']);

        $product = new Product($validated);
        $product->current_stock = $openingStock;
        $product->save();

        return response()->json($product, Response::HTTP_CREATED);
    }

    /**
     * Show a single product.
     */
    public function show(Product $product)
    {
        return response()->json($product);
    }

    /**
     * Update product details.
     * We intentionally do NOT change current_stock here (stock should move via purchases/sales),
     * unless you decide to allow manual stock edit later.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code'       => 'sometimes|required|string|max:50|unique:products,code,' . $product->id,
            'name'       => 'sometimes|required|string|max:255',

            // Product type
            'category'   => 'sometimes|required|string|max:50',

            // Target group
            'gender'     => 'sometimes|required|string|in:male,female,boys,girls,unisex',

            'size'       => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:50',
            'buy_price'  => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',

            // We ignore opening_stock on update for now
            'opening_stock' => 'prohibited',
        ]);

        $product->fill($validated);
        $product->save();

        return response()->json($product);
    }

    /**
     * Delete a product.
     * We block deletion if there are related sales or purchases,
     * because that would break history.
     */
    public function destroy(Product $product)
    {
        if ($product->purchaseItems()->exists() || $product->saleItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete product that has purchase or sale history.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
