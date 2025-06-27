<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementImage extends Model
{
    protected $table = 'announcement_images';

    protected $fillable = ['announcement_id', 'image_name', 'image_file'];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }
}
