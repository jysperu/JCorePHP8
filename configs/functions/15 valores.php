<?php

if ( ! function_exists('mes'))
{
	/**
	 * mes()
	 * Obtener un mes
	 *
	 * @param int
	 * @param string
	 * @return mixed
	 */
	function mes (int $idx, string $mode = 'normal')
	{
		$meses = Helper\Valores :: meses ();

		if ($idx < 1)  $idx = 1;
		elseif ($idx > 12) $idx = 12;

		$idx--;

		$mes = $meses[$idx];

		if ($mode == 'min.')
			$mes = substr($mes, 0, 3) . '.';
		elseif ($mode == 'min')
			$mes = substr($mes, 0, 3);

		return $mes;
	}
}

if ( ! function_exists('dia'))
{
	/**
	 * dia()
	 * Obtener un dia
	 *
	 * @param int
	 * @param string
	 * @return mixed
	 */
	function dia (int $idx, string $mode = 'normal')
	{
		$dias = Helper\Valores :: dias ();

		if ($idx < 1)  $idx = 1;
		elseif ($idx > 7) $idx = 7;

		$idx--;

		$dia = $dias[$idx];

		if ($mode == 'min.')
			$dia = substr($dia, 0, 3) . '.';
		elseif ($mode == 'min')
			$dia = substr($dia, 0, 3);

		return $dia;
	}
}

if ( ! function_exists('vTab'))
{
	/**
	 * vTab()
	 * Obtener los caracteres de Tabulación cuantas veces se requiera
	 *
	 * @param int
	 * @return string
	 */
	function vTab (int $n = 1)
	{
		$chr = Helper\Valores :: tab ();

		if ($n < 0)  $n = 0;

		return str_repeat($chr, $n);
	}
}

if ( ! function_exists('vEnter'))
{
	/**
	 * vEnter()
	 * Obtener los caracteres de Salto de Linea cuantas veces se requiera
	 *
	 * @param int
	 * @return string
	 */
	function vEnter (int $n = 1)
	{
		$chr = Helper\Valores :: enter ();

		if ($n < 0)  $n = 0;

		return str_repeat($chr, $n);
	}
}
