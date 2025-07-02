<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
    protected $table = 'choices';

    protected $fillable = [
        'question_id',
        'choice_text',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answerChoices()
    {
        return $this->hasMany(AnswerChoice::class);
    }
}
