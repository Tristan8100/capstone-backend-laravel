<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenValidation extends Model
{
    protected $table = 'token_validations';

    protected $fillable = [
        'user_id',
        'token_bearer',
        'user_agent',
    ];
}
