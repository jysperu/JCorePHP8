<?php

if ( ! function_exists('html_attrs'))
{
	function html_attrs (array $attrs = [])
	{
		$return = [];

		foreach($attrs as $key => $val)
		{
			if (is_numeric($key))
			{
				$return[(string)$val] = null;
				continue;
			}

			$return[$key] = $val;
		}

		$return = array_map(function($key, $val){
			is_array($val) and $val = implode(' ', $val);
			$val = (string) $val;

			if (is_empty($val))
				return $key;

			return $key . '="' . htmlspecialchars($val) . '"';
		}, array_keys($return), array_values($return));

		$return = implode(' ', $return);
		$return = trim($return);
		empty($return) or $return = ' ' . $return;

		return $return;
	}
}

//////////////////////////////////////////////////////////////////////
///  .                                                             ///
//////////////////////////////////////////////////////////////////////
