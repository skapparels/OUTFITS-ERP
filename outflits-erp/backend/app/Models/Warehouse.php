<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function zones()
    {
        return $this->hasMany(WarehouseZone::class);
    }

    public function operations()
    {
        return $this->hasMany(WarehouseOperation::class);
    }
}
