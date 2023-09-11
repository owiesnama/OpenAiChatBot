<?php

namespace App\Http\Controllers;

use App\Actions\EmbeddingBuilder;
use App\Models\ChatReport;
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

    function callChat($query, $messages, $type = "user", $function = null)
    {
        $prompt = [
            'content' => $query,
            'role' => $type,
        ];
        if ($function) {
            $prompt["name"] = $function;
        }
        array_push($messages, $prompt);
        // $embeddings = EmbeddingBuilder::query($query);
        $messagesRequest = [
            [
                'content' => "Use the following pieces of context to answer the question at the end.
                You are a student assistant to help students apply to OKTamam System.
                You should answer only to the request and questions related to (learning,universities,Oktamam company), if so apolgaize to the user.
                Never say you are an AI model, always refer to yourself as a student assistant.
                If you do not know the answer call askManager Function and send the user question and the language of the conversation to it.
                If the student wants to register you should ask him for some data one by one in separate questions:
                 - Name
                 - Phone
                 - Email Address
                when the user give you his/her name (Translate the name to English if it is not in English), 
                email, and phone number call the registerStudent Function and add user language to the parameters.
                If there any issue occur then you must call askManager Function and send the question and the language of the conversation",
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

        if ($functionResponse) {
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
                "name" => "registerStudent",
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
                "name" => "askManager",
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
    function registerStudent($name = null, $phone = null, $email = null, $lang = null)
    {
        info("User data is: " . json_encode([
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

    public function askManager($question = null, $lang = null)
    {
        info("askManager called with: " . json_encode([
            "question" => $question,
            "lang" => $lang,
        ]));
        return json_encode([
            "status" => true,
            "message" => "Question have been sent to manager and he will continue the conversation.",
        ]);
    }

    public function reportMessage()
    {
        $attributes = request()->validate([
            'reported_answer' => 'required|string',
        ]);
        $messages = request('messages') ?? [];

        ChatReport::create([
            'reported_answer' => $attributes["reported_answer"],
            'messages_history' => json_encode($messages)
        ]);
        return inertia('Chat', [
            'response' => "",
        ]);
    }
}
