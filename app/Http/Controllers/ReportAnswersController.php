<?php

namespace App\Http\Controllers;

use App\Models\ChatReport;

class ReportAnswersController {
    
    public function store()
    {
        $attributes = request()->validate([
            'reported_answer' => 'required|string',
            'messages' => 'sometimes'
        ]);
        ChatReport::create([
            "reported_answer" => $attributes['reported_answer'],
            "messages" => collect($attributes['messages'])
        ]);

        return back();
    }

}
