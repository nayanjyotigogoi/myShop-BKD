<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /* =========================================================
     * Helpers
     * ======================================================= */

    private function resolveDates(Request $request): array
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        return [$from, $to];
    }

    private function groupExpression(string $period): string
    {
        return match ($period) {
            'yearly'  => "YEAR(sales.sale_date)",
            'monthly' => "DATE_FORMAT(sales.sale_date, '%Y-%m')",
            default   => "DATE(sales.sale_date)", // daily
        };
    }

    private function labelExpression(string $period): string
    {
        return match ($period) {
            'yearly'  => "YEAR(sales.sale_date) as label",
            'monthly' => "DATE_FORMAT(sales.sale_date, '%Y-%m') as label",
            default   => "DATE(sales.sale_date) as label",
        };
    }

    /* =========================================================
     * SUMMARY (Widgets)
     * GET /api/reports/summary
     * ======================================================= */

    public function summary(Request $request)
    {
        [$from, $to] = $this->resolveDates($request);

        $row = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->selectRaw("
                SUM(sale_items.line_total) as sales,
                SUM(sale_items.unit_cost * sale_items.quantity) as cost,
                SUM(sale_items.line_profit) as profit,
                COUNT(DISTINCT sales.id) as invoices,
                SUM(sale_items.quantity) as products_sold
            ")
            ->first();

        return response()->json([
            'sales'         => (float) ($row->sales ?? 0),
            'cost'          => (float) ($row->cost ?? 0),
            'profit'        => (float) ($row->profit ?? 0),
            'invoices'      => (int) ($row->invoices ?? 0),
            'products_sold' => (int) ($row->products_sold ?? 0),
        ]);
    }

    /* =========================================================
     * CHART DATA
     * GET /api/reports/chart
     * ======================================================= */

    public function chart(Request $request)
    {
        [$from, $to] = $this->resolveDates($request);
        $period = $request->input('period', 'daily');

        $rows = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy(DB::raw($this->groupExpression($period)))
            ->orderBy(DB::raw($this->groupExpression($period)))
            ->selectRaw("
                {$this->labelExpression($period)},
                SUM(sale_items.line_total) as sales,
                SUM(sale_items.line_profit) as profit
            ")
            ->get();

        return response()->json(
            $rows->map(fn ($r) => [
                'label'  => (string) $r->label,
                'sales'  => (float) $r->sales,
                'profit' => (float) $r->profit,
            ])
        );
    }

    /* =========================================================
     * TOP PRODUCTS
     * GET /api/reports/top-products
     * ======================================================= */

    public function topProducts(Request $request)
    {
        [$from, $to] = $this->resolveDates($request);
        $limit = (int) $request->input('limit', 10);

        $rows = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy(
                'sale_items.product_id',
                'products.name'
            )
            ->orderByDesc(DB::raw('SUM(sale_items.quantity)'))
            ->limit($limit)
            ->selectRaw("
                sale_items.product_id as id,
                products.name,
                SUM(sale_items.quantity) as units_sold,
                SUM(sale_items.line_total) as revenue
            ")
            ->get();

        return response()->json(
            $rows->map(fn ($r) => [
                'id'         => (int) $r->id,
                'name'       => $r->name,
                'units_sold' => (int) $r->units_sold,
                'revenue'    => (float) $r->revenue,
            ])
        );
    }
}
