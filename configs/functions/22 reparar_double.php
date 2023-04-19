<?php

if ( ! function_exists('reparar_double'))
{
	function reparar_double ($valor, int $decimales = 8)
	{
	    if (is_empty($valor))
	        return 0;

		$valor = number_format($valor, $decimales, '.', '');
		$valor = (string) $valor;
		$parts = explode('.', $valor, 3);

		$entero  = (int) array_shift($parts);
		$decimal = array_shift($parts);

		if (is_empty($decimal)) return $entero;
		$decimal = (string) $decimal;

		if (preg_match('/([1-9]+)[0]{5,}[1-9]$/i', $decimal, $matches))
		{
			$decimal = $matches[1];
		}
		elseif (preg_match('/^[9]{5,}[1-9]$/i', $decimal, $matches)) {
			$entero ++;
			$decimal = '0';
		}
		elseif (preg_match('/([0-8]+)[9]{5,}[1-9]$/i', $decimal, $matches)) {
			$decimal = (int)$matches[1];
			$decimal++;
		}

		return $entero . '.' . $decimal;
	}
}