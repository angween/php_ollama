<?php
namespace Ollama;

use App\{
	Router,
	Controller,
	Database
};

defined('APP_NAME') or exit('No direct script access allowed');

class OllamaDB
{
	private const MAX_LOOP = 2;

	private const SCHEMA_PATH = "schema/";

	private const MAX_QUERY_RESULT = 50;

	private ?Database $db = null;

	private bool $toolCalled = false;

	private const SYSTEM_CONTENT9 = <<<END
### Instructions:
Your task is to convert a question into a SQL query, given a MySQL database schema.
Adhere to these rules:
- **Deliberately go through the question and database schema word by word** to appropriately answer the question
- **Use Table Aliases** to prevent ambiguity. For example, `SELECT table1.col1, table2.col1 FROM table1 JOIN table2 ON table1.id = table2.id`.
- Take message chat history into account
- When creating a ratio, always cast the numerator as float
- Add limit in Query to 10 rows except if tells differently
- No need to Reasoning

### Input:
Generate a SQL query that answers the question `{question}`.
This query will run on a database whose schema is represented in this string:

{SCHEMA}
END;
	
	private const SYSTEM_CONTENT1 = <<<END
### Task
Buatkan query SQL untuk mencari jawaban di database MySQL dari pertanyaan user: [QUESTION]{question}[/QUESTION]
Jika menurutmu pertanyaan ini tidak ada hubungannya dengan Database atau anda ingin memberikan jawaban langsung, silahkan respon dimulai dengan '### Result:'.

### Database Schema:
The query will run on a database with the following schema:

{SCHEMA}

### Answer:
END;

	private const SYSTEM_CONTENT2 = <<<END
### Task
You are Data Analyst from a corporation and Your job is to answer employee questions. 

You have access to tools: query_the_database. For any question about corporation's database, use the query_the_database tool. For any other question You may only answer questions about Yamaha motorbikes and related matters, you can deny any other question kindly. Don't mention that we are using functions defined.
 
### Database Schema:
This is the database schema for the tool query_the_database:

{SCHEMA}

### Answer:
END;
	private const SYSTEM_CONTENT = <<<END
### Task
Kamu adalah Sales Penjual Motor Yamaha Indonesia dari dealer Tjahaja Baru di kota Padang, Sumatera Barat, Indonesia. Bersikap ramah dan membantu konsumen untuk memilihkan motor merek Yamaha, usahakan topik pembicaraan selalu tentang produk Yamaha terbaru dan hindari produk dan topik selain motor Yamaha. Pastikan nama produk selalu dicantumkan ketika menjelaskan tentang motor. Sampaikan minimal 2 pilihan motor jika konsumen bertanya tentang pilihan. 

You have access to tools: query_the_database. For any question about corporation's database, use the query_the_database tool. For any other question You may only answer questions about Yamaha motorbikes and related matters, you can deny any other question kindly. Don't mention that we are using functions defined.
 
### Database Schema:
This is the database schema for the tool query_the_database:

{SCHEMA}

### Answer:
END;

	public function __construct(
		private ?Ollama $ollama = null,
		private ?Router $router = null,
		private ?Controller $controller = null,
		public ?string $workingQuery = null,
	) {
		$this->ollama = $ollama;
		$this->router = $router;
		$this->controller = $controller;

		$this->db = Database::getInstance();
	}


	public function promptDB(): array {
		// get system prompt for database
		$systemRole = CHAT_SYSTEM_DB;

		// get table schema
		$schema = $this->getTableSchema();

		// get query result from ollama
		$resultQuery = $this->getQueryFromOllama(systemRole: $systemRole, schema: $schema);

		// if result is empty
		if (empty($resultQuery)) {

			if ($this->router->isStreaming) {
				$content = "Maaf saya belum bisa menemukan jawabannya, silahkan dicoba lagi.";

				$message = json_encode([
					'status' => 'success',
					'id' => 'new',
					'role' => 'system',
					'content' => $content,
					'created' => time()
				]);

				$this->router->sendStream(message: $message);

				$this->router->endStreaming();
			} else {
				throw new \Exception("Sorry, cannot find any result!");
			}
		} elseif ( count( $resultQuery ) > $this->ollama::RESULT_MAX_ROWS ) {
			array_splice( $resultQuery, 0, $this->ollama::RESULT_MAX_ROWS);
		}


		// update progress
		$message = json_encode([
			'status' => 'working',
			'role' => 'system',
			'id' => $this->ollama->sessionID,
			'content' => '(Typing...)',
			'created' => time()
		]);

		$this->router->sendStream(message: $message);

		// debug information
		$this->debug(content: 'Result: ' . json_encode( $resultQuery) );


		// if is tool_call
		if ($this->toolCalled) {

			$chatData = $this->ollama->prepareChatData(
				llm: $this->ollama->llm,
				conversationHistory: [
					"role" => "tool",
					"content" => $resultQuery,
				],
				temperature: $this->ollama::LLM_TEMPERATURE
			);

			$response = $this->ollama->getResponOllama(url: $this->ollama::URL_CHAT, chatData: $chatData);

			echo "\nAFTER TOOL:\n";
			print_r($response);
			exit;
		}

		// response result in natural language
		$jsonResult = json_encode($resultQuery);

		$prompt = $this->ollama->prompt;

		$content = <<<END
Berdasar dari SQL Query_Result di bawah ini, jawablah Pertanyaan berikut dengan bahasa Indonesia yang natural dan mudah dimengerti.

Pertanyaan: $prompt

Query_Result: $jsonResult
END;

		$conversationDB[] = [
			"role" => "system",
			"content" => $content,
			"created" => time()
		];

		$conversationDB[] = [
			"role" => "user",
			"content" => $prompt
		];

		$chatData = $this->ollama->prepareChatData(
			llm: $this->ollama->llm,
			conversationHistory: $conversationDB,
			temperature: $this->ollama::LLM_TEMPERATURE
		);

		$response = $this->ollama->getResponOllama(url: $this->ollama::URL_CHAT, chatData: $chatData);

		return $response;
	}


	private function getQueryFromOllama(
		string $systemRole,
		string $schema
	) {
		// message to Ollama if the query return error result
		$errorResult = '';
		$progress = '';
		$conversationChat = []; 	// local varible for geting the right query 

		for ($i = 0; $i < self::MAX_LOOP; $i++) {
			if ( $errorResult != '' ) {
				$progress = "(#".($i+1).": Trying another query...)";

				$conversationChat['messages'][] = [
					"role" => "system",
					"content" => "Please generate another SQL Query because the last query return this error message: " . addslashes($errorResult)
				];

				$this->debug(content: "\nMencoba perbaiki query yg salah: $errorResult");

				$errorResult = '';
			} else {
				/* Disable this if want to use .env SYSTEM_DB */
				$systemRole = self::SYSTEM_CONTENT;
				/* ------------------------------------------ */
				$systemRole = str_replace('{SCHEMA}', $schema, $systemRole);
				$systemRole = str_replace('{question}', $this->ollama->prompt, $systemRole);

				$progress = '(#1: Querying database...)';

				$initialChat = [
					"role" => "system",
					"content" => $systemRole,
				];

				// if this is a saved conversation - load previous chat
				if ($this->ollama->sessionID == 'new') {
					$content = $initialChat;
				} else {
					$content = [];

					$content[] = $initialChat;

					foreach ($this->ollama->conversationFull as $value) {
						$content[] = $value;
					}
				}

				// tambahkan pertanyaan baru
				$content[] = [
					"role" => "user",
					"content" => $this->ollama->prompt
				];

				// preparing parameter to Ollama
				$conversationChat = [
					"model" => $this->ollama->llm,
					"stream" => false,
					"options" => [
						"temperature" => 0.5
					],
					"tools" => [
						[
							"type" => "function",
							"function" => [
								"name" => "query_the_database",
								"description" => "Get needed data from MySQL database. Always remember to:
- **Use Table Aliases** to prevent ambiguity. For example, `SELECT table1.col1, table2.col1 FROM table1 JOIN table2 ON table1.id = table2.id`.
- Take message chat history into account
- When creating a ratio, always cast the numerator as float
- Add limit in Query to 10 rows except if tells differently",
								"parameters" => [
									"type" => "object",
									"properties" => [
										"query" => [
											"type" => "string",
											"description" => "The query to run at database, e.g. SELECT a.* FROM users a LIMIT 10"
										]
									],
									"required" => ["query"]
								]
							]
						]
					],
					"messages" => $content
				];

				$this->debug(content: $systemRole);
				// $this->debug(content: str_replace('{question}', $this->ollama->prompt, self::SYSTEM_CONTENT));
			}

			// report progress if isStreaming
			if ($this->router->isStreaming ) {
				$message = json_encode([
					'status' => 'working',
					'role' => 'system',
					'content' => $progress,
					'created' => time()
				]);

				$this->router->sendStream(message: $message);
			}


			// send to Ollama
			$responseAI = $this->ollama->getResponOllama(
				url: $this->ollama::URL_CHAT,
				chatData: $conversationChat
			);

			// echo "\n\n\nRESPONSE:\n";
			// print_r($responseAI);


			// report the response to front-end debuging
			$this->debug(content: $responseAI);

			// get only the SQL Query from the response
			$sqlQuery = $this->extractSQLquery(
				responseOllama: $responseAI
			);


			// if not SQL Query found
			if ( $sqlQuery == '' ) {
				$errorResult = "Query result not found";

				continue;
			}


			// run the query
			$this->debug(content: "Generated Query: " . $sqlQuery);

			$resultQuery = $this->db->runQuery(query: $sqlQuery);


			// added the message history
			$conversationChat['messages'][] = [
				'role' => 'assistant',
				'content' => $responseAI['content']
			];


			// return respon
			if ( $resultQuery['status'] == 'error') {
				$errorResult = $resultQuery['message'];
			} else {
				if ( count( $resultQuery['data'] ) > self::MAX_QUERY_RESULT) {
					$respon = array_slice($resultQuery['data'], 0, self::MAX_QUERY_RESULT);
				} else {
					$respon = $resultQuery['data'];
				}

				$this->workingQuery = $sqlQuery;

				return $respon;
			}
		}
	}


	private function extractSQLquery(
		array $responseOllama
	) :string {
		if ( isset( $responseOllama['tool_calls'] ) &&
			! empty( $responseOllama['tool_calls'])	
		) {
			$this->toolCalled = true;

			foreach ($responseOllama['tool_calls'] as $tool) {
				if ($tool['function']['name'] === 'query_the_database' &&
				 	isset($tool['function']['arguments']['query'])
				) {
					// print_r($tool['function']['arguments']['query']);
					$stringWithSQL = $tool['function']['arguments']['query'];

					return $stringWithSQL;
				}
			}
		}

		$stringWithSQL = $responseOllama['content'];

		$pattern = '/SELECT.*?;/is';

		if (preg_match($pattern, $stringWithSQL, $matches)) {
			$sqlQuery = $matches[0];
		} else {
			return "";
		}

		return $sqlQuery;
	}

	/* private function runQueryFromOllama(): string
	{
		// Initialize the response array
		$response = [
			'status' => 'failed',
			'result' => null,
			'message' => ''
		];

		$resultQuery = null;

		// Try to find a valid SQL Query
		for ($i = 0; $i < self::MAX_LOOP; $i++) {
			$query = $this->generateQueryFromOllama(lastResult: $resultQuery);

			if (!$query = $this->validateQuery(query: $query)) {
				// query must be started with: 'SELECT ..'
				$resultQuery = 'Can not run the given query, please try another.';

				continue;
			} else {
				// query looks good, try to run it
				$resultQuery = $this->runQuery(query: $query);

				// return result
				return $resultQuery;
			}
		}

		// If no valid query working
		$response = 'Failed to generate a valid query.';

		return $response;
	} */


	private function runQuery(string $query): string
	{
		$result = $this->db->runQuery(query: $query);

		if (is_array($result))
			$result = json_encode($result);

		return $result;
	}


	private function generateQueryFromOllama(?string $lastResult = null): string
	{


		$query = "";

		return $query;
	}


	private function debug($content)
	{
		if ( $this->ollama::DEBUG == false ) {
			return;
		}

		if (! $content) {
			return;
		}

		if ($this->router->isStreaming) {
			$message = json_encode([
				'status' => 'debug',
				'content' => $content
			]);

			$this->router->sendStream(message: $message);
		}

		return true;
	}


	private function getTableSchema(): string
	{
		// find the schema file
		$filename = self::SCHEMA_PATH . DB_NAME . ".txt";


		// return the schemas
		if (file_exists($filename)) {
			$contentFile = file_get_contents($filename);

			return $contentFile;
		}

		// or create new schema from database
		if ($this->router->isStreaming) {
			$message = json_encode([
				'status' => 'working',
				'role' => 'system',
				'content' => '(Looking at schema...)',
				'created' => time()
			]);

			$this->router->sendStream(message: $message);

			// give delay for showing the 'content' message
			sleep(1);
		}

		$result = [];

		$table_names = $this->executeQueryToJson(
			query: "SELECT table_name FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'",
			key: 'table_names'
		);

		if ( count( $table_names ) == 0 ) throw new \Exception("Database doesn't any have table.");

		$table_names = array_column($table_names, 'table_name');

		foreach ($table_names as $table) {
			$_showCreate = $this->executeQueryToJson(
				query: "SHOW CREATE TABLE " . $table,
				key: $table
			);

			$_showCreate = $_showCreate[0]['Create Table'] ?? '';

			$_showCreate = str_replace(array("\r", "\n", "\r\n"), ' ', $_showCreate);

			$result[] = preg_replace('/\s+/', ' ', $_showCreate);
		}

		if ($result) {
			// format as txt
			$saveResult = implode(";\n\n", array_values( $result) );

			// save to file
			file_put_contents($filename, $saveResult);

			return $saveResult;
		}

		// or return nothing
		return '';
	}


	private function executeQueryToJson(string $query, string $key)
	{
		$result = $this->db->runQuery($query);

		if (!isset($result['data']) || $result['status'] != 'success') {
			throw new \Exception("Error generating database schema!");
		}

		$response = $result['data'];

		// To make it smaller for AI token count, we have to short out the output
		// if ( $key == 'table_names') {
		// 	$response = array_column( $result['data'], 'table_name');
		// }

		// else {
		// 	$response = [];

			// Grouping data by table_name
			// foreach ($result['data'] as $row) {
			// 	$tableName = $row['table_name'];

			// 	if (!isset($response[$tableName])) {
			// 		$response[$tableName] = [];
			// 	}

			// 	$entry = [];
			// 	foreach ($row as $key => $value) {
			// 		if ($key !== 'table_name') {
			// 			$entry[$key] = $value;
			// 		}
			// 	}

			// 	$response[$tableName][] = $entry;
			// }
		// }

		// elseif ( $key == 'primary_keys') {}
		// elseif ( $key == 'foreign_keys') {}
		// elseif ( $key == 'indexes') {}

		return $response;
	}
}