<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Daily sales & profit report for a date range.
     *
     * GET /api/reports/daily?startDate=2025-12-01&endDate=2025-12-07
     *
     * Response:
     * [
     *   {
     *     "date": "2025-12-01",
     *     "sales": 12500,
     *     "cost": 8750,
     *     "profit": 3750,
     *     "margin": 30
     *   },
     *   ...
     * ]
     */
    public function daily(Request $request)
    {
        // Parse dates or default to last 30 days
        $startDate = $request->input('startDate');
        $endDate   = $request->input('endDate');

        if ($startDate) {
            $start = Carbon::parse($startDate)->startOfDay();
        } else {
            $start = Carbon::today()->subDays(29)->startOfDay();
        }

        if ($endDate) {
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            $end = Carbon::today()->endOfDay();
        }

        // Aggregate by date from sale_items + sales
        $rows = SaleItem::selectRaw(
                'DATE(sales.sale_date) as date,
                 SUM(sale_items.line_total) as sales,
                 SUM(sale_items.unit_cost * sale_items.quantity) as cost,
                 SUM(sale_items.line_profit) as profit'
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Index by date string for easy lookup
        $byDate = $rows->keyBy('date');

        $data = [];
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        // Build continuous date range even if some days have no sales
        while ($cursor <= $lastDay) {
            $key = $cursor->toDateString();

            $row   = $byDate->get($key);
            $sales = $row?->sales ?? 0;
            $cost  = $row?->cost ?? 0;
            $profit = $row?->profit ?? 0;

            $margin = $sales > 0
                ? round(($profit / $sales) * 100, 2)
                : 0;

            $data[] = [
                'date'   => $key,
                'sales'  => (float) $sales,
                'cost'   => (float) $cost,
                'profit' => (float) $profit,
                'margin' => $margin,
            ];

            $cursor->addDay();
        }

        return response()->json($data);
    }

    /**
     * Top selling products in a date range.
     *
     * GET /api/reports/top-products?startDate=2025-12-01&endDate=2025-12-31&limit=10
     *
     * Response:
     * [
     *   {
     *     "product_id": 1,
     *     "code": "TS001",
     *     "name": "Basic T-Shirt",
     *     "category": "Kids",
     *     "quantity_sold": 125,
     *     "revenue": 43750,
     *     "profit": 15000
     *   },
     *   ...
     * ]
     */
    public function topProducts(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate   = $request->input('endDate');
        $limit     = (int) ($request->input('limit', 10));

        if ($startDate) {
            $start = Carbon::parse($startDate)->startOfDay();
        } else {
            $start = Carbon::today()->subDays(29)->startOfDay();
        }

        if ($endDate) {
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            $end = Carbon::today()->endOfDay();
        }

        $rows = SaleItem::selectRaw(
                'sale_items.product_id,
                 products.code,
                 products.name,
                 products.category,
                 SUM(sale_items.quantity) as quantity_sold,
                 SUM(sale_items.line_total) as revenue,
                 SUM(sale_items.line_profit) as profit'
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$start, $end])
            ->groupBy(
                'sale_items.product_id',
                'products.code',
                'products.name',
                'products.category'
            )
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'product_id'    => $row->product_id,
                    'code'          => $row->code,
                    'name'          => $row->name,
                    'category'      => $row->category,
                    'quantity_sold' => (int) $row->quantity_sold,
                    'revenue'       => (float) $row->revenue,
                    'profit'        => (float) $row->profit,
                ];
            });

        return response()->json($rows);
    }
}
