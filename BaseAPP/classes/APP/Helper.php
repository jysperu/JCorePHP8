<?php
namespace APP;

use Locale;
use MetaException;

trait Helper
{
	public static function clearBuffer (bool $report_content_if_exists = true):void
	{
		$buffer = '';
		while (ob_get_level())
		{
			$buffer .= ob_get_contents();
			ob_end_clean();
		}

		if ($report_content_if_exists and ! empty($buffer))
			MetaException :: quickInstance ('Contenido de buffer encontrado previo a limpiarlo', [
				'buffer' => $buffer,
			]) -> logger ();
	}



	protected static $_uri = '';

	public static function setURI ($uri):void
	{
		static :: $_uri = $uri;
	}

	public static function getURI ():string
	{
		return static :: $_uri;
	}



	protected static $_uri_inicial = '';

	public static function getUriInicial ():string
	{
		return static :: $_uri_inicial;
	}



	protected static $_IDS = [];

	public static function setIDS ($ids):void
	{
		static :: $_IDS = $ids;
	}

	public static function getIDS ()
	{
		return static :: $_IDS;
	}

	public static function addID ($id):void
	{
		static :: $_IDS[] = $id;
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

	public static function getConfig (string $key = null):mixed
	{
		if (is_null($key))
			return static :: $_config;

		if ( ! isset(static :: $_config[$key]))
			return null;

		return static :: $_config[$key];
	}



	public static $_cookie_lang = 'lang';
	public static $_cookie_lang_time = 60 * 60 * 24 * 7 * 4 * 12 * 10; ## 10 años

	protected static $_lang = 'es_PE';

	public static function setLang (string $lang, bool $set_cookie = true)
	{
		$lang = str_replace('-', '_', $lang);

		static::$_lang = $lang;

		if ( ! ISCOMMAND and $set_cookie)
		{
			setcookie(static::$_cookie_lang ?? 'lang', $lang, time() + (int) static::$_cookie_lang_time, '/');

			if (class_exists('Locale'))
				Locale::setDefault($lang);
		}

		action_apply('APP/Lang', $lang);
	}

	public static function getLang ()
	{
		return static::$_lang;
	}

	public static function getLocale ()
	{
		if (class_exists('Locale'))
			return Locale::getDefault();

		return static::getLang();
	}



	protected static $_charset = 'UTF-8';

	public static function setCharset (string $charset):void
	{
		$charset = mb_strtoupper($charset);

		static::$_charset = $charset;

		ini_set('default_charset', $charset);
		ini_set('php.internal_encoding', $charset);
		mb_substitute_character('none');

		defined('UTF8_ENABLED') or 
		define ('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && $charset === 'UTF-8');

		action_apply('APP/Charset', $charset);
	}

	public static function getCharset ():string
	{
		return static::$_charset;
	}



	protected static $_timezone = 'America/Lima';
	protected static $_utc = '-05:00';

	public static function setTimezone (string $timezone)
	{
		static::$_timezone = $timezone;

		date_default_timezone_set ($timezone);

		action_apply('APP/Response/Timezone', $timezone);

		static::$_utc = $utc = calcular_utc($timezone);

		action_apply('APP/Response/UTC', $utc);
	}

	public static function getTimezone ()
	{
		return static::$_timezone;
	}

	public static function getUTC ()
	{
		return static::$_utc;
	}
}