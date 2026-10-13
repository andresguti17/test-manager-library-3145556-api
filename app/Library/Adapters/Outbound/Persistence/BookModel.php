<?php

namespace App\Library\Adapters\Outbound\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookModel extends Model
{
    use HasUuids;

    protected $table = 'library.book';

    public $timestamps = false;

    protected $guarded = [];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id');
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItemModel::class, 'book_id', 'id');
    }
}
