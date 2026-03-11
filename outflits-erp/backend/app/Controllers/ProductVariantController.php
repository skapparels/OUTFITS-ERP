<?php

namespace App\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController
{
    public function index(Request $request)
    {
        return ProductVariant::query()
            ->with('product.style.collection')
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->paginate($request->integer('per_page', 50));
    }

    public function store(Request $request)
    {
        return ProductVariant::query()->create($request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'color' => ['required', 'string', 'max:100'],
            'size' => ['required', 'string', 'max:40'],
            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'barcode' => ['nullable', 'string', 'max:120', 'unique:product_variants,barcode'],
            'mrp' => ['required', 'numeric', 'min:0'],
            'moq' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'preferred_supplier_id' => ['nullable', 'exists:suppliers,id'],
            'is_active' => ['nullable', 'boolean'],
        ]));
    }

    public function show(ProductVariant $variant)
    {
        return $variant->load('product.style.collection');
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $variant->update($request->validate([
            'color' => ['sometimes', 'string', 'max:100'],
            'size' => ['sometimes', 'string', 'max:40'],
            'sku' => ['sometimes', 'string', 'max:100', 'unique:product_variants,sku,' . $variant->id],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:120', 'unique:product_variants,barcode,' . $variant->id],
            'mrp' => ['sometimes', 'numeric', 'min:0'],
            'moq' => ['sometimes', 'integer', 'min:0'],
            'min_stock' => ['sometimes', 'integer', 'min:0'],
            'max_stock' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'lead_time_days' => ['sometimes', 'integer', 'min:0'],
            'preferred_supplier_id' => ['sometimes', 'nullable', 'exists:suppliers,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return $variant->refresh();
    }

    public function destroy(ProductVariant $variant)
    {
        $variant->delete();
        return response()->noContent();
    }
}
