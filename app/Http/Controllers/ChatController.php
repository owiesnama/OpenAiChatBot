<?php

namespace App\Http\Controllers;

use App\Actions\Api\RegisterLead;
use App\Actions\Api\SendToZapier;
use App\Actions\EmbeddingBuilder;
use App\Models\Embedding;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
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
        try{
            $completions = $this->chat($attributes['prompt'], $messages);
        }catch(Exception $e){
            info($e->getMessage());
           return inertia('Chat', [
                'response' => [
                    "role" => 'system',
                    "content" => "Sorry something went wrong, try again"
           ]
            ]);
        }

        return inertia('Chat', [
            'response' => $completions['choices'][0]['message']
        ]);
    }
    /**
     *
     * @param mixed $content
     * @param mixed $messages
     * @param string $role
     * @param mixed $functionName
     * @return mixed
     * @throws ErrorException
     * @throws UnserializableResponse
     * @throws TransporterException
     * @throws BindingResolutionException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     */
    public function chat($content, $messages, $role = "user", $functionName = null)
    {
        $prompt = [
            'content' => $content,
            'role' => $role,
        ];
        if ($functionName) {
            $prompt["name"] = $functionName;
        }
        info($prompt);
        array_push($messages, $prompt);

        $vectors = EmbeddingBuilder::query($content);
        $embeddings = Embedding::whereVectors($vectors)->limit(2)->get();
        $textualContext = $embeddings->map(fn ($embedding) => $embedding->text)->implode("\n");
        $messagesRequest = [
            [
                'content' => $this->getInitialPrompt($textualContext),
                'role' => 'system'
            ],
            ...$messages,
        ];

        $tokenizer = new Gpt3Tokenizer(
            new Gpt3TokenizerConfig
        );

        $tokens = 0;
        foreach ($messagesRequest as $message) {
            $tokens += $tokenizer->count($message['content'] ?? '');
        }


        while ($tokens > (4096 - 145)) {
            array_shift($messagesRequest);
            $tokens = 0;
            foreach ($messagesRequest as $message) {
                $tokens += $tokenizer->count($message['content'] ?? '');
            }
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
        // RegisterLead::dispatch($name, $email, $phone);
        SendToZapier::dispatch($name, $email, $phone);

        return ["status" => true, "message" => "Student data has been saved.",];
    }

    public function askManager($question = null, $lang = null)
    {
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
