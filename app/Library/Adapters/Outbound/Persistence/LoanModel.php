<?php

namespace App\Library\Adapters\Outbound\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanModel extends Model
{
    use HasUuids;

    protected $table = 'library.loan';

    public $timestamps = false;

    protected $guarded = [];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected function casts(): array
    {
        return [
            'loaned_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(MemberModel::class, 'member_id', 'id');
    }

    public function loanedByUser(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'loaned_by_user_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LoanItemModel::class, 'loan_id', 'id');
    }
}
