<?php
/**
 * APPPATH/classes/APP.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP
 */
class APP extends JArray
{
	use IntanceAble;
	use APP\Helper;
	use APP\Response;

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

		//=== Restaurar el buffer de salida a 1
		while (ob_get_level())
			ob_end_clean();

		ob_start();

		//=== load request
		register_shutdown_function('APP::_send_response_on_shutdown');

		//=== comprobar si se requiere re-compilar
		register_shutdown_function('APP::_check_updates_on_shutdown');
	}

	public static function _send_response_on_shutdown ()
	{
		print_array(static :: getResponseType());

		action_apply('do_when_end');
		action_apply('shutdown');
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
}
