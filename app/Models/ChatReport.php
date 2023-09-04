<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatReport extends Model
{
    use HasFactory;

    protected $jsonable = ["messages_history"];
    protected $fillable = ["reported_answer", "messages_history"];
}
