<?php

namespace Codetiv;

use Codetiv\Data\DataProvider;
use Codetiv\Data\Exceptions\InvalidDataProviderException;
use Codetiv\Http\Page;
use Codetiv\Http\Response\View;
use Codetiv\Http\RouteLoader;
use Codetiv\View\ViewRenderer;
use Psr\Container\ContainerInterface;

class Generator
{

	private const string PUBLIC_DIR = 'public';

	public static Generator $instance;

	public function __construct(
		public string $basePath,
		private readonly ContainerInterface $container
	)
	{
		$this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		self::$instance = $this;
	}

	public static function get(?string $key = null)
	{
		if ($key) {
			return self::$instance->container->get($key);
		}

		return self::$instance;
	}

	public static function boot(
		string $basePath,
		ContainerInterface $container
	): Generator
	{
		return new self($basePath, $container);
	}

	public function run(array $arguments): void
	{
		$command = $arguments[1] ?? null;

		match ($command) {
			'build' => $this->build(),
			'cleanup' => $this->cleanup(),
			default => $this->help(),
		};
	}

	private function cleanup(): void
	{
		$directory = $this->basePath . self::PUBLIC_DIR;

		$this->deleteDirectory($directory);

		echo "\e[0;30;42m ✓ Cleanup completed \e[0m" . PHP_EOL;
	}

	private function deleteDirectory(string $directory): void
	{
		$files = array_diff(scandir($directory), ['.', '..']);

		foreach ($files as $file) {
			$path = $directory . DIRECTORY_SEPARATOR . $file;

			if (is_dir($path)) {
				$this->deleteDirectory($path);
			} else {
				unlink($path);
			}
		}

		rmdir($directory);
	}

	private function build(): void
	{
		$directory = $this->basePath . self::PUBLIC_DIR;

		if (!file_exists($directory)) {
			mkdir($directory);
		}

		$this->generateHtaccess();

		$this->generateErrorPages();

		$this->generateSite();
	}

	private function generateHtaccess(): void
	{
		$file = $this->basePath . self::PUBLIC_DIR . DIRECTORY_SEPARATOR . '.htaccess';

		$content = <<<HTACCESS
        ErrorDocument 404 /404.html
        ErrorDocument 500 /500.html
        HTACCESS;

		file_put_contents($file, $content);
	}

	private function generateErrorPages(): void
	{
		$errorPages = [
			'404' => 'Not Found',
			'500' => 'Internal Server Error',
		];

		foreach ($errorPages as $code => $message) {
			$content = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>$code $message</title>
            </head>
            <body>
                <h1>$code $message</h1>
            </body>
            </html>
            HTML;

			/** @var ViewRenderer $renderer */
			$renderer = $this->container->get(ViewRenderer::class);

			$template = $renderer->getTemplatePath($code);

			if (file_exists($template)) {
				$content = $renderer->render($code);
			}

			$file = $this->basePath . self::PUBLIC_DIR . DIRECTORY_SEPARATOR . $code . '.html';

			file_put_contents($file, $content);
		}
	}

	private function generateSite(): void
	{
		$routes = (new RouteLoader())->load();

		/** @var Page $route */
		foreach ($routes as $route) {
			$this->generatePage($route);
		}
	}

	private function generatePage(Page $route): void
	{
		$provider = $route->getProvider();

		if (is_string($route->getProvider())) {
			$provider = $this->container->get($provider);
		}

		if (!$provider instanceof DataProvider) {
			throw new InvalidDataProviderException();
		}

		foreach ($provider->provide() as $value) {
			$uri = $this->parseUri($route->getPath(), $value);

			$fileName = $uri === '/' ? '/index.html' : $uri. '/index.html';

			$file = $this->basePath . 'public' . $fileName;

			try {
				$regex = $this->convertPathToRegex($route->getPath());

				$matching = preg_match($regex, $uri, $params);

				$routeParams = $this->resolveParams($params);

				$callable = $this->getCallableFromRoute($route);

				/** @var View $view */
				$view =  $callable(...array_values($routeParams));

				$body = $view->getBody();

				$directory = pathinfo($file, PATHINFO_DIRNAME);

				if (!is_dir($directory)) {
					mkdir($directory, recursive: true);
				}

				file_put_contents($file, $body);

				echo "\e[0;30;42m ✓ Generated \e[0m \e[1;32m$uri\e[0m" . PHP_EOL;
			} catch (\Throwable $e) {
				echo "\e[0;30;41m ✕ Error generating \e[0m $uri\e[0m \e[0;31m" . $e->getMessage() . "\e[0m" . PHP_EOL;
			}
		}
	}

	private function parseUri(string $getPath, mixed $value): array|string
	{
		$uri = $getPath;

		foreach ($value as $key => $val) {
			$uri = str_replace('{' . $key . '}', $val, $uri);
		}

		return $uri;
	}

	private function convertPathToRegex(string $path): string
	{
		$pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);

		return '#^' . $pattern . '$#';
	}

	private function getCallableFromRoute(Page $route): array|string|\Closure|null
	{
		$callable = $route->getHandler();

		if (is_array($callable)) {
			[$controller, $method] = $callable;

			$controller = $this->container->get($controller);

			$callable = fn(...$params) => $controller->$method(...$params);
		}

		return $callable;
	}

	private function resolveParams(array $params): array
	{
		$resolved = [];

		foreach ($params as $key => $value) {
			if (is_string($key)) {
				$resolved[$key] = $value;
			}
		}

		return $resolved;
	}

	private function help(): void
	{
		echo "\e[0;30;43mUsage: php generate [command]\e[0m" . PHP_EOL;
		echo "Available commands:" . PHP_EOL;
		echo "  \e[0;32mbuild\e[0m - Build the site" . PHP_EOL;
		echo "  \e[0;31mcleanup\e[0m - Cleanup the site" . PHP_EOL;
	}
}