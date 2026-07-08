<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function findByEan(Request $request)
    {
        $ean = $request->query('ean');
        \Illuminate\Support\Facades\Log::info('Scanned EAN: ' . $ean);
        $product = Product::where('ean_code', $ean)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'ean_code' => $product->ean_code,
                'price' => (float) $product->price,
            ]
        ]);
    }

    public function findByEanOld($ean)
    {
        \Illuminate\Support\Facades\Log::info('Scanned EAN (OLD APP): ' . $ean);
        $product = Product::where('ean_code', $ean)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'ean_code' => $product->ean_code,
                'price' => (float) $product->price,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'ean_code' => 'required|string|unique:products,ean_code',
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'product' => $product
        ], 201);
    }
}
