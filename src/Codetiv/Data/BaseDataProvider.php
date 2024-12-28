<?php

namespace Codetiv\Data;

use Generator;

final class BaseDataProvider implements DataProvider
{

	public function provide(): Generator
	{
		yield [];
	}
}