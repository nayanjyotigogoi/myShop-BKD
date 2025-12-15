<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    /**
     * Return current settings for the app.
     *
     * GET /api/settings
     *
     * Response:
     * {
     *   "shopName": "MyShop",
     *   "currency": "INR",
     *   "lowStockThreshold": 5
     * }
     */
    public function show()
    {
        $shopName = Setting::getValue('shop_name', 'MyShop');
        $currency = Setting::getValue('currency', 'INR');
        $lowStockThreshold = (int) Setting::getValue('low_stock_threshold', 5);

        return response()->json([
            'shopName'          => $shopName,
            'currency'          => $currency,
            'lowStockThreshold' => $lowStockThreshold,
        ]);
    }

    /**
     * Update settings.
     *
     * PUT /api/settings
     *
     * Body:
     * {
     *   "shopName": "MyShop - Main Branch",
     *   "currency": "INR",
     *   "lowStockThreshold": 3
     * }
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'shopName'          => 'required|string|max:255',
            'currency'          => 'required|string|max:10',
            'lowStockThreshold' => 'required|integer|min:0',
        ]);

        Setting::setValue('shop_name', $data['shopName']);
        Setting::setValue('currency', $data['currency']);
        Setting::setValue('low_stock_threshold', $data['lowStockThreshold']);

        return response()->json([
            'message' => 'Settings updated successfully.',
        ], Response::HTTP_OK);
    }
}
