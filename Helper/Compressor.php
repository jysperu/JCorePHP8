<?php
/**
 * JCore/Helper/Compressor.php
 * @filesource
 */

namespace JCore\Helper;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

class Compressor
{
	/**
	 * html
	 */
	public static function html (string $content)
	{
		$replace = [
			'/\>[^\S ]+/s'		=> '>'  , # strip whitespaces after tags, except space
			'/[^\S ]+\</s'		=> '<'  , # strip whitespaces before tags, except space
			'/(\s)+/s'			=> '\\1', # shorten multiple whitespace sequences
			'/<!--(.|\s)*?-->/'	=> ''   , # Remove HTML comments
		];

		return preg_replace(array_keys($replace), array_values($replace), $content);
	}

	/**
	 * JS
	 */
	public static function JS (string $content, $arr = [])
	{
		$arr = array_merge([
			'cache' => FALSE,
			'cachetime' => NULL, 
			'use_apiminifier' => FALSE,
		], $arr);
		extract($arr);

		if (empty($content))
		{
			return $content;
		}

		if ($cache !== FALSE)
		{
			$app = APP();
			$key = ($cache !== TRUE ? $cache : md5($content)) . '.js';
			$Cache = $app->Cache($app::$_cache_nmsp_paginaassets);
			$CacheItem = $Cache->getItem($key);

			if ($CacheItem->isHit())
			{
				return $CacheItem->get();
			}
		}

		try
		{
			$temp = (new MinifyJS($content))->minify();
			$content = $temp;
		}
		catch (Exception $e)
		{}

		try
		{
			if ($use_apiminifier)
			{
				static $uri = 'https://www.toptal.com/developers/javascript-minifier/raw';

				$temp = file_get_contents($uri, false, stream_context_create([
					'http' => [
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => http_build_query([
							'input' => $content
						])
					],
				]));

				if (preg_match('/^\/\/ Error/i', $temp))
				{
					throw new Exception($temp);
				}

				if ($temp === FALSE or empty($temp))
				{
					throw new Exception('Error: No Content');
				}

				$content = $temp;
			}
		}
		catch (Exception $e)
		{
			trigger_error('Se intentó Minificar el contenido JS: ' . PHP_EOL . PHP_EOL . 
						  $content . PHP_EOL . PHP_EOL . 
						  'Error Obtenido: ' . $e->getMessage(), E_USER_WARNING);
		}

		if (isset($Cache) and isset($CacheItem))
		{
			$CacheItem->set($content);
			$Cache->save($CacheItem);
		}
		return $content;
	}

	/**
	 * CSS
	 */
	public static function CSS (string $content = '', $arr = [])
	{
		$arr = array_merge([
			'cache' => FALSE,
			'cachetime' => NULL, 
			'use_apiminifier' => FALSE,
		], $arr);
		extract($arr);

		if (empty($content))
		{
			return $content;
		}

		if ($cache !== FALSE)
		{
			$app = APP();
			$key = ($cache !== TRUE ? $cache : md5($content)) . '.css';
			$Cache = $app->Cache($app::$_cache_nmsp_paginaassets);
			$CacheItem = $Cache->getItem($key);

			if ($CacheItem->isHit())
			{
				return $CacheItem->get();
			}
		}

		try
		{
			$temp = (new MinifyCSS($content))->minify();
			$content = $temp;
		}
		catch (Exception $e)
		{}

		try
		{
			if ($use_apiminifier)
			{
				static $uri = 'https://cssminifier.com/raw';

				$temp = file_get_contents($uri, false, stream_context_create([
					'http' => [
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => http_build_query([
							'input' => $content
						])
					],
				]));

				if (preg_match('/^\/\/ Error/i', $temp))
				{
					throw new Exception($temp);
				}

				if ($temp === FALSE or empty($temp))
				{
					throw new Exception('Error: No Content');
				}

				$content = $temp;
			}
		}
		catch (Exception $e)
		{
			trigger_error('Se intentó Minificar el contenido CSS: ' . PHP_EOL . PHP_EOL . 
						  $content . PHP_EOL . PHP_EOL . 
						  'Error Obtenido: ' . $e->getMessage(), E_USER_WARNING);
		}

		if (isset($Cache) and isset($CacheItem))
		{
			$CacheItem->set($content);
			$Cache->save($CacheItem);
		}
		return $content;
	}

	/**
	 * JSON
	 */
	public static function json_compressor ($content = '')
	{
		if (empty($content))
			return $content;

		try
		{
			is_array($content) and
			$temp = json_decode($content);

			$content = json_encode($temp);
		}
		catch (Exception $e)
		{
			return $content;
		}

		return $content;
	}
}