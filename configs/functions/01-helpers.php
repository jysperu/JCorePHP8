<?php

//////////////////////////////////////////////////////////////////////
///  Manejando Clases Sin Errores                                  ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('new_class'))
{
	function new_class (string $class, array $params_for_constructor = [])
	{
		if ( ! class_exists($class) or empty($class))
			return null;

		if (count($params_for_constructor) === 0)
			return new $class(); ## no se envió parámetros se asume que no se requiere

		try
		{
			$reflection = new ReflectionClass($class);
			$instance   = $reflection -> newInstanceArgs($params_for_constructor);

			return $instance;
		}
		catch(\Exception $e)
		{
			if ( ! preg_match('/does not have a constructor/i', $e -> getMessage()))
			{ ## La excepción es diferente a "Class {Clase Llamada} does not have a constructor, so you cannot pass any constructor arguments"
				throw $e;
			}

			$instance = new $class();
			return $instance;
		}
	}
}

if ( ! function_exists('cached_class'))
{
	function cached_class (string $class, array $params_for_constructor = [])
	{
		static $instances = [];

		$key = md5(json_encode([$class, $params_for_constructor]));

		isset($instances[$key]) or
		$instances[$key] = new_class($class, $params_for_constructor);

		return $instances[$key];
	}
}

if ( ! function_exists('search_class_for'))
{
	function search_class_for (string $namespace, string $class_or_uri, string $default_function = 'index')
	{
		$namespace    = ucfirst($namespace);
		$class_or_uri = str_replace([BS, '/'], [DS, DS], $class_or_uri);
		$class_or_uri = reduce_multiples($class_or_uri, DS); 
		$class_or_uri = trim($class_or_uri, DS);

		if (empty($class_or_uri))
			return null;

		$parts = explode(DS, $class_or_uri);
		$parts = array_map(function($part){
			$part = trim($part);                                ## Limpiar espacios a los lados
			$part = ucfirst($part);                             ## Primera letra siempre mayuscula para las clases
			$part = preg_replace('/[^a-zA-Z0-9]/', '_', $part); ## Convertir todos los caracteres extraños incluidos tildes a guion bajo (_)
			$part = reduce_multiples($part, '_');               ## quitar guion bajo duplicado que se hayan generado
			$part = trim($part, '_');                           ## Limpiar posibles guion bajo a los lados

			return $part;
		}, $parts);
		$parts = array_filter($parts, function($part){
			return ! empty($part);
		});
		$parts = array_values($parts);

		$alt_parts = array_map(function($part){
			$part = explode('_', $part);
			$part = array_map('ucfirst', $part);
			$part = implode('', $part);

			return $part;
		}, $parts); ## Considerando la clase sin guion bajo, letras capitalizadas previo a donde se encontraban el guion bajo

		if (count($parts) === 0)
			return null;

		$is_default_function = true;
		$function = $default_function;
		$params   = [];

		do
		{
			$class = $parts;
			array_unshift($class, $namespace);
			$class = implode(BS, $class);

			if (class_exists($class))
				return [$class, $function, $params];

			$class = $alt_parts;
			array_unshift($class, $namespace);
			$class = implode(BS, $class);

			if (class_exists($class))
				return [$class, $function, $params];



			$temp_parts_orig = $parts;

			//=== Check if the first part and the last part has some extension
			$temp_parts = $temp_parts_orig;

			$first = array_shift($temp_parts);
			$first = explode('_', $first);
			if (1 < count($first))
				array_pop($first);
			$first = implode('_', $first);
			array_unshift($temp_parts, $first);

			$last = array_pop($temp_parts);
			$last = explode('_', $last);
			if (1 < count($last))
				array_pop($last);
			$last = implode('_', $last);
			$temp_parts[] = $last;

			array_unshift($temp_parts, $namespace);
			$class = implode(BS, $temp_parts);

			if (class_exists($class))
				return [$class, $function, $params];

			//=== Check if the last part has some extension
			$temp_parts = $temp_parts_orig;
			$last = array_pop($temp_parts);
			$last = explode('_', $last);
			if (1 < count($last))
				array_pop($last);
			$last = implode('_', $last);
			$temp_parts[] = $last;
			array_unshift($temp_parts, $namespace);
			$class = implode(BS, $temp_parts);

			if (class_exists($class))
				return [$class, $function, $params];

			//=== Check if the first part has some extension
			$temp_parts = $temp_parts_orig;
			$first = array_shift($temp_parts);
			$first = explode('_', $first);
			if (1 < count($first))
				array_pop($first);
			$first = implode('_', $first);
			array_unshift($temp_parts, $first);
			array_unshift($temp_parts, $namespace);
			$class = implode(BS, $temp_parts);

			if (class_exists($class))
				return [$class, $function, $params];



			//=== No se encontró clases
			$last = array_pop($parts);

			if ( ! $is_default_function)
				array_unshift($params, $function);

			$function = lcfirst($last); ## toda función iniciará con minúscula
			$is_default_function = false;
		}
		while(count($parts) > 0);

		return null;
	}
}

//////////////////////////////////////////////////////////////////////
///  Validadores de contenido                                      ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('is_empty'))
{
	/**
	 * is_empty()
	 * Validar si $valor está vacío
	 *
	 * Si es ARRAY entonces valida que tenga algún elemento
	 * Si es BOOL entonces retorna FALSO ya que es un valor así sea FALSO
	 * 
	 * @param array|bool|string|null $v
	 * @return bool
	 */
	function is_empty($v):bool
	{
		$type = gettype($v);

		if ($type === 'NULL')
			return TRUE;

		if ($type === 'string')
		{
			if ($v === '0')
				return FALSE;

			return empty($v);
		}

		if ($type === 'array')
			return count($v) === 0;

		return FALSE;
	}
}

if ( ! function_exists('def_empty'))
{
	/**
	 * def_empty()
	 * Obtener un valor por defecto en caso se detecte que el primer valor se encuentra vacío
	 *
	 * @param mixed
	 * @param mixed
	 * @return mixed
	 */
	function def_empty($valor, ...$valores)
	{
		array_unshift($valores, $valor);

		foreach($valores as $valor)
		{
			is_callable($valor) and 
			$valor = $valor ();

			if ( ! is_empty($valor))
				return $valor;
		}

		return null;
	}
}

if ( ! function_exists('non_empty'))
{
	/**
	 * non_empty()
	 * Ejecutar una función si detecta que el valor no está vacío
	 *
	 * @param mixed
	 * @param callable
	 * @return mixed
	 */
	function non_empty($v, callable $callback, $def_empty = null)
	{
		if ( ! is_empty($v))
			return $callback($v);

		return def_empty ($v, $def_empty);
	}
}

//////////////////////////////////////////////////////////////////////
///  Manejando el buffer temporal                                  ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('_o'))
{
	/**
	 * _o()
	 * Obtiene el ob_content de una función
	 *
	 * @param callable
	 * @return string
	 */
	function _o (callable ...$callbacks)
	{
		ob_start();

		foreach($callbacks as $callback)
			call_user_func($callback);

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}
}

//////////////////////////////////////////////////////////////////////
///  Otros                                                         ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('with'))
{
	/**
	 * with()
	 */
	function with(...$params)
	{
		
		$args   = [];
		$result = null;

		foreach($params as $param)
		{
			if (is_callable($param))
			{
				$result = call_user_func_array($param, $args);

				is_null($result) or 
				$args = (array)$result;

				continue;
			}

			$args[] = $param;
		}

		return $result; //retorna el último result
	}
}

if ( ! function_exists('html_esc'))
{
	/**
	 * html_esc
	 */
	function html_esc($str){
		return htmlspecialchars($str);
	}
}

if ( ! function_exists('compare'))
{
	/**
	 * compare
	 */
	function compare($str, $txt, $success = 'selected="selected"', $echo = TRUE)
	{
		$equal = $str == $txt;

		if ($equal)
		{
			is_callable($success) and
			$success = $success($str, $txt, $echo);

			$success = (string) $success;

			if ($echo)
			{
				echo  $success;
				return TRUE;
			}

			return $success;
		}

		if ($echo)
			return FALSE;

		return '';
	}
}

if ( ! function_exists('remove_invisible_characters'))
{
	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function remove_invisible_characters($str, $url_encoded = TRUE)
	{
		return XONK :: cleanInvisibleCharacter ($str, $url_encoded);
	}
}

//////////////////////////////////////////////////////////////////////
///  Estructura HTML y Snippets del RESPONSE                       ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('use_structure'))
{
	function use_structure (string $clase, array $options = [])
	{
		$instance = new_class('Structure' . BS . $clase, [$options]); ## Just one parameter for constructor
		is_null($instance) or APP :: setResponseHtmlStructure($instance);
		return $instance;
	}
}

if ( ! function_exists('use_theme'))
{
	function use_theme (string $clase, array $options = [])
	{
		return use_structure($clase, $options);
	}
}

if ( ! function_exists('use_snippet'))
{
	function use_snippet (string $snippet, array $local_variables = [], bool $return_content = true)
	{
		$directory = dirname($snippet);
		$filename  = basename($snippet, '.php') . '.php';

		$directory === '.' and $directory = DS;

		if ( ! empty($directory))
		{
			$directory = strtr($directory, '/' . BS, DS . DS);
			$directory = DS . ltrim($directory, DS);
		}

		$file = $directory . DS . $filename;
		if ( ! file_exists(APPPATH . DS . 'snippets' . $file))
		{
			trigger_error('Snippet `' . $snippet . '` no encontrado', E_USER_WARNING);
			return NULL;
		}

		if ( ! $return_content)
			return APPPATH . DS . 'snippets' . $file;

		ob_start();
		extract($local_variables, EXTR_REFS);

		try
		{
			include APPPATH . DS . 'snippets' . $file;
		}
		catch (Exception $e)
		{
			(new MetaException\Snippet($e, $snippet, $file))
			-> logger(); ## No Throw
		}

		$content = ob_get_contents();
		ob_end_clean();
	}
}

if ( ! function_exists('snippet'))
{
	function snippet (string $snippet, array $local_variables = [], bool $return_content = true)
	{
		return use_snippet ($snippet, $local_variables, $return_content);
	}
}

//////////////////////////////////////////////////////////////////////
///  Obtener la clase para un Objeto                               ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('use_obj'))
{
	function use_obj (string $clase, array $ids = [])
	{
		return new_class('Objeto' . BS . $clase, $ids);
	}
}

if ( ! function_exists('obj'))
{
	function obj (string $clase, array $ids = [])
	{
		return use_obj($clase, $ids);
	}
}

//////////////////////////////////////////////////////////////////////
///  Funciones relacionados a los assets                           ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('add_css'))
{
	function add_css ($codigo, $uri = NULL, $options = [])
	{
		return APP :: addCSS($codigo, $uri, $options);
	}
}

if ( ! function_exists('add_js'))
{
	function add_js ($codigo, $uri = NULL, $options = [])
	{
		return APP :: addCSS($codigo, $uri, $options);
	}
}

if ( ! function_exists('add_inline_css'))
{
	function add_inline_css (string $content, string $position = 'body', int $orden = 80)
	{
		return APP :: addInlineCSS($content, $position, $orden);
	}
}

if ( ! function_exists('add_inline_js'))
{
	function add_inline_js (string $content, int $orden = 80, string $position = 'body')
	{
		return APP :: addInlineJS($content, $position, $orden);
	}
}

use MatthiasMullie\Minify\JS  as MinifyJS;
use MatthiasMullie\Minify\CSS as MinifyCSS;
use Driver\Cache\Principal as CacheDriver;

if ( ! function_exists('add_compiled_css'))
{
	function add_compiled_css (array $args = [], string ...$files):bool
	{
		if (count($files) === 0)
			return false;

		$md5 = md5(json_encode([$args, $files]));
		$public_file = '/cached/css/' . $md5 . '.css';

		if (file_exists(HOMEPATH . $public_file))
			return add_css($md5, $public_file, ['version' => filemtime(HOMEPATH . $public_file)]);

		$files_php = array_filter($files, function($o){
			return preg_match('/\.php$/', $o);
		});

		$files_php = array_combine($files_php, array_map(function($o) use (&$args){
			ob_start();
			$content_returned = include $o;
			$content = ob_get_contents() . (is_string($content_returned) ? $content_returned : '');
			ob_end_clean();

			return $content;
		}, $files_php));

		$minifier = new MinifyCSS();
		foreach ($files as $file)
		{
			if (isset($files_php[$file]))
				$minifier -> add($files_php[$file]); ## Agregando el contenido devuelto por el archivo PHP
			else
				$minifier -> addFile($file); ## Agregando el archivo (obviamente debe ser un archivo con extensión CSS)
		}

		$compiled = $minifier -> minify();

		$public_path = dirname(HOMEPATH . $public_file);

		if ( ! file_exists($public_path))
			Helper\Directories :: mkdir ($public_path, HOMEPATH);

		file_put_contents($public_file, $compiled);

		return add_css($md5, $public_file, ['version' => filemtime(HOMEPATH . $public_file)]);
	}
}

if ( ! function_exists('add_compiled_js'))
{
	function add_compiled_js (array $args = [], string ...$files):bool
	{
		if (count($files) === 0)
			return false;

		$md5 = md5(json_encode([$args, $files]));
		$public_file = '/cached/js/' . $md5 . '.js';

		if (file_exists(HOMEPATH . $public_file))
			return add_js($md5, $public_file, ['version' => filemtime(HOMEPATH . $public_file)]);

		$item -> expiresAfter(3600);

		$files_php = array_filter($files, function($o){
			return preg_match('/\.php$/', $o);
		});

		$files_php = array_combine($files_php, array_map(function($o) use (&$args){
			ob_start();
			$content_returned = include $o;
			$content = ob_get_contents() . (is_string($content_returned) ? $content_returned : '');
			ob_end_clean();

			return $content;
		}, $files_php));

		$minifier = new MinifyCSS();
		foreach ($files as $file)
		{
			if (isset($files_php[$file]))
				$minifier -> add($files_php[$file]); ## Agregando el contenido devuelto por el archivo PHP
			else
				$minifier -> addFile($file); ## Agregando el archivo (obviamente debe ser un archivo con extensión CSS)
		}

		$compiled = $minifier -> minify();

		$public_path = dirname(HOMEPATH . $public_file);

		if ( ! file_exists($public_path))
			Helper\Directories :: mkdir ($public_path, HOMEPATH);

		file_put_contents($public_file, $compiled);

		return add_js($md5, $public_file, ['version' => filemtime(HOMEPATH . $public_file)]);
	}
}
