<?php
/**
 * JCore/Component/ErrorControl.php
 * @filesource
 */

namespace JCore\Component;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore;
use JCore\ComponenteTrait;

/**
 * ErrorControl
 * Controla todo tipo de error y/o exception que se genere en el flujo del sistema
 */
class ErrorControl
{
	use ComponenteTrait;

	public function init ()
	{
		$JCore = JCore :: instance();

		//=== Registrar el control de errores
//		ini_set('display_errors', 0);
//		error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
//		set_error_handler([$this, '_handlerError']);
//		set_exception_handler([$this, '_handlerException']);
	}
}