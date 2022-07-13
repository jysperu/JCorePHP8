<?php

if ( ! function_exists('url'))
{
	/**
	 * url()
	 * Obtiene la estructura y datos importantes de la URL
	 *
	 * @param	string	$get
	 * @return	mixed
	 */
	function &url($get = 'base')
	{
		static $datos = [];

		if (count($datos) === 0)
		{
			$file = __FILE__;

			isset($_SERVER['SERVER_PORT'])    or $_SERVER['SERVER_PORT']    = ISCOMMAND ? 8080 : 80;
			isset($_SERVER['REQUEST_URI'])    or $_SERVER['REQUEST_URI']    = '/';
			isset($_SERVER['HTTP_HOST'])      or $_SERVER['HTTP_HOST']      = (ISCOMMAND ? 'coman' : 'desconoci') .'.do';

			$_SERVER_HTTP_HOST = $_SERVER['HTTP_HOST'];

			//Archivo index que se ha leído originalmente
			$script_name = $_SERVER['SCRIPT_NAME'];

			//Este es la ruta desde el /public_html/{...}/APPPATH/index.php
			// y sirve para identificar si la aplicación se ejecuta en una subcarpeta
			// o desde la raiz, con ello podemos añadir esos subdirectorios {...} en el enlace
			$datos['srvpublic_path'] = '';
			$datos['srvpublic_path'] = APP()->filter_apply('JApi/url/srvpublic_path', $datos['srvpublic_path'], $_SERVER_HTTP_HOST);

			//Devuelve si usa https (boolean)
			$datos['https'] = FALSE;
			if (
				( ! empty($_SERVER['HTTPS'])                  and mb_strtolower($_SERVER['HTTPS']) !== 'off') ||
				(   isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and mb_strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
				( ! empty($_SERVER['HTTP_FRONT_END_HTTPS'])   and mb_strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') ||
				(   isset($_SERVER['REQUEST_SCHEME'])         and $_SERVER['REQUEST_SCHEME'] === 'https')
			)
			{
				$datos['https'] = TRUE;
			}
			isset($_SERVER['REQUEST_SCHEME']) or $_SERVER['REQUEST_SCHEME'] = 'http' . ($datos['https'] ? 's' : '');

			$_parsed = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER_HTTP_HOST . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
			$_parsed = parse_url($_parsed);

			//Devuelve 'http' o 'https' (string)
			$datos['scheme'] = $_parsed['scheme'];

			//Devuelve el host (string)
			$datos['host'] = $_parsed['host'];

			//Devuelve el port (int)
			$datos['port'] = $_parsed['port'];

			isset($_parsed['user']) and $datos['user'] = $_parsed['user'];
			isset($_parsed['pass']) and $datos['pass'] = $_parsed['pass'];

			$datos['path'] = isset($_parsed['path']) ? $_parsed['path'] : '/';
			if (ISCOMMAND)
			{
				global $argv;
				array_shift($argv); // Archivo SCRIPT
				if (count($argv) > 0)
				{
					$datos['path'] = '/' . array_shift($argv);
				}
			}

			empty($datos['srvpublic_path']) or 
			$datos['path'] = str_replace($datos['srvpublic_path'], '', $datos['path']);

			$datos['path'] = preg_replace('#(^|[^:])//+#', '\\1/', $datos['path']); // reduce double slashes
			$datos['path'] = '/' . trim($datos['path'], '/');

			$datos['query'] = isset($_parsed['query']) ? $_parsed['query'] : '';
			$datos['fragment'] = isset($_parsed['fragment']) ? $_parsed['fragment'] : '';

			//Devuelve el port en formato enlace (string)		:8082	para el caso del port 80 o 443 retorna vacío
			$datos['port-link'] = url_part::create($datos, function($datos){
				$port_link = '';
				if ($datos['port'] <> 80 and $datos['port'] <> 443)
				{
					$port_link = ':' . $datos['port'];
				}
				return $port_link;
			}, [
				'port'
			]);

			//Devuelve si usa WWW (boolean)
			$datos['www'] = (bool)preg_match('/^www\./', $datos['host']);

			//Devuelve el base host (string)
			$datos['host-base'] = url_part::create($datos, function($datos){
				$host_base = explode('.', $datos['host']);

				while (count($host_base) > 2)
				{
					array_shift($host_base);
				}

				$host_base = implode('.', $host_base);
				return $host_base;
			}, [
				'host'
			]);

			//Devuelve el base host (string)
			$datos['host-parent'] = url_part::create($datos, function($datos){
				$host_parent = explode('.', $datos['host']);

				if ($datos['www'])
				{
					array_shift($host_parent);
				}

				if (count($host_parent) > 2)
				{
					array_shift($host_parent);
				}

				$host_parent = implode('.', $host_parent);
				return $host_parent;
			}, [
				'host',
				'www'
			]);

			//Devuelve el host mas el port (string)			intranet.net:8082
			$datos['host-link'] = url_part::create($datos, function($datos){
				$host_link = $datos['host'] . $datos['port-link'];
				return $host_link;
			}, [
				'host',
				'port-link'
			]);

			//Devuelve el host sin puntos o guiones	(string)	intranetnet
			$datos['host-clean'] = url_part::create($datos, function($datos){
				$host_clean = preg_replace('/[^a-z0-9]/i', '', $datos['host']);
				return $host_clean;
			}, [
				'host'
			]);

			//Devuelve el scheme mas el host-link (string)	https://intranet.net:8082
			$datos['host-uri'] = url_part::create($datos, function($datos){
				$host_uri = $datos['scheme'] . '://' . $datos['host-link'];
				return $host_uri;
			}, [
				'scheme',
				'host-link'
			]);

			//Devuelve la URL base hasta la aplicación
			$datos['base'] = url_part::create($datos, function($datos){
				$base = $datos['host-uri'] . $datos['srvpublic_path'];
				return $base;
			}, [
				'host-uri',
				'srvpublic_path'
			]);

			//Devuelve la URL base hasta el alojamiento real de la aplicación
			$datos['abs'] = url_part::create($datos, function($datos){
				$abs = $datos['host-uri'] . $datos['srvpublic_path'];
				return $abs;
			}, [
				'host-uri',
				'srvpublic_path'
			]);

			//Devuelve la URL base hasta el alojamiento real de la aplicación
			$datos['host-abs'] = url_part::create($datos, function($datos){
				$abs = str_replace('www.', '', $datos['host']) . $datos['srvpublic_path'];
				return $abs;
			}, [
				'host',
				'srvpublic_path'
			]);

			//Devuelve la URL completa incluido el PATH obtenido
			$datos['full'] = url_part::create($datos, function($datos){
				$full = $datos['base'] . $datos['path'];
				return $full;
			}, [
				'base',
				'path'
			]);

			//Devuelve la URL completa incluyendo los parametros QUERY si es que hay
			$datos['full-wq'] = url_part::create($datos, function($datos){
				$full_wq = $datos['full'] . ( ! empty($datos['query']) ? '?' : '' ) . $datos['query'];
				return $full_wq;
			}, [
				'full',
				'query'
			]);

			//Devuelve la ruta de la aplicación como directorio del cookie
			$datos['cookie-base'] = $datos['srvpublic_path'] . '/';

			//Devuelve la ruta de la aplicación como directorio del cookie hasta la carpeta de la ruta actual
			$datos['cookie-full'] = url_part::create($datos, function($datos){
				$cookie_full = $datos['srvpublic_path'] . rtrim($datos['path'], '/') . '/';
				return $cookie_full;
			}, [
				'srvpublic_path',
				'path'
			]);

			//Obtiene todos los datos enviados
			$datos['request'] =& request('array');

			//Request Method
			$datos['request_method'] = mb_strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'cli');

			$datos = APP()->filter_apply('JApi/url', $datos);
		}

		if ($get === 'array')
		{
			return $datos;
		}

		isset($datos[$get]) or $datos[$get] = NULL;
		return $datos[$get];
	}
}

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


if ( ! class_exists('url_part'))
{
	class url_part implements JsonSerializable
	{
		public static function create (&$datos, $string_callback, $parts)
		{
			return new self($datos, $string_callback, $parts);
		}

		private $datos;
		private $string_callback;
		private $parts;

		protected function __construct(&$datos, $string_callback, $parts)
		{
			$this->datos           =& $datos;
			$this->string_callback =  $string_callback;
			$this->parts        =  $parts;
		}

		public function __toString()
		{
			return call_user_func($this->string_callback, $this->datos, $this);
		}

		public function __debugInfo()
		{
			$data = [];

			$data['result'] = (string)$this->__toString();
			foreach($this->parts as $part)
			{
				$data[$part] = (string)$this->datos[$part];
			}

			return $data;
		}

		public function jsonSerialize():mixed
		{
			return $this->__toString();
		}
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

		if (in_array($port, [80, 443]))
		{
			## Son puertos webs que dependen del scheme
			empty($scheme) and $scheme = $port === 80 ? 'http' : 'https';
			$port = '';
		}

		empty($scheme)   or $scheme .= '://';
		empty($port)     or $port    = ':' . $port;
		empty($pass)     or $pass    = ':' . $pass;
		empty($query)    or $query   = '?' . $query;
		empty($fragment) or $fragment= '#' . $fragment;

		$pass     = ($user || $pass) ? "$pass@" : '';

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
		error_reporting(0);

		is_array($url) and $url = build_url($url);
		$parsed_url = parse_url($url);

		isset($parsed_url['scheme']) or $parsed_url['scheme'] = url('scheme');
		isset($parsed_url['path']) or $parsed_url['path'] = '/';
		if ( ! isset($parsed_url['host']))
		{
			$parsed_url['host'] = url('host');
			$parsed_url['path'] = url('srvpublic_path') . '/' . ltrim($parsed_url['path'], '/');
		}

		if ( ! is_null($query))
		{
			isset($parsed_url['query'])    or $parsed_url['query']  = [];
			is_array($parsed_url['query']) or $parsed_url['query']  = parse_str($parsed_url['query']);

			$parsed_url['query'] = array_merge($parsed_url['query'], $query);
		}

		$url = build_url ($parsed_url);

		APP() -> GetAndClear_BufferContent(); // El contenido no será reportado como error

		header('Location: ' . $url) or die('<script>location.replace("' . $url . '");</script>');
		die();
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
	/**
	 * force_exit()
	 */
	function force_exit ($status = null)
	{
		exit($status);
	}
}

