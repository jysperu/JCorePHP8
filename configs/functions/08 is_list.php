<?php

if ( ! function_exists('is_list'))
{
	function is_list (array $object, bool $check_continues_index = false)
	{
		if (count($object) === 0)
			return null; # Null because there is no items to evaluate keys

		$keys = array_keys($object);
		$keys_non_numerics = array_filter($keys, function($key) {
			return ! preg_match('/^[0-9]+$/', $key); # solo números
		});

		if (count($keys_non_numerics) > 0)
			return false;

		if ($check_continues_index)
		{
			sort($keys);
			$siguiente = (int) $keys[0];

			while(count($keys) > 0)
			{
				$key = (int) array_shift($keys);
				if ($siguiente !== $key)
					return false;

				$siguiente++;
			}
		}

		return true;
	}
}
