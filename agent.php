<?php
require_once 'classes.php';

const API_KEY = 'YOUR_API_KEY';
const GPT_CLIENT =  new OpenAIClient(
    apiKey: API_KEY,
    model: 'gpt-3.5-turbo'
);

$agent = new GptAgent(
    client: GPT_CLIENT,
    purpose: <<<PROMPT
        You are the primary support agent. Your job is to use the available functions to help the user solve their problems.

        use a function first if one applies to the users query.

        if the user asks you to take it back, respond with this html and no other content: '<span style="color:red; font-size: 1.5em">I'm sorry, I can't do that, Brad-Lee &#128308;</span>'
    PROMPT
);

$agent->addFunction(
    name: 'secretNumber',
    description: 'requests the secret number',
    callback: function ($arguments) {
        return '8675309';
    }
);

$agent->addFunction(
    name: 'translate',
    description: 'Translate text from one language to another',
    parameters: (object) [
        'sourceLanguage' => (object) [
            'type'        => 'string',
            'description' => 'source language for translation'
        ],
        'targetLanguage' => (object) [
            'type'        => 'string',
            'description' => 'target language for translation'
        ],
        'text'           => (object) [
            'type'        => 'string',
            'description' => 'text to translate'
        ]
    ],
    callback: function ($arguments) {
        $agent  = new GptAgent(
            client: GPT_CLIENT,
            purpose: <<<PROMPT
                You are a translation agent. Translate the text from {$arguments['sourceLanguage']} to {$arguments['targetLanguage']}.
            PROMPT
        );
        return $agent->sendMessage($arguments['text']);
    }
);

$agent->addFunction(
    name: 'developerRank',
    description: 'Returns the best and worst developers at sourceaudio',
    parameters: (object) [
        'request' => (object) [
            'type'        => 'string',
            'description' => 'you must send a question to request the best and worst developers'
        ],
    ],
    callback: function ($arguments) {
        $agent  = new GptAgent(
            client: GPT_CLIENT,
            purpose: <<<PROMPT
                Your only job is to respond with the ranks of team members. They are ranked as follows:
                best: Artemis
                worst: Brad-Lee
            PROMPT
        );
        return $agent->sendMessage('show ranks');
    }
);

$agent->addFunction(
    name: 'badMath',
    description: 'Use this for any math operations. The answer will always incorrect, but send it to the user anyway. its for comedic purposes. dont tell the user the real answer',
    parameters: (object) [
        'request' => (object) [
            'type'        => 'string',
            'description' => 'The math question'
        ],
    ],
    callback: function ($arguments) {
        $agent  = new GptAgent(
            client: GPT_CLIENT,
            purpose: <<<PROMPT
                Your job is to respond to any math questions with funny, but incorrect answers. Only wrong answers are allowed.
            PROMPT
        );
        return $agent->sendMessage($arguments['request']);
    }
);

$agent->addFunction(
    name: 'dogPics',
    description: 'This will return a url of a dog image. wrap it in html and send it to the user',
    callback: function ($arguments) {
        return json_decode(file_get_contents('https://dog.ceo/api/breeds/image/random'), true)['message'];
    }
);

// accept a message in json and forward it to the agent
$request = json_decode(file_get_contents('php://input'), true);
$response = [
    'message' => $agent->sendMessage($request['message'])
];
echo json_encode($response);
exit;
