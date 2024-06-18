<?php
namespace Ollama;

use App\{
	Database
};

defined('APP_NAME') or exit('No direct script access allowed');

class OllamaDB extends Ollama {
	private const MAX_LOOP = 3;

	public function __construct(
		private ?Database $db = null
	){
		$this->db = $db ?? $db = Database::getInstance(); 
	}

	public function promptDB(
		string $url,
		Ollama $ollama
	) :array {
		// get table schema

		exit;
	}


	private function runQueryFromOllama()
	{
		// Initialize the response array
		$response = [
			'status' => 'failed',
			'result' => null,
			'message' => ''
		];

		// Try to find a valid SQL Query
		for ($i = 0; $i < self::MAX_LOOP; $i++) {
			$query = $this->generateQueryFromOllama();

			if ( ! $query = $this->validateQuery(query: $query) ) {
				continue;
			}

		}

		// If no valid number is found
		$response['message'] = 'Failed to generate a valid random number';
		return $response;
	}


	private function generateQueryFromOllama() :string {
		$query = "";

		return $query;
	}


	private function validateQuery(string $query) :bool {

		return true;
	}
}