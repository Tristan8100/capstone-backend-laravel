<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{

    protected $table = 'activity_logs';

    protected $fillable = [
        'admin_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * The admin who performed the action.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

}
