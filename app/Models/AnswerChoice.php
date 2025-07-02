<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerChoice extends Model
{
    protected $table = 'answer_choices';

    protected $fillable = [
        'answer_id',
        'choice_id',
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

    public function choice()
    {
        return $this->belongsTo(Choice::class);
    }
}
