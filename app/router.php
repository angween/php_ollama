<?php
namespace App;

use App\Controller;

require_once('config.php');

require_once('session.php');

defined('APP_NAME') or exit('No direct script access allowed');

$router = new Router();

if ( ! $router->path()?->route()?->response() ) {
	$router->responseError(message: 'Error!');
}

class Router {
	public function __construct(
		private ?array $post = null,
		private ?array $path = null,
		private ?string $controllerName = null,
		private ?string $controllerMethod = null,
		private ?string $errorMessage = null,
		private ?Controller $controller = new Controller(),
	) {
		$this->post = $this->post ?? $_POST;
	}


	public function path()
	{
		$path = $this->post['path'] ?? null;

		if ( ! $path ) return null;

		$this->path = explode('/', $path);

		$this->controllerName = ucfirst($this->path[0]);

		$this->controllerMethod = array_shift($this->path[1]) ?? null;

		return $this;

	}

	public function route()
	{
		try {
			$this->controller(
				controllerName: $this->controllerName,
				controllerMethod: $this->controllerMethod,
			); 
		}
		
		catch (\Exception $e ) {
			$this->errorMessage = $e->getMessage();
		}

		return $this;
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