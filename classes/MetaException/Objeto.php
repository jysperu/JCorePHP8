<?php
namespace MetaException;

use MetaException as Base;
use ObjetoBase;

class Objeto extends Base
{
	public function __construct(string $error, ObjetoBase $objeto, array $metadata = [])
	{
		$metadata['objeto'] = $objeto;
		parent::__construct($error, $metadata);
	}
}