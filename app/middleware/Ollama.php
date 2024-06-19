<?php
namespace Ollama;

use App\{
	Controller,
	Router
};

use Ollama\OllamaDB;

defined('APP_NAME') or exit('No direct script access allowed');

class Ollama
{
	private const LLM = OLLAMA_MODEL ?? 'llama3';

	private const LLM_MODELS = OLLAMA_MODEL_LIST ?? [];

	private const LLM_TEMPERATURE = OLLAMA_TEMPERATURE ?? 0.6;

	private const LLM_TOPIC = ['database', 'general'];

	private const URL_GENERATE = OLLAMA_GENERATE ?? 'http://localhost:11434/api/generate';

	private const URL_CHAT = OLLAMA_CHAT ?? 'http://localhost:11434/api/chat';

	private const SYSTEM_CONTENT_GENERAL = CHAT_SYSTEM ?? "You are an helpfull assistant, answer the user's question with the same language given.";

	private const SYSTEM_CONTENT_DATABASE = "You are an SQL expert and Data Analyst for a company, based on following table schema bellow, answer the question bellow only in the correct SQL Query format, so the system can run that SQL Query to retrieve to answer.";

	private const SESSION_PATH = '../session/';

	public function __construct(
		private ?Controller $controller = null,
		private ?Router $router = null,
		private ?OllamaDB $ollamaDB = null,
		private ?array $response = null,
		private array $conversationHistory = [],
		private array $post = [],
		private string $sessionID = ""
	) {
		$this->controller = $controller;

		$this->router = $router;

		$this->ollamaDB = $ollamaDB ?? new OllamaDB;
	}

	public function prompt()
	{
		// get all payload
		$prompt = $this->getPrompt();

		$llm = $this->getLLM();
		
		$topic = $this->getTopic();

		// get sessionId
		$sessionID = $this->router->clientPost['sessionId'] ?? null;

		// new prompt
		$newPrompt = [
			'role' => 'user',
			'content' => $prompt,
			'created' => time()
		];

		// variable to collect all the new conversation to save
		$conversationToSave = [];


		// send chatData to Ollama
		if ( $topic == 'general') {
			// load or set new conversation
			if ($sessionID == 'new') {
				$conversationHistory = [
					[
						"role" => "system",
						"content" => self::SYSTEM_CONTENT_GENERAL,
						"created" => time()
					]
				];

				$conversationToSave = $conversationHistory;
			} else {
				$conversationHistory = $this->loadConversation(filename: $sessionID, newPrompt: $newPrompt);
			}

			// append new prompt
			$conversationHistory[] = $newPrompt;

			// prepare chatData
			$chatData = $this->prepareChatData(
				llm: $llm,
				conversationHistory: $conversationHistory,
				temperature: self::LLM_TEMPERATURE
			);

			// get Ollama respon
			$this->response = $this->getResponOllama(url: self::URL_CHAT, chatData: $chatData);

			// debug Simulation Response -- REMOVE
			// $this->response = [
			// 	"role" => "assistant",
			// 	"content" => "It's-a me, Mario! Ahahahaha! Don't worry, I'm on it! That no-good Koopa King is always causing trouble!"
			// ];
		}
		else if ($topic == 'database' ) {
			
			$this->response = $this->ollamaDB->promptDB(url: self::URL_CHAT, ollama: $this);
		}


		// handle respon
		if (!$this->response) {
			throw new \Exception("AI did not Response!");
		} else {
			$this->response['created'] = time();
		}

		// fill up new conversation data
		$conversationToSave[] = $newPrompt;
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


	public function loadSessionHistory() :void 
	{
		$files = scandir(self::SESSION_PATH);

		$data = [];

		foreach ($files as $file) {
			// Check if the file is a .txt file
			if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
				// Extract the date part from the filename (first 12 characters)
				$datePart = substr($file, 0, 10);

				// echo "FILE: $file \n";
				// echo "DATE: $datePart \n";

				// Validate date part format
				if (preg_match('/^\d{10}$/', $datePart)) {
					// Parse the date part
					$year = '20' . substr($datePart, 0, 2);
					$month = substr($datePart, 2, 2);
					$day = substr($datePart, 4, 2);
					$hour = substr($datePart, 6, 2);
					$minute = substr($datePart, 8, 2);

					// Convert the hour to the desired timezone (assuming +7)
					$dateTime = new \DateTime("$year-$month-$day $hour:$minute");
					$dateTime->setTimezone(new \DateTimeZone('Asia/Jakarta')); // +7 timezone

					// Format the date
					// $formattedDate = $dateTime->format('d/m/Y \a\t H:i');
					$timestamp = $dateTime->getTimestamp();

					$filePath = self::SESSION_PATH . $file;

					// Read the first 200 characters from the file
					$fileContent = file_get_contents($filePath, false, null, 0, 500);

					// Extract the second user conversation
					$conversation = '';
					if (preg_match('/"role":"user","content":"([^"]+)"/', $fileContent, $matches)) {
						$conversation = $matches[1];
					}

					// Add the formatted date and file content to the array
					$data[] = [
						'id' => str_replace('.txt', '', $file),
						'created' => $timestamp,
						'title' => $conversation
					];
				}
			}
		}

		$result = [
			'status' => 'success',
			'rows' => $data
		];

		$this->controller->response(message: $result);
	}

	private function loadConversation(
		string $filename,
		array $newPrompt,
	): ?array {
		$filename = self::SESSION_PATH . $filename . ".txt";

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

		$filename = self::SESSION_PATH . $sessionID . "txt";

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

		$filename = self::SESSION_PATH . $sessionID . ".txt";

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
				$LLManswer = $response['response'];
			}

			if (isset($response['message'])) {
				if (is_array($response['message']))
					$LLManswer = $response['message'];
				else
					$LLManswer = [
						'role' => 'assistant',
						'content' => $response['message']
					];
			}

			curl_close($ch);

			return $LLManswer;
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
		return [
			"model" => $llm,
			"stream" => false,
			"options" => [
				"temperature" => $temperature,
			],
			"messages" => $conversationHistory
		];
	}




	/**
	 * Supporting methods bellow
	 */

	private function getPrompt() :string
	{
		$prompt = $this->router->clientPost['prompt'] ?? null;

		if (!$prompt)
			throw new \Exception("Missing a Prompt!");

		// sanitize prompt
		$prompt = $this->sanitizeString(input: $prompt);

		return $prompt;
	}

	private function getLLM() :string 
	{
		$llm = $this->router->clientPost['llm'] ?? null;

		if ( ! in_array( $llm, self::LLM_MODELS) ) $llm = self::LLM;

		return $llm;
	}

	private function getTopic() :string 
	{
		$topic = $this->router->clientPost['topic'] ?? null;

		if ( ! in_array( $topic, self::LLM_TOPIC) ) $topic = 'general';

		return $topic;
	}
}