<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $table = 'conversations';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', // Custom string ID
        'admin_id',
        'user_id',
        'last_message',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
