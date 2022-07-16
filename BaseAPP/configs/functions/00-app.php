<?php

//////////////////////////////////////////////////////////////////////
///  APP                                                           ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('APP'))
{
	/**
	  * APP()
	  */
	function APP ()
	{
		return APP :: instance();
	}
}

//////////////////////////////////////////////////////////////////////
///  Gestionando la Configuración                                  ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('config'))
{
	function config (string $key, string ...$subkeys)
	{
		$return = APP :: getConfig($key);

		foreach ($subkeys as $subkey)
		{
			if ( ! is_array($return) or ! isset($return[$subkey]))
				return null;

			$return = $return[$subkey];
		}

		return $return;
	}
}

//////////////////////////////////////////////////////////////////////
///  REQUEST ejecutado por Comando                                 ///
//////////////////////////////////////////////////////////////////////

defined('ISCOMMAND') or define('ISCOMMAND', (substr(PHP_SAPI, 0, 3) === 'cli' ? 'cli' : defined('STDIN')));

if ( ! function_exists('is_command'))
{
	/**
	 * is_command()
	 * identifica si la solicitud de procedimiento ha sido por comando
	 * @return Boolean False en caso de que la solicitud ha sido por web.
	 */
	function is_command ()
	{
		return ISCOMMAND;
	}
}

if ( ! function_exists('is_cli'))
{
	/**
	 * is_cli()
	 */
	function is_cli ()
	{
		return ISCOMMAND === 'cli';
	}
}

//////////////////////////////////////////////////////////////////////
///  Respuestas de RESPONSE                                        ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('response_success'))
{
	/**
	 * response_success()
	 */
	function response_success (string $message = null, $code = null)
	{
		APP :: response_success ($message, $code);
	}
}

if ( ! function_exists('response_error'))
{
	/**
	 * response_error()
	 */
	function response_error (string $error = null, $code = null)
	{
		APP :: response_error ($error, $code);
	}
}

if ( ! function_exists('response_notice'))
{
	/**
	 * response_notice()
	 */
	function response_notice (string $message, $code = null)
	{
		APP :: response_notice ($message, $code);
	}
}

if ( ! function_exists('response_confirm'))
{
	/**
	 * response_confirm()
	 */
	function response_confirm (string $message, $code = null)
	{
		APP :: response_confirm ($message, $code);
	}
}

//////////////////////////////////////////////////////////////////////
///  Manejo de Cache de RESPONSE                                   ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('response_nocache'))
{
	/**
	 * response_nocache()
	 */
	function response_nocache ()
	{
		APP :: applyResponseHeaderNoCache ();
	}
}

if ( ! function_exists('response_cache'))
{
	/**
	 * response_cache()
	 */
	function response_cache (int $days = 365, string $for = 'private', string $rev = 'no-revalidate')
	{
		APP :: applyResponseHeaderCache ($days, $for, $rev);
	}
}

//////////////////////////////////////////////////////////////////////
///  Mensaje de Resultado para RESPONSE                            ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('print_response_result'))
{
	/**
	 * print_response_result()
	 */
	function print_response_result (bool $return_html = false, bool $clean = true)
	{
		$html = APP :: getResponseResultAsHtml ($clean);

		if ($return_html)
			return $html;

		echo $html;
		return;
	}
}

if ( ! function_exists('process_result_message'))
{
	/**
	 * process_result_message()
	 */
	function process_result_message (bool $echo = true, bool $clean = true)
	{
		return print_response_result( ! $echo, $clean);
	}
}

//////////////////////////////////////////////////////////////////////
///  Funciones según el Tipo de RESPONSE                           ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('exit_ifhtml'))
{
	/**
	 * exit_ifhtml()
	 */
	function exit_ifhtml (int $exit_status = null, bool $include_type_body = true)
	{
		APP::exitIfTypeIsHtml($exit_status, $include_type_body);
	}
}

if ( ! function_exists('exit_ifjson'))
{
	/**
	 * exit_ifjson()
	 */
	function exit_ifjson (int $exit_status = null)
	{
		APP::exitIfTypeIsJson($exit_status);
	}
}

if ( ! function_exists('redirect_ifhtml'))
{
	/**
	 * redirect_ifhtml()
	 */
	function redirect_ifhtml (string $link)
	{
		APP::redirectIfTypeIsHtml($link);
	}
}

if ( ! function_exists('redirect_ifjson'))
{
	/**
	 * redirect_ifjson()
	 */
	function redirect_ifjson (string $link)
	{
		APP::redirectIfTypeIsJson($link);
	}
}

//////////////////////////////////////////////////////////////////////
///  Añadiendo contenido al buffer de salida                       ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('add_json'))
{
	/**
	 * add_json()
	 */
	function add_json ($key, $val = null)
	{
		APP::addResponseContentJSON($key, $val);
	}
}

if ( ! function_exists('add_html'))
{
	/**
	 * add_html()
	 */
	function add_html ($content)
	{
		if (is_array($content))
		{
			foreach ($content as $msg)
			{
				add_html($msg);
			}
			return;
		}

		APP::addResponseContentNOJSON($content);
	}
}

//////////////////////////////////////////////////////////////////////
///  Estructura HTML y Snippets del RESPONSE                       ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('use_structure'))
{
	function use_structure (string $clase, array $option = [])
	{
		return new_class('Structure' . BS . $clase);
	}
}

if ( ! function_exists('use_theme'))
{
	function use_theme (string $clase, array $option = [])
	{
		return use_structure($clase, $option);
	}
}

if ( ! function_exists('snippet'))
{
	function snippet (string $snippet, array $local_variables = [], bool $return_content = true)
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

//////////////////////////////////////////////////////////////////////
///  Reemplazando la URI de salida del RESPONSE (history)          ///
//////////////////////////////////////////////////////////////////////

if ( ! function_exists('force_uri'))
{
	function force_uri (string $uri = null)
	{
		APP :: setResponseHistoryURI($uri);
	}
}
