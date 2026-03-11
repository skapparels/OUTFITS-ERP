<?php

namespace App\Services;

use App\Models\InventoryTransfer;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StoreInventory;
use App\Models\WarehouseInventory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventorySystemService
{
    public function adjustStock(array $data, ?int $userId = null): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $row = $this->resolveInventoryRow($data['location_type'], $data['location_ref'], (int) $data['product_variant_id']);
            $newQty = $row->quantity + (int) $data['quantity_change'];

            if ($newQty < 0) {
                throw new RuntimeException('Stock cannot be negative after adjustment.');
            }

            $row->update(['quantity' => $newQty]);

            $movementType = $data['quantity_change'] >= 0 ? 'adjustment_in' : 'adjustment_out';
            $movement = StockMovement::query()->create([
                'product_variant_id' => $data['product_variant_id'],
                'location_type' => $data['location_type'],
                'location_ref' => $data['location_ref'],
                'movement_type' => $movementType,
                'quantity_change' => (int) $data['quantity_change'],
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'reason' => $data['reason'] ?? null,
                'performed_by' => $userId,
            ]);

            return ['inventory' => $row->refresh(), 'movement' => $movement];
        });
    }

    public function transferStock(array $data, ?int $userId = null): InventoryTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $from = $this->resolveInventoryRow($data['from_type'], $data['from_ref'], (int) $data['product_variant_id']);
            $to = $this->resolveInventoryRow($data['to_type'], $data['to_ref'], (int) $data['product_variant_id']);

            $qty = (int) $data['quantity'];
            if ($qty <= 0) {
                throw new RuntimeException('Transfer quantity must be positive.');
            }
            if ($from->quantity < $qty) {
                throw new RuntimeException('Insufficient stock at source location.');
            }

            $from->update(['quantity' => $from->quantity - $qty]);
            $to->update(['quantity' => $to->quantity + $qty]);

            $transfer = InventoryTransfer::query()->create([
                'product_variant_id' => $data['product_variant_id'],
                'from_type' => $data['from_type'],
                'from_ref' => $data['from_ref'],
                'to_type' => $data['to_type'],
                'to_ref' => $data['to_ref'],
                'quantity' => $qty,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            StockMovement::query()->create([
                'product_variant_id' => $data['product_variant_id'],
                'location_type' => $data['from_type'],
                'location_ref' => $data['from_ref'],
                'movement_type' => 'transfer_out',
                'quantity_change' => -$qty,
                'reference_type' => 'inventory_transfer',
                'reference_id' => (string) $transfer->id,
                'reason' => $data['notes'] ?? null,
                'performed_by' => $userId,
            ]);

            StockMovement::query()->create([
                'product_variant_id' => $data['product_variant_id'],
                'location_type' => $data['to_type'],
                'location_ref' => $data['to_ref'],
                'movement_type' => 'transfer_in',
                'quantity_change' => $qty,
                'reference_type' => 'inventory_transfer',
                'reference_id' => (string) $transfer->id,
                'reason' => $data['notes'] ?? null,
                'performed_by' => $userId,
            ]);

            return $transfer;
        });
    }

    private function resolveInventoryRow(string $type, string $ref, int $variantId)
    {
        ProductVariant::query()->findOrFail($variantId);

        if ($type === 'store') {
            return StoreInventory::query()->firstOrCreate(
                ['store_id' => (int) $ref, 'product_variant_id' => $variantId],
                ['quantity' => 0]
            );
        }

        if ($type === 'warehouse') {
            return WarehouseInventory::query()->firstOrCreate(
                ['warehouse_code' => $ref, 'product_variant_id' => $variantId],
                ['zone' => null, 'rack' => null, 'quantity' => 0]
            );
        }

        throw new RuntimeException('Invalid inventory location type.');
    }
}
