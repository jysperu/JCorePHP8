<?php

if ( ! function_exists('array_search2'))
{
	function array_search2($array, $filter_val, $filter_field = NULL, $return_field = NULL)
	{
		$obj = [];

		if (is_null($filter_field))
		{
			$obj = array_search($filter_val, $array);
		}
		else
		{
			foreach($array as $arr)
			{
				if ($arr[$filter_field] == $filter_val)
				{
					$obj = $arr;
				}
			}
		}

		if (is_null($return_field))
			return $obj;

		isset ($obj[$return_field]) or $obj[$return_field] = NULL;
		return $obj[$return_field];
	}
}
