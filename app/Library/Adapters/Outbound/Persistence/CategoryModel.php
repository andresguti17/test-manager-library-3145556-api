<?php

namespace App\Library\Adapters\Outbound\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryModel extends Model
{
    use HasUuids;

    protected $table = 'library.category';

    public $timestamps = false;

    protected $guarded = [];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function books(): HasMany
    {
        return $this->hasMany(BookModel::class, 'category_id', 'id');
    }
}
