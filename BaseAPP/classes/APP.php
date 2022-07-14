<?php
/**
 * APPPATH/classes/APP.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP
 */

use Structure\Base as BaseStructure;

class APP extends JArray
{
	use IntanceAble;

	/**
	 * _init()
	 * @protected
	 */
	protected function _init ()
	{
		//=== Leer archivo de configuración
		$file = APPPATH . DS . 'configs' . DS . 'config.php';
		if (file_exists($file))
			static :: $_config = require_once($file);

		//=== load request
		

		//=== comprobar si se requiere re-compilar
		register_shutdown_function('APP::_check_updates_on_shutdown');
	}

	public static function _check_updates_on_shutdown ()
	{
		try
		{
			if ( ! defined('JCorePATH'))
				return;

			require_once JCorePATH . DS . 'JCore.php';

			if ( ! JCore::requiereCompilar())
				return;

			JCore::compile();
		}
		catch (\BasicException $e){}
		catch (\Exception      $e){}
		catch (\TypeError      $e){}
		catch (\Error          $e){}
	}

	protected static $_config = [];

	function getConfig (string $key):mixed
	{
		if ( ! isset(static :: $_config[$key]))
			return null;

		return static :: $_config[$key];
	}

	function setConfig (string $key, mixed $val, bool $replace = true):int
	{
		$isset = isset(static :: $_config[$key]);

		if ($isset and ! $replace)
			return 0;

		static :: $_config[$key] = $val;

		return $isset ? 2 : 1;
	}

	protected static $_response_html_structure;

	function setResponseHtmlStructure (BaseStructure $instance):void
	{
		static :: $_response_html_structure = $instance;
	}

	function getResponseHtmlStructure ()
	{
		return static :: $_response_html_structure;
	}
}