<?php

if ( ! function_exists('is_empty'))
{
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
	function is_empty($v):bool
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

if ( ! function_exists('def_empty'))
{
	/**
	 * def_empty()
	 * Obtener un valor por defecto en caso se detecte que el primer valor se encuentra vacío
	 *
	 * @param mixed
	 * @param mixed
	 * @return mixed
	 */
	function def_empty($valor, ...$valores)
	{
		array_unshift($valores, $valor);

		foreach($valores as $valor)
		{
			is_callable($valor) and 
			$valor = $valor ();

			if ( ! is_empty($valor))
				return $valor;
		}

		return null;
	}
}

if ( ! function_exists('non_empty'))
{
	/**
	 * non_empty()
	 * Ejecutar una función si detecta que el valor no está vacío
	 *
	 * @param mixed
	 * @param callable
	 * @return mixed
	 */
	function non_empty($v, callable $callback, $def_empty = null)
	{
		if ( ! is_empty($v))
			return $callback($v);

		return def_empty ($v, $def_empty);
	}
}

if ( ! function_exists('with'))
{
	/**
	 * with()
	 */
	function with(...$params)
	{
		
		$args   = [];
		$result = null;

		foreach($params as $param)
		{
			if (is_callable($param))
			{
				$result = call_user_func_array($param, $args);

				is_null($result) or 
				$args = (array)$result;

				continue;
			}

			$args[] = $param;
		}

		return $result; //retorna el último result
	}
}

if ( ! function_exists('html_esc'))
{
	/**
	 * html_esc
	 */
	function html_esc($str){
		return htmlspecialchars($str);
	}
}

if ( ! function_exists('compare'))
{
	/**
	 * compare
	 */
	function compare($str, $txt, $success = 'selected="selected"', $echo = TRUE)
	{
		$equal = $str == $txt;

		if ($equal)
		{
			is_callable($success) and
			$success = $success($str, $txt, $echo);

			$success = (string) $success;

			if ($echo)
			{
				echo  $success;
				return TRUE;
			}

			return $success;
		}

		if ($echo)
			return FALSE;

		return '';
	}
}

if ( ! function_exists('remove_invisible_characters'))
{
	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		return XONK :: cleanInvisibleCharacter ($str, $url_encoded);
	}
}

if ( ! function_exists('_o'))
{
	/**
	 * _o()
	 * Obtiene el ob_content de una función
	 *
	 * @param callable
	 * @return string
	 */
	function _o (callable ...$callbacks)
	{
		ob_start();

		foreach($callbacks as $callback)
			call_user_func($callback);

		$html = ob_get_contents();

		ob_end_clean();
		return $html;
	}
}
