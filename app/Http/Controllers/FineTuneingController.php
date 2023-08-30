<?php

namespace App\Http\Controllers;

use OpenAI\Laravel\Facades\OpenAI;

class FineTuneingController
{
    function store()
    {
        // $response = OpenAI::files()->upload([
        //     'purpose' => 'fine-tune',
        //     'file' => fopen('data.jsonl', 'r'),
        // ]);
        return dd($result = OpenAI::files()->list());
        // OpenAI::fineTuning()->createJob([
        //     'training_file' => collect($result['data'])->sortByDesc('created_at')->first()['id'],
        //     'model' => 'gpt-3.5-turbo',
        // ]);
    }
}
