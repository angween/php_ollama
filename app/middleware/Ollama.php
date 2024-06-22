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
	public const LLM = OLLAMA_MODEL ?? 'llama3';

	private const LLM_MODELS = OLLAMA_MODEL_LIST ?? [];

	public const LLM_TEMPERATURE = OLLAMA_TEMPERATURE ?? 0.6;

	private const LLM_TOPIC = ['database', 'general'];

	public const URL_GENERATE = OLLAMA_GENERATE ?? 'http://localhost:11434/api/generate';

	public const URL_CHAT = OLLAMA_CHAT ?? 'http://localhost:11434/api/chat';

	public const SYSTEM_CONTENT_GENERAL = CHAT_SYSTEM ?? "You are an helpfull assistant, answer the user's question with the same language given.";

	public const SYSTEM_CONTENT_DATABASE = CHAT_SYSTEM_DB ?? "You are an SQL expert and Data Analyst for a company, based on following table schema bellow, answer the question bellow only in the correct SQL Query format, so the system can run that SQL Query to retrieve to answer.\n\nSchema: #SCHEMA#\n\nQuestion: #QUESTION#";

	private const SESSION_PATH = '../session/';

	public function __construct(
		private ?Controller $controller = null,
		private ?Router $router = null,
		private ?OllamaDB $ollamaDB = null,
		private ?array $response = null,
		private array $conversationHistory = [],
		private array $post = [],
		public string $sessionID = "",
		public string $prompt = "",
		public string $llm = "",
		public string $promptTopic = "",
	) {
		$this->controller = $controller;

		$this->router = $router;
	}

	public function prompt()
	{
		// get all payload
		$this->prompt = $this->getPrompt();

		$this->llm = $this->getLLM();
		
		$this->promptTopic = $this->getTopic();

		// get sessionId
		$sessionID = $this->router->clientPost['sessionId'] ?? null;

		// new prompt
		$newPrompt = [
			'role' => 'user',
			'content' => $this->prompt,
			'created' => time()
		];

		// variable to collect all the new conversation to save
		$conversationToSave = [];


		// is client expectiong SSE?
		if ($this->router->clientAccept == 'text/event-stream') {
			$this->router->startStreaming();
		}


		// send chatData to Ollama
		if ( $this->promptTopic == 'general') {
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
				$conversationHistory = $this->loadConversation(filename: $sessionID);
			}

			// append new prompt
			$conversationHistory[] = $newPrompt;

			// prepare chatData
			$chatData = $this->prepareChatData(
				llm: $this->llm,
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

		else if ($this->promptTopic == 'database' ) {
			$this->ollamaDB = new OllamaDB(
				ollama: $this,
				router: $this->router,
				controller: $this->controller,
			);

			$this->response = $this->ollamaDB->promptDB(url: self::URL_CHAT);

			$conversationHistory[] = $this->response; 

			$chatData = $this->prepareChatData(
				llm: $this->llm,
				conversationHistory: $conversationHistory,
				temperature: self::LLM_TEMPERATURE
			);

			$this->response = null;

			// generate natural language for query
			$this->response = $this->getResponOllama(url: self::URL_CHAT, chatData: $chatData);
		}

		// handle respon
		if (!$this->response) {
			throw new \Exception("AI did not Response, make sure Ollama is running, and the selected model is exists!");
		} else {
			$this->response['status'] = 'success';
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
			'title' => $this->prompt,
			'created' => time()
		];

		// return respon to Front End
		if ($this->router->isStreaming ) {
			$this->router->sendStream(message: json_encode($this->response));

			$this->router->endStreaming();
		} else {
			$this->controller->response(message: $this->response);
		}
	}


	public function loadSessionId() :void
	{
		$sessionID = $this->router->clientPost['sessionID'] ?? null;

		$content = $this->loadConversation(filename: $sessionID);

		$filteredArray = array_filter($content, function($element) {
			return $element['role'] !== 'system';
		});
		
		// Reindex the array to have consecutive numeric keys
		$filteredArray = array_values($filteredArray);

		$result = [
			'status' => 'success',
			'data' => $filteredArray
		];

		$this->controller->response(message: $result);
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

					// Read the first 500 characters from the file
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
		string $filename
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


	public function deleteSession(): void {
		$sessionID = $this->router->clientPost['sessionID'] ?? '_no_file_';

		if ( ! is_string($sessionID) ) {
			$result = [
				'status' => 'failed',
				'message' => 'Invalid Conversation ID!'
			];
			$this->controller->response(message: $result);
		}

		$filename = self::SESSION_PATH . $sessionID . ".txt";
		
		if ( file_exists($filename) && unlink($filename) ) {
			$result = [
				'status' => 'success'
			];
		} else {
			$result = [
				'status' => 'failed',
				'message' => 'File not found!'
			];
		}

		$this->controller->response(message: $result);
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


	public function getResponOllama(string $url, array $chatData)
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

		// is it streaming?
		if ( $this->router->isStreaming ) {
			if ($this->promptTopic == 'general' ) {
				$content = '(Typing...)';

				$message = json_encode([
					'status' => 'working',
					'role' => 'system',
					'content' => $content,
					'created' => time()
				]);

				$this->router->sendStream(message: $message);
			}
		}

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
		preg_match_all($pattern, $input, $matches, PREG_OFFSET_CAPTURE);

		// $matches[0] contains all matched emails, URLs, and numbers with their positions
		$preservedElements = $matches[0];

		// Step 3: Remove all special characters except spaces, preserved elements, and allowed punctuation
		$sanitized = preg_replace($pattern, ' ', $input); // Replace preserved elements with spaces
		$sanitized = preg_replace('/[^a-zA-Z0-9\s!$?]/', '', $sanitized); // Remove special characters except allowed ones

		// Step 4: Insert preserved elements back into the sanitized string at their original positions
		foreach (array_reverse($preservedElements) as $element) {
			$position = $element[1];
			$sanitized = substr_replace($sanitized, $element[0], $position, 0);
		}

		// Step 5: Trim and normalize spaces
		$sanitized = preg_replace('/\s+/', ' ', $sanitized);
		$sanitized = trim($sanitized);

		return $sanitized;
	}


	public function prepareChatData(
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
		$llm = (int) $this->router->clientPost['llm'] ?? -1;

		$llm = self::LLM_MODELS[$llm] ?? self::LLM;

		return $llm;
	}

	private function getTopic() :string 
	{
		$topic = $this->router->clientPost['topic'] ?? null;

		if ( ! in_array( $topic, self::LLM_TOPIC) ) $topic = 'general';

		return $topic;
	}
}