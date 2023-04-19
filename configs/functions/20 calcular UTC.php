<?php

if ( ! function_exists('calcular_utc'))
{
	function calcular_utc (string $timezone):string
	{
		$timezone = new DateTimeZone($timezone);
		$datetime = new DateTime('now', $timezone);
		$offset   = $timezone->getOffset($datetime);

		return sprintf( '%s%02d:%02d', $offset >= 0 ? '+' : '-', abs( $offset / 3600 ), abs( $offset % 3600 ) );
	}
}