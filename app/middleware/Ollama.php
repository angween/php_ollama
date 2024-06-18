<?php
namespace Ollama;

use App\{
	Controller,
	Router
};

defined('APP_NAME') or exit('No direct script access allowed');

class Ollama
{
	private const LLM = OLLAMA_MODEL ?? 'llama3';
	private const LLM_TEMPERATURE = OLLAMA_TEMPERATURE ?? 0.6;

	private const URL_GENERATE = OLLAMA_GENERATE ?? 'http://localhost:11434/api/generate';

	private const URL_CHAT = OLLAMA_CHAT ?? 'http://localhost:11434/api/chat';

	private const SYSTEM_CONTENT = CHAT_SYSTEM ?? "You are an helpfull assistant, answer the user's question with the same language given.";

	public function __construct(
		private ?Controller $controller = null,
		private ?Router $router = null,
		private ?array $response = null,
		private array $conversationHistory = [],
		private array $post = [],
		private string $sessionID = ""
	) {
		$this->controller = $controller;

		$this->router = $router;
	}


	public function prompt()
	{
		// get the prompt
		$prompt = $this->router->clientPost['prompt'] ?? null;
		if (!$prompt)
			throw new \Exception("Missing a Prompt!");

		// sanitize prompt
		$prompt = $this->sanitizeString(input: $prompt);

		// load users conversation history for this session
		$sessionID = $this->router->clientPost['sessionId'] ?? null;


		// new prompt
		$newPrompt = [
			'role' => 'user',
			'content' => $prompt,
			'created' => time()
		];

		$conversationToSave = [];


		// load or set new conversation
		if ($sessionID == 'new') {
			$conversationHistory = [
				[
					"role" => "system",
					"content" => self::SYSTEM_CONTENT,
					"created" => time()
				]
			];

			$conversationToSave = $conversationHistory;
		} else {
			$conversationHistory = $this->loadConversation(filename: $sessionID, newPrompt: $newPrompt);
		}

		// append new prompt
		$conversationHistory[] = $newPrompt;


		// array to save later to file session
		$conversationToSave[] = $newPrompt;


		// prepare chatData
		$chatData = $this->prepareChatData(
			llm: self::LLM,
			conversationHistory: $conversationHistory,
			temperature: self::LLM_TEMPERATURE
		);


		// send chatData to Ollama
		$this->getResponOllama(url: self::URL_CHAT, chatData: $chatData);


		// debug Simulation Response -- REMOVE
		// $this->response = [
		// 	"role" => "assistant",
		// 	"content" => "It's-a me, Mario! Ahahahaha! Don't worry, I'm on it! That no-good Koopa King is always causing trouble!"
		// ];


		// handle respon
		if (!$this->response) {
			throw new \Exception("AI did not Response!");
		} else {
			$this->response['created'] = time();
		}


		// update conversationtoSave
		$conversationToSave[] = $this->response;


		// save conversation 
		$this->saveConversation(
			sessionID: $sessionID,
			newPrompt: $conversationToSave,
		);

		$this->response['sessionID'] = [
			'id' => $this->sessionID,
			'title' => $prompt,
			'created' => time()
		];

		// return respon to Front End
		$this->controller->response(message: $this->response);
	}


	private function loadConversation(
		string $filename,
		array $newPrompt,
	): ?array {
		$filename = "../session/" . $filename . ".txt";

		if (file_exists($filename)) {
			$contentFile = file_get_contents($filename);

			// add brackets so it can be treat as array
			$contentFile = "[$contentFile]";

			$array = json_decode($contentFile, true);

			return $array;
		} else {
			throw new \Exception("File not found!");
		}
	}


	private function saveConversationOLD(
		string $sessionID,
		array $conversation,
	): bool {
		// set the $this->sessionID
		if ($sessionID == 'new')
			$sessionID = date('ymd') . '_' . uniqid();

		$filename = "../session/" . $sessionID . "txt";

		$this->sessionID = $sessionID;

		// print_r($conversation); exit;

		// Save the updated array back to the file
		$jsonArray = json_encode($conversation);

		file_put_contents($filename, $jsonArray);

		return true;
	}


	private function saveConversation(
		string $sessionID,
		array $newPrompt,
	): bool {
		// set the $this->sessionID
		if ($sessionID == 'new') $sessionID = date('ymdhi') . '_' . uniqid();

		$filename = "../session/" . $sessionID . ".txt";

		$this->sessionID = $sessionID;


		// make it json
		$conversation = json_encode($newPrompt);


		// Load file
		if (file_exists($filename)) {
			$fileHandle = fopen($filename, 'a');

			// Add a comma if the file is not empty
			if (filesize($filename) > 0) {
				fwrite($fileHandle, ",\n");
			}

			// Write the new array in JSON format to the file
			fwrite($fileHandle, trim($conversation, "[]\n"));

			fclose($fileHandle);
		} else {
			// Create a new file and write the new array
			file_put_contents($filename, trim($conversation, "[]\n"));
		}

		return true;
	}


	private function getResponOllama(string $url, array $chatData)
	{
		// echo $url,"\n"; print_r($chatData); exit;

		$ch = curl_init();

		$chatData = json_encode($chatData);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $chatData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen($chatData)
		]);

		// Execute the cURL request
		$response = curl_exec($ch);


		// Check for errors
		if (curl_errno($ch)) {
			$errorMessage = curl_error($ch);

			curl_close($ch);

			throw new \Exception('Error: ' . $errorMessage);
		} else {
			$response = json_decode($response, true);

			if (isset($response['response'])) {
				$this->response = $response['response'];
			}

			if (isset($response['message'])) {
				if (is_array($response['message']))
					$this->response = $response['message'];
				else
					$this->response = [
						'role' => 'assistant',
						'content' => $response['message']
					];
			}

			curl_close($ch);

			return true;
		}
	}


	private function sanitizeString(string $input): string
	{
		// Step 1: Remove unwanted HTML tags
		$input = strip_tags($input);

		// Step 2: Extract email addresses, URLs, and numbers
		$pattern = '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})|((http|https):\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/[a-zA-Z0-9._%+-]*)?)|(\b\d+\b)/';
		preg_match_all($pattern, $input, $matches);

		// $matches[0] contains all matched emails, URLs, and numbers
		$preservedElements = $matches[0];

		// Step 3: Remove all special characters except spaces, preserved elements, and allowed punctuation
		$sanitized = preg_replace($pattern, '', $input); // Remove preserved elements
		$sanitized = preg_replace('/[^a-zA-Z0-9\s!$?]/', '', $sanitized); // Remove special characters except allowed ones

		// Step 4: Insert preserved elements back into the sanitized string
		foreach ($preservedElements as $element) {
			$sanitized .= ' ' . $element;
		}

		// Step 5: Trim and normalize spaces
		$sanitized = preg_replace('/\s+/', ' ', $sanitized);
		$sanitized = trim($sanitized);

		return $sanitized;
	}


	private function prepareChatData(
		string $llm,
		array $conversationHistory,
		float $temperature = 0.5,
	): array {
		// create
		// $newConversation = [
		// 	'role' => 'user',
		// 	'content' => $newPrompt,
		// 	'created' => time()
		// ];

		// if ($sessionID == 'new')
		// 	$conversationHistory = [$conversationHistory, $newConversation];
		// else
		// 	$conversationHistory[] = $newConversation;

		return [
			"model" => $llm,
			"stream" => false,
			"options" => [
				"temperature" => $temperature,
			],
			"messages" => $conversationHistory
		];
	}
}