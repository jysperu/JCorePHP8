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
	 * instance()
	 * @static
	 * @return JCore
	 */
	public static function instance ():APP
	{
		static $_instance;

		if ( ! isset($_instance))
		{
			$_instance = new static ();
			$_instance -> _init();
		}

		return $_instance;
	}

	/**
	 * __construct()
	 * @protected
	 */
	protected function __construct ()
	{
		parent :: __construct ();
	}

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

		//=== 
		
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