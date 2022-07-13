<?php

if ( ! function_exists('extracto'))
{
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
	function extracto($str, $lenght = 50, $position = 1, $dots = '&hellip;', $tags_allowed = ''){
		// Strip tags
		$html = trim(strip_tags($str, $tags_allowed));
		$strn = trim(strip_tags($str));
		$inc_tag = FALSE;
		
		if (mb_strlen($html) > mb_strlen($strn))
		{
			$inc_tag = TRUE;
			$o = 0;
			$v = [];
			for($i=0; $i<=mb_strlen($html); $i++)
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
}