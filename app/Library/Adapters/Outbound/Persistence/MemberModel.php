<?php

namespace App\Library\Adapters\Outbound\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberModel extends Model
{
    use HasUuids;

    protected $table = 'library.member';

    public $timestamps = false;

    protected $guarded = [];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(LoanModel::class, 'member_id', 'id');
    }
}
