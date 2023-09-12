<?php
/*!
 * configs/functions/BenchMark.php
 * @filesource
 */
defined('APPPATH') or exit(0); ## Acceso directo no autorizado

use Driver\BenchMark\Point;

/**
 * mark()
 * Función que permite generar un punto de control del BenchMark
 */
if ( ! function_exists('mark'))
{
	function mark (string|Point $key, ? float $time = null, ? int $memory = null): Point
	{
		return BenchMark :: mark ($key, $time, $memory);
	}
}