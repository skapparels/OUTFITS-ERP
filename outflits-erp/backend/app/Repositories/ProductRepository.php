<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function paginated(int $perPage = 25): LengthAwarePaginator
    {
        return Product::query()->with(['style.collection', 'variants'])->paginate($perPage);
    }
}
