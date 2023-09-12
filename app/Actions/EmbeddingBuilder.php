<?php
namespace App\Actions;

use OpenAI\Laravel\Facades\OpenAI;

class EmbeddingBuilder {
    public static function query($text){
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-ada-002',
            'input' => $text,
        ]);

        return $response['data'][0]['embedding'];       
    }
}