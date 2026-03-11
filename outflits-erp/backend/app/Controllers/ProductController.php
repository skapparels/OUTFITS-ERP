<?php

namespace App\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()->with(['style.collection', 'variants'])->paginate($request->integer('per_page', 25));
        return ProductResource::collection($products);
    }

    public function store(Request $request): ProductResource
    {
        $data = $request->validate([
            'style_id' => ['required', 'exists:styles,id'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,clearance,discontinued'],
            'hsn_code_id' => ['nullable', 'exists:hsn_codes,id'],
            'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
        ]);

        return new ProductResource(Product::query()->create($data));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['style.collection', 'variants']));
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $product->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive,clearance,discontinued'],
        ]));

        return new ProductResource($product->refresh());
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }
}
