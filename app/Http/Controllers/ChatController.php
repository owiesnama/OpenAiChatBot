<?php

namespace App\Http\Controllers;

use App\Actions\EmbeddingBuilder;
use App\Models\ChatReport;
use App\Models\Embedding;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\ResponseFactory;
use Inertia\Response;
use OpenAI\Exceptions\InvalidArgumentException;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\UnserializableResponse;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Laravel\Facades\OpenAI;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ChatController
{
    /**
     * 
     * @return ResponseFactory|Response 
     * @throws RuntimeException 
     */
    public function index()
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
    public function store()
    {

        $attributes = request()->validate([
            'prompt' => 'required|string',
        ]);
        $messages = request('messages') ?? [];

        $completions = $this->chat($attributes['prompt'], $messages);

        return inertia('Chat', [
            'response' => $completions['choices'][0]['message']
        ]);
    }
    /**
     * 
     * @param mixed $query 
     * @param mixed $messages 
     * @param string $type 
     * @param mixed $function 
     * @return mixed 
     * @throws ErrorException 
     * @throws UnserializableResponse 
     * @throws TransporterException 
     * @throws BindingResolutionException 
     * @throws NotFoundExceptionInterface 
     * @throws ContainerExceptionInterface 
     * @throws InvalidArgumentException 
     */
    public function chat($query, $messages, $type = "user", $function = null)
    {
        $prompt = [
            'content' => $query,
            'role' => $type,
        ];
        if ($function) {
            $prompt["name"] = $function;
        }
        array_push($messages, $prompt);
        

        $tokens = 0;
        foreach ($messages as $message) {
            $tokens += mb_strlen($message['content'] ?? '', 'UTF-8');
        }

        while($tokens > (4096 - 145)) {
            info('max_info');
            array_shift($messages);
            $tokens = 0;
            foreach ($messages as $message) {
                $tokens += mb_strlen($message['content'] ?? '', 'UTF-8');
            }
        }

        $vectors = EmbeddingBuilder::query($query);
        $embeddings = Embedding::whereVectors($vectors)->limit(2)->get();
        $textualContext = $embeddings->map(fn ($embedding) => $embedding->text)->implode("\n");
        $messagesRequest = [
            [
                'content' => $this->getInitialPrompt($textualContext),
                'role' => 'system'
            ],
            ...$messages,
        ];
        $completions = OpenAi::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'functions' =>  config("openai.functions"),
            'messages' => $messagesRequest,
        ]);
        $functionResponse = $this->handleOpenAiFunctionCalls($completions);

        if ($functionResponse) {
            array_push($messages, $completions['choices'][0]['message']);
            $functionName = $completions['choices'][0]['message']['function_call']['name'];
            $completions = $this->chat($functionResponse, $messages, "function", $functionName);
        }

        return $completions;
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

        return ["status" => false, "message" => "Fail to save student data.",];
    }

    public function askManager($question = null, $lang = null)
    {
        info("askManager called with: " . json_encode([
            "question" => $question,
            "lang" => $lang,
        ]));
        return [
            "status" => true,
            "message" => "Question have been sent to manager and he will continue the conversation.",
        ];
    }

    public function getInitialPrompt($context = '')
    {
        $text = file_get_contents(public_path('initial-prompt.txt'));
        return str_replace('{context}', $context, $text);
    }

    public function sendToHook()
    {
        Http::post('https://hooks.zapier.com/hooks/catch/3152365/35twx3b/');
    }
}
