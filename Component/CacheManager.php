<?php
/**
 * JCore/Component/CacheManager.php
 * @filesource
 */

namespace JCore\Component;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

	//=================================================================================//
	//==== VARIABLES ESTÁTICAS — CacheManager                                     =====//
	//=================================================================================//

	/**
	 * $DIR4_SESSION
	 * Directorio donde se alojarán los archivos de cache
	 *
	 * Los $DIR4_X se encontrarán dentro de la carpeta JCA_PATH 
	 * (añadir slash al inicio, omitirlo al final)
	 */
//	public static $DIR4_CACHE   = '/tmpdata/cache';

use JCore;
use JCore\ComponenteTrait;

class CacheManager
{
	use ComponenteTrait;

	public function init ()
	{
		$JCore = JCore :: instance();

		//== Establecer el directorio de cache por defecto
		$cache_path = JCA_PATH . $JCore :: $DIR4_CACHE;
		file_exists($cache_path) or mkdir($cache_path, 0777, true);
	}
}