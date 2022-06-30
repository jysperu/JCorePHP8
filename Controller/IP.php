<?php
/**
 * JCore/Controller/IP.php
 * @filesource
 */

namespace JCore\Controller;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

use function gethostbyaddr;
use function substr;
use function in_array;

/**
 * IP
 *
 * getIp():string
 * setIp(string):void
 * getRdns():string
 * setRdns(string):void
 * detectRequestIp():string
 */
trait IP
{
	protected static $_ip   = '';
	protected static $_rdns = '';

	public static function getIp ():string
	{
		return static :: $_ip;
	}

	public static function setIp (string $ip):void
	{
		static :: $_ip = $ip;
        static :: setRdns ((empty($ip) or in_array(substr($ip, 0, 8), ['192.168.', '127.0.'])) ? 'localhost' : gethostbyaddr($ip));
	}

	public static function getRdns ():string
	{
		return static :: $_rdns;
	}

	public static function setRdns (string $rdns):void
	{
		static :: $_rdns = $rdns;
	}

	public static function detectRequestIp ():string
	{
		static $ip_address = ''; # por defecto retornar vacío (NO DETECTADO)

		if ( ! empty($ip_address))
			return $ip_address;

		$SERVER_ADDR = $_SERVER['SERVER_ADDR'];
		$keys        = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR', 'SERVER_ADDR'];

		foreach ($keys as $key)
		{
			if ( ! isset($_SERVER[$key]))
				continue; # No se encontró el valor

			$val = $_SERVER[$key];

			if (empty($val))
				continue; # El valor está vacío

			if ( ! filter_var($val, FILTER_VALIDATE_IP))
				continue; # IP no válido (IPV4 o IPV6)

			if ($val === $SERVER_ADDR)
				continue; # IP es del servidor (probablemente proveniente por un proxy interno)

			$ip_address = $val;
			break;
		}

		return $ip_address;
	}
}