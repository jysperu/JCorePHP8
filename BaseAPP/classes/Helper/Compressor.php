<?php
/**
 * Helper/Compressor.php
 * @filesource
 */

namespace Helper;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

use MatthiasMullie\Minify\JS  as MinifyJS;
use MatthiasMullie\Minify\CSS as MinifyCSS;
use Driver\Cache\Principal as CacheDriver;
use Exception;

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
	public static function JS (string $content, $options = [])
	{
		$options = array_merge([
			'cache'           => false,
			'cachetime'       => null, 
			'use_apiminifier' => false,
		], $options);
		extract($options);

		if (empty($content))
			return $content;

		if ($cache !== FALSE)
		{
			$key = ($cache !== TRUE ? $cache : md5($content)) . '.js';
			$Cache = CacheDriver :: for(CacheDriver :: FOR_ASSETS_JS);
			$CItem = $Cache -> getItem($key);

			if ($CItem -> isHit())
				return $CItem -> get();
		}

		try
		{
			$temp = (new MinifyJS($content)) -> minify();

			if (empty($temp))
				throw new Exception('Contenido Vacío');

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
					throw new Exception($temp);

				if ($temp === FALSE or empty($temp))
					throw new Exception('Error: No Content');

				$content = $temp;
			}
		}
		catch (Exception $e)
		{
			trigger_error(
				'Se intentó Minificar el contenido JS: ' . PHP_EOL . PHP_EOL . 
				$content . PHP_EOL . PHP_EOL . 
				'Error Obtenido: ' . $e -> getMessage(), E_USER_WARNING);
		}

		if (isset($Cache) and isset($CItem))
		{
			$CItem -> set ($content);
			$Cache -> save($CItem);
		}

		return $content;
	}

	/**
	 * CSS
	 */
	public static function CSS (string $content, $options = [])
	{
		$options = array_merge([
			'cache'           => false,
			'cachetime'       => null, 
			'use_apiminifier' => false,
		], $options);
		extract($options);

		if (empty($content))
			return $content;

		if ($cache !== FALSE)
		{
			$key = ($cache !== TRUE ? $cache : md5($content)) . '.js';
			$Cache = CacheDriver :: for(CacheDriver :: FOR_ASSETS_JS);
			$CItem = $Cache -> getItem($key);

			if ($CItem -> isHit())
				return $CItem -> get();
		}

		try
		{
			$temp = (new MinifyCSS($content)) -> minify();

			if (empty($temp))
				throw new Exception('Contenido Vacío');

			$content = $temp;
		}
		catch (Exception $e)
		{}

		try
		{
			if ($use_apiminifier)
			{
				static $uri = 'https://www.toptal.com/developers/cssminifier/raw';

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
					throw new Exception($temp);

				if ($temp === FALSE or empty($temp))
					throw new Exception('Error: No Content');

				$content = $temp;
			}
		}
		catch (Exception $e)
		{
			trigger_error(
				'Se intentó Minificar el contenido CSS: ' . PHP_EOL . PHP_EOL . 
				$content . PHP_EOL . PHP_EOL . 
				'Error Obtenido: ' . $e->getMessage(), E_USER_WARNING);
		}

		if (isset($Cache) and isset($CItem))
		{
			$CItem -> set ($content);
			$Cache -> save($CItem);
		}

		return $content;
	}

	/**
	 * JSON
	 */
	public static function json ($content)
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