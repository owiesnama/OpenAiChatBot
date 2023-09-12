<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Embedding extends Model
{
    use HasFactory;

    public $fillable = ['embeddings_collection_id', 'text', 'embedding'];

    public function scopeWhereVectors($query, $vectors)
    {
        $vectors = json_encode($vectors);
        return $query
            ->select("text")
            ->selectSub("embedding <=> '{$vectors}'::vector", "distance")
            ->orderBy('distance', 'asc');
    }
}
