<?php
namespace App;

defined('APP_NAME') or exit('No direct script access allowed');

class Controller
{
	private const PATH = "\\Ollama\\";

	public function __construct(
		private ?Router $router = null,
		public ?string $controllerName = null,
		public ?string $controllerMethod = null,
	) {
		$this->router = $router ?? new Router();
	}


	public function setHeader(int $statusCode)
    {
        http_response_code($statusCode);
    }

    public function setContentType(string $contentType)
    {
        header("Content-Type: $contentType");
    }

    public function response(string|array $message, int $statusCode = 202)
    {
        $this->setHeader(statusCode: $statusCode);

        if (is_array($message)) {
            $this->setContentType('application/json');
        } else {
            $this->setContentType('text/plain');
        }

        echo is_array($message) ? json_encode($message) : $message;

        exit;
    }


	public function setControllerName(
		string $controllerName
	) {
		$this->controllerName = $controllerName;
	}

	public function setControllerMethod(
		string $controllerMethod
	) {
		$this->controllerMethod = $controllerMethod;
	}

	public function run()
	{
        // Fully qualify the controller class name
        $controllerName = self::PATH . $this->controllerName;

        // Validate and instantiate the controller
        $controller = $this->getControllerInstance($controllerName);

        // Validate the method
        $method = $this->controllerMethod;
        $this->validateControllerMethod($controller, $method);

        // Call the controller method
        $controller->$method();
	}


	private function getControllerInstance(string $controllerName)
	{
		if (!class_exists($controllerName)) {
			throw new \Exception("Application Class '$controllerName' not found!");
		}

		return new $controllerName(controller: $this, router: $this->router);
	}

	private function validateControllerMethod($controller, string $method)
	{
		if (!method_exists($controller, $method)) {
			throw new \Exception("Method '$method' not found in " . get_class($controller));
		}

		if (!is_callable([$controller, $method])) {
			throw new \Exception("Method '$method' cannot be called on " . get_class($controller));
		}
	}
}
