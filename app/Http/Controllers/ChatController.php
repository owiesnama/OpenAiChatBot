<?php

namespace App\Http\Controllers;

use App\Actions\Api\RegisterLead;
use App\Actions\Api\SendToZapier;
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
use Gioni06\Gpt3Tokenizer\Gpt3TokenizerConfig;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

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

        $config = new Gpt3TokenizerConfig();
        $tokenizer = new Gpt3Tokenizer($config);

        $tokens = 0;
        foreach ($messagesRequest as $message) {
            $tokens += $tokenizer->count($message['content'] ?? '');
        }

        info($tokens);

        while($tokens > (4096 - 145)) {
            array_shift($messagesRequest);
            $tokens = 0;
            foreach ($messagesRequest as $message) {
                $tokens += $tokenizer->count($message['content'] ?? '');
            }
            info($tokens);
        }


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

        // RegisterLead::dispatch($name, $email, $phone);
        SendToZapier::dispatch($name, $email, $phone);

        return ["status" => false, "message" => "Fail to save student data.",];
    }

    public function askManager($question = null, $lang = null)
    {
        info("askManager called with: " . json_encode([
            "question" => $question,
            "lang" => $lang,
        ]));
        return [
            "role" => 'system',
            "content" => "Question have been sent to manager and he will continue the conversation.",
        ];
    }

    public function getInitialPrompt($context = '')
    {
        $text = file_get_contents(public_path('initial-prompt.txt'));
        return str_replace('{context}', $context, $text);
    }
}
