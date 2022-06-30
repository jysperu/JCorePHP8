<?php
/**
 * JCore/Module/CacheManager.php
 * @filesource
 */

namespace JCore\Module;
isset($JCore) or exit(0);
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore\ComponenteTrait;

class CacheManager
{
	use ComponenteTrait;

	public function init ()
	{
		global $JCore;

		//== Establecer el directorio de cache por defecto
		$cache_path = JCA_PATH . $JCore :: $DIR4_CACHE;
		file_exists($cache_path) or mkdir($cache_path, 0777, true);
	}
}