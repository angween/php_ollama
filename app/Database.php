<?php
namespace App;

defined('APP_NAME') or exit('No direct script access allowed');

/**
 * // Running a select query
 * $query = "SELECT * FROM your_table WHERE id = :id";
 * $params = ['id' => 1];
 * $result = $db->runQuery($query, $params);
 * print_r($result);
 * 
 * // Running an insert query
 * $query = "INSERT INTO your_table (column1, column2) VALUES (:value1, :value2)";
 * $params = ['value1' => 'some value', 'value2' => 'another value'];
 * $result = $db->runQuery($query, $params);
 * if ($result['status'] == 'success') {
 *     echo "Insert successful. Last Insert ID: " . $db->lastInsertId();
 * } else {
 *     echo "Insert failed: " . $result['message'];
 * }
 */
class Database {
	private static $instance = null;
	private $pdo;
	private $stmt;

	private function __construct(string $dsn = "") {
		if ( ! $dsn ) $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8";

		$options = [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES => false,
		];
 
		try {
			$this->pdo = new \PDO($dsn, DB_USER, DB_PASS, $options);
		} catch (\PDOException $e) {
			throw new \Exception("Database connection failed: " . $e->getMessage());
		}
	}


	// Static method to get the single instance of the class
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	// Function to run a query with sanitized parameters
	public function runQuery($query, $params = []) :array {
		// Sanitize parameters
		$sanitizedParams = array_map([$this, 'sanitize'], $params);

		try {
			$this->stmt = $this->pdo->prepare($query);

			$this->stmt->execute($sanitizedParams);

			$data = $this->stmt->fetchAll();

			$result = [
				'status' => 'success',
				'data' => $data,
				'total' => $this->stmt->rowCount(),
				'message' => ''
			];
		} catch (\PDOException $e) {
			$result = [
				'status' => 'error',
				'data' => [],
				'total' => 0,
				'message' => $e->getMessage()
			];
		}

		return $result;
	}

	// Function to sanitize input
	private function sanitize($input) :string {
		return htmlspecialchars(strip_tags($input));
	}

	// Function to get the last inserted ID
	public function lastInsertId() :bool|string {
		return $this->pdo->lastInsertId();
	}

}