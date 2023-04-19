<?php
/**
 * APPPATH/classes/APP/Cache.php
 * @filesource
 */
namespace APP;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP\Cache
 */
use Driver\CacheManager;

trait Cache
{
	protected static function checkPageCache ()
	{
		$request_hash = static :: getRequestHash();

		$request_cache = CacheManager :: for ('static-pages')
		-> getItem($request_hash);

		if ($request_cache -> isHit())
		{
			$metadata = $request_cache -> get();

			static :: clearBuffer(false);
			static :: ResponseAs($metadata['type'], $metadata['charset'], $metadata['mime']);
			die($metadata['content']);
		}
	}



	public static function cachePage (array $metadata = null, string $request_hash = null)
	{
		is_null($request_hash) and 
		$request_hash = static :: getRequestHash();

		if (is_null($metadata))
		{
			$metadata = [
				'type'    => static :: getResponseType(),
				'charset' => static :: getCharset(),
				'mime'    => static :: getResponseMime(),
				'content' => static :: getResponseContent(),
			];
		}

		$cache_instance = CacheManager :: for ('static-pages');
		$request_cache  = $cache_instance -> getItem($request_hash);
		$request_cache -> set($metadata);

		return $cache_instance -> save($request_cache);
	}
}