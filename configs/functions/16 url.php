<?php


if ( ! function_exists('request'))
{
	/**
	 * request()
	 * Obtiene los request ($_GET $_POST)
	 *
	 * @param	string	$get
	 * @return	mixed
	 */

	function &request($get = 'array', $default = NULL, $put_default_if_empty = TRUE)
	{
		static $datos = [];

		if (count($datos) === 0)
		{
			$PhpInput = (array)json_decode(file_get_contents('php://input'), true);
			$_POST = array_merge($_POST, [], $PhpInput, $_POST);

			$datos = array_merge(
				$_REQUEST,
				$_POST,
				$_GET
			);

			$path = explode('/', url('path'));
			foreach($path as $_p)
			{
				if (preg_match('/(.+)(:|=)(.*)/i', $_p, $matches))
				{
					$datos[$matches[1]] = $matches[3];
				}
			}
		}

		if ($get === 'array')
		{
			return $datos;
		}

		$get = (array)$get;

		$return = $datos;
		foreach($get as $_get)
		{
			if ( ! isset($return[$_get]))
			{
				$return = $default;
				break;
			}

			if ($put_default_if_empty and ((is_array($return[$_get]) and count($return[$_get]) === 0) or empty($return[$_get])))
			{
				$return = $default;
				break;
			}

			$return = $return[$_get];
		}

		return $return;
	}
}



if ( ! function_exists('build_url'))
{
	/**
	 * build_url()
	 * Construye una URL
	 *
	 * @param	array	$parsed_url	Partes de la URL a construir {@see http://www.php.net/manual/en/function.parse-url.php}
	 * @return	string
	 */

	function build_url($parsed_url)
	{
		isset($parsed_url['query']) and is_array($parsed_url['query']) and 
		$parsed_url['query'] = http_build_query($parsed_url['query']);

		$scheme   = isset($parsed_url['scheme'])  ? $parsed_url['scheme']  : '';
		$host     = isset($parsed_url['host'])    ? $parsed_url['host']    : '';
		$port     = isset($parsed_url['port'])    ? $parsed_url['port']    : '';
		$user     = isset($parsed_url['user'])    ? $parsed_url['user']    : '';
		$pass     = isset($parsed_url['pass'])    ? $parsed_url['pass']    : '';
		$path     = isset($parsed_url['path'])    ? $parsed_url['path']    : '';
		$query    = isset($parsed_url['query'])   ? $parsed_url['query']   : '';
		$fragment = isset($parsed_url['fragment'])? $parsed_url['fragment']: '';

		isset($parsed_url['path_before']) and 
		$path = $parsed_url['path_before'] . $path;

		if (in_array($port, [80, 443]))
		{
			## Son puertos webs que dependen del scheme
			$scheme = $port === 80 ? 'http' : 'https';
			$port   = '';
		}

		empty($scheme)   or $scheme .= '://';
		empty($port)     or $port    = ':' . $port;
		empty($pass)     or $pass    = ':' . $pass;
		empty($query)    or $query   = '?' . $query;
		empty($fragment) or $fragment= '#' . $fragment;

		$pass = ($user || $pass) ? ($pass . '@') : '';

		return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
	}
}

if ( ! function_exists('redirect'))
{
	/**
	 * Establecer una redirección en caso el Tipo sea
	 * @param	string	$link
	 * @return	self
	 */
	function redirect($url, $query = NULL)
	{
		$parsed = is_array($url) ? $url : parse_url($url);

		isset($parsed['scheme']) or $parsed['scheme'] = url('scheme');
		isset($parsed['path'])   or $parsed['path']   = '/';

		if ( ! isset($parsed['host']))
		{
			$parsed['host'] = url('host');
			$parsed['path'] = url('path_before') . '/' . ltrim($parsed['path'], '/');
		}

		if ( ! is_null($query))
		{
			isset($parsed['query'])    or $parsed['query']  = [];
			is_array($parsed['query']) or parse_str($parsed['query'], $parsed['query']);

			$parsed['query'] = array_merge($parsed['query'], $query);
		}

		$url = build_url ($parsed);

		APP :: clearBuffer();      ## limpiar el buffer de salida
		ErrorControl :: silence(); ## no se reportará cualquier error producido automáticamente

		header('Location: ' . $url) or die('<script>location.replace("' . $url . '");</script>');
		exit;
	}
}

if ( ! function_exists('http_code_message'))
{
	/**
	 * http_code_message()
	 * Resuelve el valor por defecto de las respuestas del HTTP status
	 *
	 * @param Integer $code El código
	 * @return string
	 */
	function http_code_message (int $code = 200)
	{
		static $messages = [
			100 => 'Continue',
			101 => 'Switching Protocols',

			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			307 => 'Temporary Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			422 => 'Unprocessable Entity',
			426 => 'Upgrade Required',
			428 => 'Precondition Required',
			429 => 'Too Many Requests',
			431 => 'Request Header Fields Too Large',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			511 => 'Network Authentication Required',
		];
		return isset($messages[$code]) ? $messages[$code] : 'Non Status Text';
	}
}

if ( ! function_exists('http_code'))
{
	/**
	 * http_code()
	 * Establece la cabecera del status HTTP
	 *
	 * @param Integer $code El código
	 * @param String $message El texto del estado
	 * @return void
	 */
	function http_code ($code = 200, $message = '')
	{
		static $server_protocol_alloweds = [
			'HTTP/1.0', 
			'HTTP/1.1', 
			'HTTP/2'
		];

		if (defined('STDIN')) return;

		is_int($code) or 
		$code = (int) $code;

		empty($message) and 
		$message = http_code_message($code);

		if (ISCOMMAND)
		{
			@header('Status: ' . $code . ' ' . $message, TRUE);
			return;
		}

		
		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL']) && in_array($_SERVER['SERVER_PROTOCOL'], $server_protocol_alloweds, TRUE)) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
		@header($server_protocol . ' ' . $code . ' ' . $message, TRUE, $code);
		return;
	}
}

if ( ! function_exists('force_exit'))
{
	function force_exit (int $status = null)
	{
		exit ($status);
	}
}
