<?php

if ( ! function_exists('csvstr'))
{
	function csvstr(...$params)
	{
		$f = fopen('php://memory', 'r+');
		array_unshift($params, $f);

		if (call_user_func_array('fputcsv', $params) === false)
			return false;

		rewind($f);
		$csv_line = stream_get_contents($f);
		return ltrim($csv_line);
	}
}
