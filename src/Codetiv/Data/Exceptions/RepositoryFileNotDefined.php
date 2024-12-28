<?php

namespace Codetiv\Data\Exceptions;

class RepositoryFileNotDefined extends \Exception
{

	public function __construct($message = 'Repository file not defined', $code = 0, \Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}