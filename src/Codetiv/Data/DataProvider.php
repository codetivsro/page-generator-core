<?php

namespace Codetiv\Data;

use Generator;

interface DataProvider
{

	public function provide(): Generator;
}