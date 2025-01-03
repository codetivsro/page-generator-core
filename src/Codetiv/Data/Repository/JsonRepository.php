<?php

namespace Codetiv\Data\Repository;

use Codetiv\Data\Exceptions\RepositoryFileNotDefined;

abstract class JsonRepository
{

	protected ?string $file = null;

	protected array $data = [];

	public function __construct()
	{
		if ($this->file === null) {
			throw new RepositoryFileNotDefined();
		}

		$this->data = $this->load();
	}

	public function load()
	{
		if (!file_exists($this->file)) {
			return [];
		}

		$data = file_get_contents($this->file);

		return json_decode($data, false);
	}

	public function all(): array
	{
		return $this->data;
	}

	public function findBy($key, $value)
	{
		foreach ($this->data as $item) {
			if ($item->$key === $value) {
				return $item;
			}
		}

		return null;
	}
}