<?php
namespace Ollama;

use App\Controller;

defined('APP_NAME') or exit('No direct script access allowed');

class Ollama
{
	private const LLM = OLLAMA_MODEL ?? 'llama3';

	private const URL_GENERATE = OLLAMA_GENERATE ?? 'http://localhost:11434/api/generate';

	private const URL_CHAT = OLLAMA_CHAT ?? 'http://localhost:11434/api/chat';

	private const SYSTEM_CONTENT = CHAT_SYSTEM ?? "You are an helpfull assistant, answer the user's question with the same language given.";

	public function __construct(
		private ?Controller $controller = null,
		private ?array $response = null,
		private array $conversationHistory = [],
	) {
		$this->controller = $controller;
	}


	public function prompt()
	{
		// get the prompt
		$prompt = $_POST['prompt'] ?? null;
		if ( ! $prompt ) throw new \Exception("Missing a Prompt!");

		// sanitize prompt
		$prompt = $this->sanitizeString(input: $prompt);

		// load users conversation history for this session
		$conversationHistory = [];
		// TODO
		// $conversationHistory = $this->loadConversation(user: $currentUser);

		// prepare chatData
		$chatData = $this->prepareChatData(
			llm: self::LLM,
			newPrompt: $prompt, 
			conversationHistory: $conversationHistory,
			temperature: 1
		);

		// send chatData to Ollama
		$this->getResponOllama(url: self::URL_CHAT, chatData: $chatData);

		// debug
		// $this->response = [
		// 	"role"=> "assistant",
		// 	"content"=> "It's-a me, Mario!\n\nAhahahaha! Don't worry, I'm on it! Your princess... hmm... could you be referring to Princess Peach? She's the ruler of the Mushroom Kingdom, and she's a real sweetheart.\n\nLet me check if she's in trouble again. *takes out a mushroom-sized map* Ah, yes! Bowser has kidnapped her once more! That no-good Koopa King is always causing trouble!\n\nDon't worry, I've got a plan to rescue her. I'll power-jump my way through the Mushroom Kingdom, avoiding Goombas and Koopa Troopas, and face off against that pesky Bowser himself!\n\nWant to join me on this adventure? We can work together to save Princess Peach!"
		// ];

		// handle respon
		if ( ! $this->response ) {
			throw new \Exception("AI did not Response!");
		}

		// save conversation 
		// TODO 
		// $this->saveConversation(newResponse: $response);


		// return respon to Front End
		$this->controller->response(message: $this->response);
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
				if ( is_array( $response['message'] ) ) $this->response = $response['message'];
				else $this->response = [
					'role' => 'assistant',
					'content' => $response['message']
				];
			}

			curl_close($ch);

			return true;
		}
	}


	private function sanitizeString(string $input) :string {
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
		string $newPrompt,
		float $temperature = 0.5,
		array $conversationHistory = []
	) :array {
		// create
		$newConversation = [
			'role' => 'user',
			'content' => $newPrompt
		];

		// add old conversation - or start new 
		if ( empty($conversationHistory) ) {
			$this->conversationHistory[] = [
				'role' => 'system',
				'content' => self::SYSTEM_CONTENT
			];
		} 

		// append new prompt
		$this->conversationHistory[] = $newConversation;

		return [
			"model" => $llm,
			"stream" => false,
			"options" => [
				"temperature" => $temperature,
			],
			"messages" => $this->conversationHistory
		];
	}
}