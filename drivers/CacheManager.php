<?php
/**
 * APPPATH/drivers/CacheManager.php
 * @filesource
 */
namespace Driver;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * CacheManager
 */
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Helper\Directories;

defined('CACHE_DIR') or define('CACHE_DIR', Directories :: mkdir('cache', ROOTPATH));

class CacheManager extends FilesystemAdapter
{
	public static function for (string $namespace = '@', ? int $lifetime = null, ? string $dir = null)
	{
		is_null($lifetime) and $lifetime = config('cache_lifetime');
		is_null($dir)      and $dir      = config('cache_path');

		if ( ! is_empty($dir) and ! file_exists($dir))
			mkdir($dir, 0777, true);

		return new static ($namespace, $lifetime ?? config('cache_lifetime') ?? 0, $dir ?? CACHE_DIR);
	}
}