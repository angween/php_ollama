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

	private const SYSTEM_CONTENT = <<<END
### Instructions:
Your task is to convert a question into a SQL query, given a MySQL database schema.
Adhere to these rules:
- **Deliberately go through the question and database schema word by word** to appropriately answer the question
- **Use Table Aliases** to prevent ambiguity. For example, `SELECT table1.col1, table2.col1 FROM table1 JOIN table2 ON table1.id = table2.id`.
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
		// $systemRole['content'] = str_replace('#SCHEMA#', $schema, $systemRole['content']);

		// add user's prompt to the role
		// $systemRole['content'] = str_replace('#QUESTION', $this->ollama->prompt, $systemRole['content']);
		// $systemRole['content'] = addslashes( $systemRole['content'] );

		// get query result from ollama
		$resultQuery = $this->getQueryFromOllama(systemRole: $systemRole, schema: $schema);

		print_r($resultQuery);

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
		array $systemRole,
		string $schema
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

				echo "\nMencoba perbaiki query yg salah: $errorResult\n\n";

				$errorResult = '';
			} else {
				// conversation based on the URL type
				$systemContent = self::SYSTEM_CONTENT;
				$systemContent = str_replace('{SCHEMA}', $schema, $systemContent);
				$systemContent = str_replace('{question}', $this->ollama->prompt, $systemContent);

				$conversationChat = [
					"model" => $this->ollama->llm,
					"stream" => false,
					"options" => [
						"temperature" => 0
					],
					"messages" => [
						[
							"role" => "system",
							"content" => $systemContent
						]
					]
				];

				print_r($conversationChat);
			}

			// send to Ollama
			$responseAI = $this->ollama->getResponOllama(
				url: $this->ollama::URL_CHAT,
				chatData: $conversationChat
			);

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

		echo "\n", $sqlQuery, "\n";

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
		$filename = self::SCHEMA_PATH . DB_NAME . ".txt";


		// return the schemas
		if (file_exists($filename)) {
			$contentFile = file_get_contents($filename);

			return $contentFile;
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

		// or create new schema from database
		// $queries = [
		// 	'table_names' => "SELECT table_name FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'",

		// 	'table_names_and_column' => "SELECT table_name, column_name, data_type, column_key, 
		// 		is_nullable /* , column_default */ FROM information_schema.columns 
		// 		WHERE table_schema = '" . DB_NAME . "' ORDER BY table_name, ordinal_position",

		// 	'primary_keys' => "SELECT table_name, column_name 
		// 		FROM information_schema.key_column_usage 
		// 		WHERE table_schema = '" . DB_NAME . "' AND constraint_name = 'PRIMARY'",

		// 	'foreign_keys' => "SELECT table_name, column_name, referenced_table_name, referenced_column_name
		// 		FROM information_schema.key_column_usage
		// 		WHERE table_schema = '" . DB_NAME . "'
		// 		AND referenced_table_name IS NOT NULL",

		// 	'indexes' => "SELECT table_name, index_name, column_name, non_unique
		// 		FROM information_schema.statistics
		// 		WHERE table_schema = '" . DB_NAME . "'
		// 		ORDER BY table_name, index_name, seq_in_index",
		// ];

		
		// foreach ($queries as $key => $query) {
		// 	$result[$key] = $this->executeQueryToJson(query: $query, key: $key);
		// }
		
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