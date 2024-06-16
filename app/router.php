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
			'message' => $router->errorMessage
		]
	);
}

class Router {
	public function __construct(
		private ?array $post = null,
		private ?array $path = null,
		public ?string $errorMessage = null,
		public ?Controller $controller = null,
	) {
		$this->post = $this->post ?? $_POST;
		$this->controller = $this->controller ?? new Controller();
	}


	public function getPath()
	{
		$path = $this->post['path'] ?? null;

		if ( ! $path ) return null;

		$this->path = explode('/', $path);

		$controllerName = ucfirst($this->path[0]);
		$controllerMethod = $this->path[1] ?? null;

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