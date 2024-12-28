<?php

namespace Codetiv\Http;

use Closure;
use Codetiv\Data\BaseDataProvider;
use Codetiv\Data\DataProvider;

#[\Attribute]
class Page
{

	private Closure|string|array|null $handler = null;

	public function __construct(
		private readonly string $path = '/',
		private readonly string|DataProvider $provider = BaseDataProvider::class
	)
	{
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getProvider(): string|DataProvider
	{
		return $this->provider;
	}

	public function getHandler(): array|string|Closure|null
	{
		return $this->handler;
	}

	public function setHandler(array|string|Closure $handler): void
	{
		$this->handler = $handler;
	}
}