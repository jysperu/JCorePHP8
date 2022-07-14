<?php
namespace APP;

trait Helper
{
	protected static $_uri;

	public static function setURI ($uri):void
	{
		static :: $_uri = $uri;
	}

	public static function getURI ()
	{
		return static :: $_uri;
	}



	protected static $_uri_inicial;

	public static function getUriInicial ()
	{
		return static :: $_uri_inicial;
	}



	protected static $_IDS;

	public static function setIDS ($ids):void
	{
		static :: $_IDS = $ids;
	}

	public static function getIDS ()
	{
		return static :: $_IDS;
	}



	protected static $_config = [];

	public static function setConfig (string $key, mixed $val, bool $replace = true):int
	{
		$isset = isset(static :: $_config[$key]);

		if ($isset and ! $replace)
			return 0;

		static :: $_config[$key] = $val;

		return $isset ? 2 : 1;
	}

	public static function getConfig (string $key):mixed
	{
		if ( ! isset(static :: $_config[$key]))
			return null;

		return static :: $_config[$key];
	}



	protected static $_timezone;

	public static function setTimezone ($timezone):void
	{
		static :: $_timezone = $timezone;
	}

	public static function getTimezone ()
	{
		return static :: $_timezone;
	}



	protected static $_utc;

	public static function setUTC ($utc):void
	{
		static :: $_utc = $utc;
	}

	public static function getUTC ()
	{
		return static :: $_utc;
	}
}