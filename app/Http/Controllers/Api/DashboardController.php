<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary()
    {
        $today = Carbon::today();
        $now   = Carbon::now();

        // ---------- BASIC KPI CARDS ----------

        // Today’s sales (sum of sale.total)
        $todaySales = Sale::whereDate('sale_date', $today)->sum('total');

        // Today’s profit (sum of line_profit for today)
        $todayProfit = SaleItem::whereHas('sale', function ($q) use ($today) {
                $q->whereDate('sale_date', $today);
            })
            ->sum('line_profit');

        // Total stock value = sum(current_stock * buy_price)
        $totalStockValue = Product::selectRaw('SUM(current_stock * buy_price) as stock_value')
            ->value('stock_value') ?? 0;

        // Low stock threshold from settings (default = 5)
        $lowStockThreshold = (int) (Setting::getValue('low_stock_threshold', 5));

        $lowStockCount = Product::where('current_stock', '<', $lowStockThreshold)->count();

        // ---------- SALES TREND (LAST 7 DAYS) ----------

        $startDate = $today->copy()->subDays(6); // last 7 days including today

        // Sales total per day
        $salesPerDay = Sale::selectRaw('DATE(sale_date) as date, SUM(total) as total')
            ->whereBetween('sale_date', [$startDate->copy()->startOfDay(), $now])
            ->groupBy('date')
            ->pluck('total', 'date'); // ['2025-12-01' => 1200, ...]

        // Profit per day (from sale_items.line_profit)
        $profitPerDay = SaleItem::selectRaw('DATE(sales.sale_date) as date, SUM(line_profit) as profit')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$startDate->copy()->startOfDay(), $now])
            ->groupBy('date')
            ->pluck('profit', 'date'); // ['2025-12-01' => 300, ...]

        $salesTrend = [];

        // Build 7-day array even if no data on some days
        for ($d = $startDate->copy(); $d <= $today; $d->addDay()) {
            $key = $d->toDateString();

            $salesTrend[] = [
                'date'   => $key,
                'sales'  => (float) ($salesPerDay[$key] ?? 0),
                'profit' => (float) ($profitPerDay[$key] ?? 0),
            ];
        }

        // ---------- CATEGORY SALES (LAST 30 DAYS) ----------

        $categoryStart = $today->copy()->subDays(29);

        $categorySales = SaleItem::selectRaw('products.category as category, SUM(sale_items.line_total) as total')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$categoryStart->copy()->startOfDay(), $now])
            ->groupBy('products.category')
            ->get()
            ->map(function ($row) {
                return [
                    'category' => $row->category ?? 'Unknown',
                    'total'    => (float) $row->total,
                ];
            })
            ->values();

        // ---------- LOW STOCK ITEMS LIST (FOR TABLE) ----------

        $lowStockItems = Product::where('current_stock', '<', $lowStockThreshold)
            ->orderBy('current_stock', 'asc')
            ->limit(10)
            ->get([
                'id',
                'code',
                'name',
                'category',
                'size',
                'current_stock',
            ]);

        // ---------- FINAL RESPONSE ----------

        return response()->json([
            'todaySales'        => (float) $todaySales,
            'todayProfit'       => (float) $todayProfit,
            'totalStockValue'   => (float) $totalStockValue,
            'lowStockCount'     => (int) $lowStockCount,
            'lowStockThreshold' => $lowStockThreshold,

            'salesTrend'        => $salesTrend,
            'categorySales'     => $categorySales,

            'lowStockItems'     => $lowStockItems,
        ]);
    }
}
