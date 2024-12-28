<?php

namespace Codetiv\Http\Response;

use Codetiv\Generator;

final class View
{

	private string|array|null $body;

	public function __construct(string $template, array $data)
	{
		$this->body = $this->render($template, $data);
	}

	public function getBody(): string|array|null
	{
		return $this->body;
	}

	private function render(string $template, array $data): false|string
	{
		$renderer = Generator::get(\Codetiv\View\ViewRenderer::class);

		return $renderer->render($template, $data);
	}
}