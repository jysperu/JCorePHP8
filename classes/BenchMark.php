<?php
/*!
 * BenchMark.php
 * @filesource
 */
defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * BenchMark
 */
use BenchMark\Point;

class BenchMark
{
	protected static $_global_point = null;

	protected static $_punto_actual = null;

	protected static $_nivel = 0;
	protected static $_history  = '';

	public static function registerGlobalPoint ():void
	{
		if ( ! is_null(static :: $_global_point))
			return;

		static :: $_global_point = '...generando...';
		static :: $_global_point = static :: newPoint('CORE');
		static :: $_global_point
		-> indicateStartParams(execution_start_time, execution_start_memory);
	}

	public static function newPoint (string $key):Point
	{
		if (is_null(static :: $_global_point))
			static :: registerGlobalPoint ();

		$Point = new Point ($key, static :: $_punto_actual);
		$Point -> start();

		if ( ! is_null(static :: $_punto_actual))
			static :: $_punto_actual -> setPuntoSiguiente ($Point);

		static :: $_punto_actual = $Point;
		static :: $_nivel++;
		static :: $_history .= str_repeat('|-- ', static :: $_nivel) . $Point -> getKey() . PHP_EOL;
		static :: $_history .= str_repeat('|   ', static :: $_nivel) . 'started at ' . date(DATE_ATOM, (int)$Point->getStartTime()) . PHP_EOL;
		static :: $_history .= str_repeat('|   ', static :: $_nivel) . 'width ' . transform_size($Point->getStartMemory()) . ' used' . PHP_EOL;

		return $Point;
	}

	public static function mark (string $key):Point
	{
		return static :: newPoint ($key);
	}

	public static function endPoint (Point $Point = null):void
	{
		if (is_null($Point))
			$Point = static :: $_punto_actual;

		if ( ! is_null($Point))
			$Point -> end ();
	}

	public static function ActualPoint ():Point
	{
		return static :: $_punto_actual;
	}

	public static function endAllPoints ():void
	{
		while( ! is_null(static :: $_punto_actual))
		{
			static :: $_punto_actual
				-> end();
		}
	}

	public static function getHistory ():string
	{
		return static :: $_history;
	}

	public static function _setPuntoAnterior (Point $Point):void
	{
		$_total_time = $Point -> getTotalTime();
		$_total_time = reparar_double($_total_time, $_total_time > 1 ? 6 : 10);

		$_total_memory_neg = false;
		$_total_memory = $Point -> getTotalMemory();
		if ($_total_memory < 0)
		{
			$_total_memory_neg = true;
			$_total_memory *= -1;
		}
		$_total_memory = transform_size($_total_memory);

		static :: $_punto_actual = $Point -> getPuntoAnterior();
		static :: $_history .= str_repeat('|   ', static :: $_nivel) . '![' . $Point -> getKey() . ']' . PHP_EOL;
		static :: $_history .= str_repeat('|   ', static :: $_nivel) . 'ended at ' . date(DATE_ATOM, (int)$Point->getEndTime()) . ' (' . $_total_time . 's)' . PHP_EOL;
		static :: $_history .= str_repeat('|   ', static :: $_nivel) . 'width ' . ($_total_memory_neg ? 'favor ' : '') . 'memory of ' . $_total_memory . ' (' . transform_size($Point->getEndMemory()) . ' used)' . PHP_EOL;
		static :: $_history .= str_repeat('|   ', static :: $_nivel -1) . PHP_EOL;
		static :: $_nivel--;
	}
}