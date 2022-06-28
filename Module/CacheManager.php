<?php
/**
 * JCore/Module/CacheManager.php
 * @filesource
 */

namespace JCore\Module;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore\ComponenteTrait;
use JCore as JCoreInstance;

class CacheManager
{
	use ComponenteTrait;

	public function init (JCoreInstance $JCore)
	{
		//== Establecer el directorio de cache por defecto
		$cache_path = APPPATH . $JCore :: $DIR4_CACHE;
		file_exists($cache_path) or mkdir($cache_path, 0777, true);
	}
}