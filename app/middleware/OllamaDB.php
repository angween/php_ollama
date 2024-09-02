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
	private const MAX_LOOP = 3;

	private const SCHEMA_PATH = "schema/";

	private ?Database $db = null;

	private const SYSTEM_CONTENTo = <<<END
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

### Response:
Based on your instructions, here is the SQL query I have generated to answer the question `{question}`:
```sql
END;
	
	private const SYSTEM_CONTENT = <<<END
### Task
Generate a MySQL SQL query to answer [QUESTION]{question}[/QUESTION].
If you think the question is not Database related or you want to stright give the answer, please respon started with '### Result:'.

### Database Schema:
The query will run on a database with the following schema:

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


	public function promptDB(
		string $url,
	): array {
		// get system prompt for database
		$systemRole = CHAT_SYSTEM_DB;

		// get table schema
		$schema = $this->getTableSchema();

		// get query result from ollama
		$resultQuery = $this->getQueryFromOllama(systemRole: $systemRole, schema: $schema);

		// if result is empty
		if (empty($resultQuery)) {
			if ($this->router->isStreaming) {
				$content = "I'm sorry, I cannot find any result, please try again.";

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

		// response result in natural language
		$jsonResult = json_encode($resultQuery);

		$prompt = $this->ollama->prompt;

		$content = <<<END
Based on the following SQL Query result, answer the user question using natural language with the same language the user use.

Question: `$prompt`

Query Result: `$jsonResult`
END;

		$conversationDB[] = [
			"role" => "system",
			"content" => $content,
			"created" => time()
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
	){
		// message to Ollama if the query return error result
		$errorResult = '';
		$progress = '';
		$conversationChat = []; 	// local varible for geting the right query 

		for ($i = 0; $i <= self::MAX_LOOP; $i++) {
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
					"content" => $systemRole
				];

				// if this is a saved conversation - load previous chat
				if ($this->ollama->sessionID == 'new') {
					echo "NEW";
					$content = $initialChat;
				} else {
					echo "NONEW";
					$content = [];

					$content[] = $initialChat;

					foreach ($this->ollama->conversationFull as $value) {
						$content[] = $value;
					}
				}

				// print_r($content);
				// exit;

				// preparing parameter to Ollama
				$conversationChat = [
					"model" => $this->ollama->llm,
					"stream" => false,
					"options" => [
						"temperature" => 0
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

			// print_r($conversationChat);

			// send to Ollama
			$responseAI = $this->ollama->getResponOllama(
				url: $this->ollama::URL_CHAT,
				chatData: $conversationChat
			);

			// report the response to front-end debuging
			$this->debug(content: $responseAI);

			// get only the SQL Query from the response
			$sqlQuery = $this->extractSQLquery(responseOllama: $responseAI['content']);

			// if not SQL Query found
			if ( $sqlQuery == '' ) {
				$errorResult = "Query result not found";

				continue;
			}

			// run the query
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
				if ( count( $resultQuery['data'] ) > 10 ) {
					$respon = array_slice($resultQuery['data'], 0, 10);
				} else {
					$respon = $resultQuery['data'];
				}

				$this->workingQuery = $sqlQuery;

				return $respon;
			}
		}
	}


	private function extractSQLquery(
		string $responseOllama
	) :string {
		$pattern = '/SELECT.*?;/is';

		if (preg_match($pattern, $responseOllama, $matches)) {
			$sqlQuery = $matches[0];
		} else {
			return "";
		}

		$this->debug(content: "Generated Query: " . $sqlQuery);

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