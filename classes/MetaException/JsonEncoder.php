<?php
namespace MetaException;

use MetaException as Base;
use Exception;

class JsonEncoder extends Base
{
	public function __construct(string $err, mixed $data)
	{
		parent::__construct($err, [
			'data' => $data,
		]);
	}
}