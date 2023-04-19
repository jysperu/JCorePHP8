<?php
/*!
 * APPPATH/classes/APP/Config.php
 * @filesource
 */
namespace APP;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP\Config
 * > Este trait solo puede ser utilizado por la clase APP
 * Encargado del funcionamiento de la configuración
 */
trait Config
{
	/** $_config_info */
	protected static $_config_info = [];

	/**
	 * loadConfig()
	 * Lee el archivo `APPPATH/configs/config.php` y obtiene la configuración
	 * @return void
	 */
	protected static function loadConfig ():void
	{
		$file = APPPATH . DS . 'configs' . DS . 'config.php';
		file_exists($file) and static :: $_config_info = require_once $file;
	}


	/**
	 * setConfig
	 * Estable un dato en la configuración
	 * @param	string	$key		
	 * @param	mixed	$val		
	 * @param	bool	$replace	@optional
	 * @return	int
	 */
	public static function setConfig (string $key, mixed $val, bool $replace = true):int
	{
		$isset = isset(static :: $_config_info[$key]);

		if ($isset and ! $replace)
			return 0;

		static :: $_config_info[$key] = $val;

		return $isset ? 2 : 1;
	}

	/**
	 * getConfig
	 * Retorna un dato de la configuración
	 * @param	string	$key		
	 * @return	mixed
	 */
	public static function getConfig (string $key = null):mixed
	{
		if (is_null($key) or $key === 'array')
			return static :: $_config_info;

		if ( ! isset(static :: $_config_info[$key]))
			return null;

		return static :: $_config_info[$key];
	}
}