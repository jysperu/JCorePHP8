<?php
/**
 * JCore/Helper.php
 * @filesource
 */

namespace JCore;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

class Helper
{
	/**
	 * isEmpty()
	 * Validar si $valor está vacío
	 *
	 * Si es ARRAY entonces valida que tenga algún elemento
	 * Si es BOOL entonces retorna FALSO ya que es un valor así sea FALSO
	 * 
	 * @param array|bool|string|null $v
	 * @return bool
	 */
	public static function isEmpty ($v):bool
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

	/**
	 * defEmpty()
	 * Obtener un valor por defecto en caso se detecte que el primer valor se encuentra vacío
	 *
	 * @param mixed
	 * @param mixed
	 * @return mixed
	 */
	public static function defEmpty ($valor, ...$valores)
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

	/**
	 * nonEmpty()
	 * Ejecutar una función si detecta que el valor no está vacío
	 *
	 * @param mixed
	 * @param callable
	 * @return mixed
	 */
	public static function nonEmpty ($v, callable $callback, $def_empty = null)
	{
		if ( ! is_empty($v))
			return $callback($v);

		return static :: defEmpty ($v, $def_empty);
	}

	/**
	 * with()
	 */
	public static function with ( ...$params)
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

	/**
	 * htmlEsc
	 */
	public static function htmlEsc($str)
	{
		return htmlspecialchars($str);
	}

	/**
	 * extracto
	 * Retorna un resumen del texto, basado en el tamaño de caracteres indicado pero soportando tags html
	 * y ubica los puntos de separación en donde se desee
	 * @param String $str
	 * @param Integer $lenght
	 * @param Integer|Decimal $position Valor decimal entre el 0 y el 1
	 * @param String $dots Separador del texto
	 * @param String $tags_allowed Tags html soportado, Eg: '<a><p>'
	 * @return String
	 */
	public static function extracto ($str, $lenght = 50, $position = 1, $dots = '&hellip;', $tags_allowed = '')
	{
		// Strip tags
		$html = trim(strip_tags($str, $tags_allowed));
		$strn = trim(strip_tags($str));

		$inc_tag = FALSE;
		if (mb_strlen($html) > mb_strlen($strn))
		{
			$inc_tag = TRUE;
			$o = 0;
			$v = [];

			for($i = 0; $i <= mb_strlen($html); $i++)
			{
				$html_char = mb_substr($html, $i, 1);
				$strn_char = mb_substr($strn, $i, 1);

				if ($html_char == '<')
				{
					$tag = '';
					$c = 0;

					do
					{
						$html_char = mb_substr($html, $i + $c, 1);
						$tag .= $html_char;

						$c++;
					}
					while($html_char <> '>');

					$v[$o] = $tag;
					$i+=$c - 1;
				}
				else
				{
					$o++;
				}
			}
		}

		// Is the string long enough to ellipsize?
		if (mb_strlen($strn) <= $lenght)
		{
			return $html;
		}

		$position = $position > 1 ? 1 : ($position < 0 ? 0 : $position);

		$beg = mb_substr($strn, 0, floor($lenght * $position));
		if ($position === 1)
		{
			$end = mb_substr($strn, 0, -($lenght - mb_strlen($beg)));
		}
		else
		{
			$end = mb_substr($strn, -($lenght - mb_strlen($beg)));
		}

		if ($inc_tag)
		{
			$beg_e = mb_strlen($beg);
			$end_s = mb_strlen($end);
			$spc_l = mb_strlen($strn) - $end_s - $beg_e;
			$end_s = $beg_e + $spc_l;

			$return = '';
			$opened_lvl = 0;
			for($i=0; $i<=mb_strlen($strn); $i++)
			{
				if ($i>=$beg_e and $i<$end_s)
				{
					while($opened_lvl > 0)
					{
						for($ti = $beg_e; $ti <= $end_s; $ti++)
						{
							if (isset($v[$ti]))
							{
								if ($v[$ti][1] == '/')
								{
									$opened_lvl--;
								}
								else
								{
									$opened_lvl++;
								}

								$is_br = preg_match('#<br( )*(/){0,1}>#', $v[$ti]);
								if ($is_br)
								{
									$opened_lvl--;
									continue;
								}

								$return .= $v[$ti];
							}
						}
					}

					$return .= $dots;
					$i += $spc_l - 1;
					continue;
				}

				$char = mb_substr($strn, $i, 1);

				if (isset($v[$i]))
				{
					if ($v[$i][1] == '/')
					{
						$opened_lvl--;
					}
					else
					{
						$opened_lvl++;
					}

					$is_br = preg_match('#<br( )*(/){0,1}>#', $v[$i]);
					if ($is_br)
					{
						$opened_lvl--;
					}

					$return .= $v[$i];
				}

				if ($i < $beg_e or $i >= $end_s)
				{
					$return .= $char;
				}
			}

			return $return;
		}
		else
		{
			return $beg . $dots . $end;
		}
	}

	/**
	 * compare
	 */
	public static function compare ($str, $txt, $success = 'selected="selected"', $echo = TRUE)
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