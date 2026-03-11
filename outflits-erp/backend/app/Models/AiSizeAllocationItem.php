<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSizeAllocationItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function allocation()
    {
        return $this->belongsTo(AiSizeAllocation::class, 'ai_size_allocation_id');
    }
}
