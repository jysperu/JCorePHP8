<?php
/**
 * JCore/Controller/UserAgent.php
 * @filesource
 */

namespace JCore\Controller;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

use function gethostbyaddr;
use function substr;
use function in_array;

/**
 * UserAgent
 *
 * getUa():string
 * setUa(string):void
 * detectRequestUa():string
 */
trait UserAgent
{
	protected static $_ua   = '';

	public static function getUa ():string
	{
		return static :: $_ua;
	}

	public static function setUa (string $ua):void
	{
		static :: $_ua = $ua;
	}

	public static function detectRequestUa ():string
	{
		static $user_agent = ''; # por defecto retornar vacío (NO DETECTADO)

		if ( ! empty($user_agent))
			return $user_agent;

		$keys = ['HTTP_X_USER_AGENT', 'HTTP_USER_AGENT'];

		foreach ($keys as $key)
		{
			if ( ! isset($_SERVER[$key]))
				continue; # No se encontró el valor

			$val = $_SERVER[$key];

			if (empty($val))
				continue; # El valor está vacío

			$user_agent = $val;
			break;
		}

		return $user_agent;
	}
}