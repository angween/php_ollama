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

	public function __construct(
		private ?Ollama $ollama = null,
		private ?Router $router = null,
		private ?Controller $controller = null,
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
		$systemRole = [
			'role' => 'system',
			'content' => CHAT_SYSTEM_DB
		];

		// get table schema
		$schema = $this->getTableSchema();

		// add schema to role
		$systemRole['content'] = str_replace('#SCHEMA#', $schema, $systemRole['content']);

		// add user's prompt to the role
		// $systemRole['content'] = str_replace('#QUESTION', $this->ollama->prompt, $systemRole['content']);
		$systemRole['content'] = addslashes( $systemRole['content'] );

		// get query result from ollama
		$this->getQueryFromOllama(systemRole: $systemRole);

		exit;
		$content = <<<END
"Based on the following SQL Query result, answer the user question with it using natural language.

Question: #question#

Query Result: $resultQuery
"
END;

		return [
			"role" => "system",
			"content" => $content,
		];
	}


	private function getQueryFromOllama(
		array $systemRole
	){
		// message to Ollama if the query return error result
		$errorResult = '';

		/* 
				$conversationGenerate = [
					"model" => $this->ollama->llm,
					"prompt" => $systemRole['content'],
					"stream" => false,
					"temperature" => 0
				];
			*/

		/* Questions example:
				  what are our tables name
				  what are our best seller product
			  */


		for ($i = 0; $i < self::MAX_LOOP; $i++) {
			if ( $errorResult != '' ) {
				$conversationChat['messages'][] = [
					"role" => "system",
					"content" => "Please generate another SQL Query because the last query return this error message: " . addslashes($errorResult)
				];


				echo "Coba perbaiki query yg salah:\n\n";

				$errorResult = '';
			} else {
				// conversation based on the URL type
				$conversationChat = [
					"model" => $this->ollama->llm,
					"stream" => false,
					"options" => [
						"temperature" => 0
					],
					"messages" => [
						[
							"role" => "system",
							"content" => $systemRole['content']
						],
						[
							"role" => "user",
							"content" => $this->ollama->prompt
						]
					]
				];
			}

			print_r($conversationChat);


			// send to Ollama
			$response = $this->ollama->getResponOllama(
				url: $this->ollama::URL_CHAT,
				chatData: $conversationChat
			);

			// get only the SQL Query from the response
			$sqlQuery = $this->extractSQLquery(responseOllama: $response);


			// added the message history
			$conversationChat['messages'][] = [
				'role' => 'assistant',
				'content' => addslashes( $sqlQuery )
			];

			// run the query
			$resultQuery = $this->db->runQuery(query: $sqlQuery);

			if ( $resultQuery['status'] == 'error') {
				$errorResult = $resultQuery['message'];
			} else {
				return $resultQuery['data'];
			}
		}
	}


	private function extractSQLquery(
		array $responseOllama
	){
		echo "Respon Ollama:\n";
		print_r( $responseOllama);
		echo "\n\n";

		$lines = explode("\n", $responseOllama['content']);
		$sqlQueryLines = [];

		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			if (strpos($trimmedLine, 'SELECT') === 0 || strpos($trimmedLine, 'FROM') === 0 || strpos($trimmedLine, 'JOIN') === 0 || strpos($trimmedLine, 'GROUP BY') === 0 || strpos($trimmedLine, 'ORDER BY') === 0) {
				$sqlQueryLines[] = $trimmedLine;
			}
		}

		// Combine the SQL query lines into a single string
		$sqlQuery = implode("\n", $sqlQueryLines);

		return $sqlQuery;
	}

	private function runQueryFromOllama(): string
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
	}


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


	private function validateQuery(string $query): bool
	{

		return true;
	}


	private function getTableSchema(): string
	{
		// find the schema file
		$filename = self::SCHEMA_PATH . DB_NAME . ".json";


		// return the schemas
		// if (file_exists($filename)) {
		// 	$contentFile = file_get_contents($filename);

		// 	return $contentFile;
		// }


		// or create new schema from database
		$queries = [
			'table_names' => "SELECT table_name FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'",

			'table_names_and_column' => "SELECT table_name, column_name, data_type, column_key, 
				is_nullable /* , column_default */ FROM information_schema.columns 
				WHERE table_schema = '" . DB_NAME . "' ORDER BY table_name, ordinal_position",

			'primary_keys' => "SELECT table_name, column_name 
				FROM information_schema.key_column_usage 
				WHERE table_schema = '" . DB_NAME . "' AND constraint_name = 'PRIMARY'",

			'foreign_keys' => "SELECT table_name, column_name, referenced_table_name, referenced_column_name
				FROM information_schema.key_column_usage
				WHERE table_schema = '" . DB_NAME . "'
				AND referenced_table_name IS NOT NULL",

			'indexes' => "SELECT table_name, index_name, column_name, non_unique
				FROM information_schema.statistics
				WHERE table_schema = '" . DB_NAME . "'
				ORDER BY table_name, index_name, seq_in_index",
		];

		
		foreach ($queries as $key => $query) {
			$result[$key] = $this->executeQueryToJson(query: $query, key: $key);
		}
		
		if ($result) {
			// format as JSON
			$jsonResult = json_encode($result);

			// save to JSON file
			file_put_contents($filename, $jsonResult);

			return $jsonResult;
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

		// To make it smaller for AI token count, we have to short out the output
		if ( $key == 'table_names') {
			$response = array_column( $result['data'], 'table_name');
		}

		else {
			$response = [];

			// Grouping data by table_name
			foreach ($result['data'] as $row) {
				$tableName = $row['table_name'];

				if (!isset($response[$tableName])) {
					$response[$tableName] = [];
				}

				$entry = [];
				foreach ($row as $key => $value) {
					if ($key !== 'table_name') {
						$entry[$key] = $value;
					}
				}

				$response[$tableName][] = $entry;
			}
		}

		// elseif ( $key == 'primary_keys') {}
		// elseif ( $key == 'foreign_keys') {}
		// elseif ( $key == 'indexes') {}

		return $response;
	}
}