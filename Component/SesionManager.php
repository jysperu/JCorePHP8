<?php
/**
 * JCore/Component/SesionManager.php
 * @filesource
 */

namespace JCore\Component;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore;
use JCore\ComponenteTrait;

class SesionManager
{
	use ComponenteTrait;

	public function init ()
	{
		$JCore = JCore :: instance();

		//=== Iniciando la sesión
		session_name( $JCore :: $SESSION_NAME );
		$sesion_path = JCA_PATH . $JCore :: $DIR4_SESSION;
		file_exists($sesion_path) or mkdir($sesion_path, 0777, true);
		session_save_path($sesion_path);
		session_start();
	}
}