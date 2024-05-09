<?php
enum MessageRole: string {
    case system = 'system';
    case user = 'user';
    case assistant = 'assistant';
    case function = 'function';
}

class GptMessage {
    public $role;
    public $content;
    public $name;
    public $function_call;

    public function __construct(MessageRole $role, string $content, string $name = null, string $functionCall = null) {
        $this->role = $role;
        $this->content = $content;
        $this->name = $name;
        $this->function_call = $functionCall;
    }
}

class GptFunction {
    public $name;
    public $description;
    public $parameters;

    public function __construct(string $name, string $description, object $parameters) {
        $this->name = $name;
        $this->description = $description;
        $this->parameters = $parameters;
    }
}

class GptRequest {
    public $messages = [];
    public $functions = [];

    public function __construct(string $model) {
        $this->model = $model;
    }

    public function addMessage(MessageRole $role, string $content, string $name = null, $functionCall = null) {
        $message = [
            'role' => $role->value,
            'content' => $content
        ];
        if ($name) $message['name'] = $name;
        if ($functionCall) $message['function_call'] = $functionCall;

        $this->messages[] = $message;
    }

    public function addFunction(string $name, string $description, object $parameters) {
        $this->functions[] = (object) [
            'name'          => $name,
            'description'   => $description,
            'parameters'    => $parameters
        ];
    }
}

class OpenAIClient {
    private $apiKey;
    private $apiEndpoint = 'https://api.openai.com/v1/chat/completions';

    private $model;

    public function __construct(string $apiKey, string $model) {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function call(array $messages, array $functions) {
        $ch = curl_init($this->apiEndpoint);

        $data = [
            'model' => $this->model,
            'messages' => $messages,
        ];
        // only include functions if they exist
        if ($functions) $data['functions'] = $functions;

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $result = curl_exec($ch);

        if (!$result) {
            throw new Exception('Error: ' . curl_error($ch));
        }

        curl_close($ch);

        $responseObject = json_decode($result, true);

        return $responseObject['choices'][0]['message'];
    }
}

class GptAgent {
    private $client;
    private $messages = [];
    private $functions = [];
    private $callables = [];


    public function __construct(OpenAIClient $client, string $purpose) {
        $this->client = $client;

        // give the agent a purpose
        $this->addMessage(
            role: MessageRole::system,
            content: $purpose
        );
    }

    public function addMessage(MessageRole $role, string $content, string $name = null, $functionCall = null) {
        $message = [
            'role' => $role->value,
            'content' => $content
        ];
        // only include name and function_call if they exist
        if ($name) $message['name'] = $name;
        if ($functionCall) $message['function_call'] = $functionCall;

        $this->messages[] = $message;
    }

    public function addFunction(string $name, string $description, callable $callback, object $parameters = null) {

        // wrap parameters
        $wrappedParameters = (object)[
            'type'       => 'object',
            'properties' => (object) [
                empty($parameters) ? (object) [] : $parameters // if empty, use empty object
            ]
        ];

        $this->functions[] = (object) [
            'name'          => $name,
            'description'   => $description,
            'parameters'    => $wrappedParameters
        ];

        $this->callables[$name] = $callback;
    }

    public function sendMessage(?string $userMessage = 'im requesting a response') {
        $this->addMessage(
            role: MessageRole::user,
            content: $userMessage
        );

        return $this->callApi();
    }

    private function callApi() {
        $response = $this->client->call(
            messages: $this->messages,
            functions: $this->functions
        );
        $this->messages[] = $response;

        // if response is a function call, call the function, add the result to messages and resend
        $functionCall = $response['function_call'] ?? null;
        if ($functionCall) {

            $this->callFunction(
                name: $functionCall['name'],
                arguments: empty($functionCall['arguments']) ? [] : json_decode($functionCall['arguments'], true)
            );
            return $this->callApi();
        } else {
            return $response['content'];
        }
    }

    private function callFunction($name, $arguments) {

        $result = $this->callables[$name]($arguments ?? []);

        $this->addMessage(
            role: MessageRole::function,
            content: $result,
            name: $name
        );
    }
}
