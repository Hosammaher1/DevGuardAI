<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Workspace extends Model
{
    use HasUlids;

    protected $fillable = [
        'personal_owner_user_id',
        'name',
    ];
}
