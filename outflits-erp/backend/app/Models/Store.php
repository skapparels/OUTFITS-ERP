<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }
}
