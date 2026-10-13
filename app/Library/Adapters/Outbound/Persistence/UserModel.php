<?php

namespace App\Library\Adapters\Outbound\Persistence;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserModel extends Model
{
    use HasUuids;

    protected $table = 'library.user';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = [
        'password_hash',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(LoanModel::class, 'loaned_by_user_id', 'id');
    }
}
