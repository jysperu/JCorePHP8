<?php
namespace MetaException;

use MetaException as Base;
use Exception;

class Snippet extends Base
{
	public function __construct(Exception $ex, string $snippet, string $file)
	{
		parent::__construct($ex -> getMessage(), [
			'snippet' => $snippet,
			'file'    => $file,
		], $ex -> getCode(), $ex);

		$this -> setFile($ex -> getFile());
		$this -> setLine($ex -> getLine());
	}
}