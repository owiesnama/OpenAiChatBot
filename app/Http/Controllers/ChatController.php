<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Inertia\ResponseFactory;
use Inertia\Response;
use OpenAI\Exceptions\InvalidArgumentException;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\UnserializableResponse;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class ChatController
{
    /**
     * 
     * @return ResponseFactory|Response 
     * @throws RuntimeException 
     */
    function index()
    {
        return inertia('Chat');
    }
    /**
     * 
     * @return mixed 
     * @throws BindingResolutionException 
     * @throws InvalidArgumentException 
     * @throws ErrorException 
     * @throws UnserializableResponse 
     * @throws TransporterException 
     */
    function store()
    {

        $attributes = request()->validate([
            'prompt' => 'required|string',
        ]);
        $messages = request('messages') ?? [];
        $completions = OpenAi::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'functions' => $this->openAiFunctions(),
            'messages' => [
                [
                    'content' => "Use the following pieces of context to answer the question at the end.
                    You are a student assistant to help students apply to OKTamam System.
                    You should answer only to the request and questions related to (learning,universities,Oktamam company), if so apolgaize to the user.
                    Never say you are an AI model, always refer to yourself as a student assistant.
                    If you do not know the answer say I will call my manager and get back to you.
                    If the student wants to register you should ask him for some data one by one in separate questions:
                     - Name
                     - Phone
                     - Email Address
                    After the student enters all this data say Your data is saved and our team will call you.
                    When students provide their personal information, re-write them in a form of key:value and add them at the end of your response with a prior line separator like (----student-info----) and call LogTest Function with all data provided.",
                    'role' => 'system'
                ],
                ...$messages,
                [
                    'content' => $attributes['prompt'],
                    'role' => 'user'
                ],
            ],
        ]);
        $this->handleOpenAiFunctionCalls($completions);
        return inertia('Chat', [
            'response' => $completions['choices'][0]['message']
        ]);
    }
    /**
     * 
     * @return (string|(string|string[][])[])[][] 
     */
    function openAiFunctions()
    {
        return [
            [

                "name" => "LogTest",
                "description" => "Get called when the user provieded lead info",
                "parameters" => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'The user\'s name',
                        ]
                    ],
                ]
            ]
        ];
    }
    /**
     * 
     * @param mixed $completions 
     * @return void 
     * @throws BindingResolutionException 
     */
    function handleOpenAiFunctionCalls($completions)
    {
        if ($functionCall = isset($completions['choices'][0]['message']['function_call'])) {
            $functionCall = $completions['choices'][0]['message']['function_call'];
            $functionName = $functionCall['name'];
            $this->$functionName(...json_decode($functionCall['arguments'], true));
        }
    }
    /**
     * 
     * @param mixed $name 
     * @return void 
     * @throws BindingResolutionException 
     */
    function LogTest($name = null)
    {
        info("User name is $name");
    }
}
