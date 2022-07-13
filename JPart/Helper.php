<?php
namespace JCore\JPart;

trait Helper
{
	public static function isList (array $object, bool $check_continues_index = false)
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

	/**
	 * is_empty()
	 * Validar si $valor está vacío
	 *
	 * Si es ARRAY entonces valida que tenga algún elemento
	 * Si es BOOL entonces retorna FALSO ya que es un valor así sea FALSO
	 * 
	 * @param array|bool|string|null $v
	 * @return bool
	 */
	public static function isEmpty($v):bool
	{
		$type = gettype($v);

		if ($type === 'NULL')
			return TRUE;

		if ($type === 'string')
		{
			if ($v === '0')
				return FALSE;

			return empty($v);
		}

		if ($type === 'array')
			return count($v) === 0;

		return FALSE;
	}
}