<?php
namespace App;

use App\Controller;

require_once('config.php');

require_once('session.php');

// Run the request
$router = new Router();

if ( ! $router->getPath()?->execute() ) {
	$router->controller->response(
		message: [
			'status' => 'error',
			'message' => $router->errorMessage,
			'sender' => 'Router',
		]
	);
}

class Router {
	public function __construct(
		public ?string $errorMessage = null,
		public ?Controller $controller = null,
		public ?string $clientRequestMethod = null,
		public ?string $clientAccept = null,
		public ?array $clientPost = null,
		public ?array $requestContentType = null,
	) {
		$this->getRequest();
		$this->controller = $this->controller ?? new Controller(router: $this);
	}


	private function getRequest() 
	{
		$this->requestContentType = $this->getContentType();
		$this->clientRequestMethod = $this->cekRequestMethod();
		$this->clientAccept = $_SERVER['HTTP_ACCEPT'] ?? '';
		$this->clientPost = isset($_POST) ? $this->getPost() : [];
	}

	private function cekRequestMethod(): ?string
	{
		if ($_SERVER['REQUEST_METHOD'] === 'GET') return 'GET';
		elseif ($_SERVER['REQUEST_METHOD'] === 'POST') return 'POST';
		elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') return 'PUT';
		elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') return 'DELETE';
		elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') return 'PATCH';
		elseif ($_SERVER['REQUEST_METHOD'] === 'HEAD') return 'HEAD';
		return null;
	}


	private function getContentType() :?array {
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$requestContentType = array_map(
				'trim',
				explode(";", $_SERVER['CONTENT_TYPE'])
			);
		} else {
			return null;
		}

		return $requestContentType;
	}


	private function sanitizeString(string $value = null) :?string 
	{
		$value = trim($value);

		// Replace newline characters (\x0A) with the literal string '\n'
		$value = preg_replace('/\x0A/', '\\n', $value);

		// Replace any non-printable ASCII characters with a space
		$value = preg_replace('/[^\x20-\x7E]/', ' ', $value);

		// Encode special HTML characters to prevent XSS attacks
		$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

		// Limit the length of the input
		$value = substr($value, 0, 255);

		return $value;
	} 


	private function getPost(?string $value = null)
	{
		if (
			$this->cekContentType('application/x-www-form-urlencoded')
			|| $this->cekContentType('multipart/form-data')
		) {
			foreach ($_POST as $key => $value) {
				if (is_array($value)) {
					$path['post'][$key] = $value;

					foreach ($data as $key => $value) {
						$data[$key] = $this->sanitizeString($value);
					}

				} else {
					$value = $this->sanitizeString($value);

					$path['post'][$key] = $value;
				}
			}
		} elseif ($this->cekContentType('application/json')) {
			$body   = file_get_contents('php://input');
			$_body2 = null;

			parse_str($body, $path['post']);

			$_body  = json_decode($body, true);
			$_body2 = parse_str($body, $path['post']) ?? null;

			if (!$_body && $_body2) $path['post'] = $_body2;

			if ($_body && !$_body2) $path['post'] = $_body;
		}

		return $path['post'];
	}


	private function cekContentType(string $cekContent = '')
	{
		if (empty($this->requestContentType) || empty($cekContent)) return false;

		if (in_array($cekContent, $this->requestContentType)) return true;

		return false;
	}


	public function getPath()
	{
		$path = $this->clientPost['path'] ?? null;

		if ( ! $path ) return null;

		$path = explode('/', $path);

		$controllerName = ucfirst($path[0]);
		$controllerMethod = $path[1] ?? null;

		$this->controller->controllerName = $controllerName;
		$this->controller->controllerMethod = $controllerMethod;

		return $this;
	}

	public function execute()
	{
		try {
			$this->controller->run();

			return true;
		}
		
		catch (\Exception $e ) {
			$this->errorMessage = $e->getMessage();

			return null;
		}
	}

	public function response()
	{
		//

		exit;
	}

	public function responseError(string $message)
	{
		//

		exit;
	}
}