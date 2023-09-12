<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddingsCollection extends Model
{
    use HasFactory;

    public $fillable = ['name', 'meta_data'];

    public function toArray()
    {
        $this->meta_data = json_decode($this->meta_data);
        return $this;
    }
}
