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
        $completions = $this->callChat($attributes['prompt'], $messages);
        return inertia('Chat', [
            'response' => $completions['choices'][0]['message']
        ]);
    }

    function callChat($query, $messages, $type = "user", $function = null){
        $prompt = [
            'content' => $query,
            'role' => $type,
        ];
        if($function){
            $prompt["name"] = $function;
        }
        array_push($messages, $prompt);

        $messagesRequest = [
            [
                'content' => "Use the following pieces of context to answer the question at the end.
                You are a student assistant to help students apply to OKTamam System.
                You should answer only to the request and questions related to (learning,universities,Oktamam company), if so apolgaize to the user.
                Never say you are an AI model, always refer to yourself as a student assistant.
                If you do not know the answer call AskManager Function and send the user question to it.
                If the student wants to register you should ask him for some data one by one in separate questions:
                 - Name
                 - Phone
                 - Email Address
                when the user give you his/her name (Translate the name to English if it is not in English), 
                email, and phone number call the RegisterStudent Function and add user language to the parameters.
                If there any issue occur then you must call AskManager Function and send the question and the language of the conversation",
                'role' => 'system'
            ],
            ...$messages,
        ];
        $completions = OpenAi::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'functions' => $this->openAiFunctions(),
            'messages' => $messagesRequest,
        ]);
        $functionResponse = $this->handleOpenAiFunctionCalls($completions);

        if($functionResponse){
            // Add prompt to old messages.
            array_push($messages, $completions['choices'][0]['message']);
            $functionName = $completions['choices'][0]['message']['function_call']['name'];
            $completions = $this->callChat($functionResponse, $messages, "function", $functionName);
        }
        return $completions;
    }
    /**
     * 
     * @return (string|(string|string[][])[])[][] 
     */
    function openAiFunctions()
    {
        return [
            [
                "name" => "RegisterStudent",
                "description" => "Get called when the user provieded lead info",
                "parameters" => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'The user\'s name',
                        ],
                        'email' => [
                            'type' => 'string',
                            'description' => 'The user\'s email',
                        ],
                        'phone' => [
                            'type' => 'string',
                            'description' => 'The user\'s phone',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'The user\'s conversation language as a lang code like en, ar, or tr',
                        ],
                    ],
                ]
            ],
            [
                "name" => "AskManager",
                "description" => "Get called when the answer is not known to the model",
                "parameters" => [
                    'type' => 'object',
                    'properties' => [
                        'question' => [
                            'type' => 'string',
                            'description' => 'The user\'s question',
                        ],
                        'lang' => [
                            'type' => 'string',
                            'description' => 'The user\'s conversation language as a lang code like en, ar, or tr',
                        ],
                    ],
                ]
            ],
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
            return $this->$functionName(...json_decode($functionCall['arguments'], true));
        }
        return null;
    }
    /**
     * 
     * @param mixed $name 
     * @return void 
     * @throws BindingResolutionException 
     */
    function RegisterStudent($name = null, $phone = null, $email = null, $lang = null)
    {
        info("User data is: ".json_encode([
            "name" => $name, 
            "email" => $email, 
            "phone" => $phone,
            "lang" => $lang,
        ]));
        return json_encode([
            "status" => false,
            "message" => "Fail to save student data.",
        ]);
    }

    function AskManager($question = null, $lang = null) {
        info("AskManager called with: ".json_encode([
            "question" => $question,
            "lang" => $lang,
        ]));
        return json_encode([
            "status" => true,
            "message" => "Question have been sent to manager and he will continue the conversation.",
        ]);
    }
}
