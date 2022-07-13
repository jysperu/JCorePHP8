<?php

/**
 * ErrorControl
 * El guardador puede ser un driver integrado a una base datos
 * 
 */
class ErrorControl
{
	const display_errors  = 0;
	const error_reporting = E_ALL; // E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED; ## Recomendado para producción

	const errors = E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR | E_CORE_WARNING | E_COMPILE_WARNING;

	public static function listen ()
	{
		@ini_set('display_errors', static :: display_errors);
		@error_reporting(static :: error_reporting);

		set_error_handler         ('ErrorControl::_handler_error'                 );
		set_exception_handler     ('ErrorControl::_handler_exception'             );
		register_shutdown_function('ErrorControl::_handler_last_error_on_shutdown');
	}

	/**
	 * logger()
	 * Función que guarda los logs
	 *
	 * @param BasicException|Exception|TypeError|Error|string 	$message	El mensaje reportado
	 * @param int|null 		$code		(Optional) El código del error
	 * @param string|null	$severity	(Optional) La severidad del error
	 * @param array|null 	$meta		(Optional) Los metas del error
	 * @param string|null 	$filepath	(Optional) El archivo donde se produjo el error
	 * @param int|null 		$line		(Optional) La linea del archivo donde se produjo el error
	 * @param array|null 	$trace		(Optional) La ruta que tomó la ejecución hasta llegar al error
	 * @return void
	 */
	public static function logger ($message, $code = NULL, $severity = NULL, $meta = NULL, $filepath = NULL, $line = NULL, $trace = NULL)
	{
		static $_alertas_omitidas = [
//			'Trying to access array offset on value of type null',
		];

		/**
		 * Listado de Levels de Errores
		 * @static
		 * @global
		 */
		static $error_levels = 
		[
			E_ERROR			    =>	'Error',				
			E_WARNING		    =>	'Warning',				
			E_PARSE			    =>	'Parsing Error',		
			E_NOTICE		    =>	'Notice',				

			E_CORE_ERROR		=>	'Core Error',		
			E_CORE_WARNING		=>	'Core Warning',		

			E_COMPILE_ERROR		=>	'Compile Error',	
			E_COMPILE_WARNING	=>	'Compile Warning',	

			E_USER_ERROR		=>	'User Error',		
			E_USER_DEPRECATED	=>	'User Deprecated',	
			E_USER_WARNING		=>	'User Warning',		
			E_USER_NOTICE		=>	'User Notice',		

			E_STRICT		    =>	'Runtime Notice'		
		];

		echo '<pre>';
		var_dump(func_get_args());
		return;

		$_directories = APP()->get_app_directories_labels();

		(is_array($severity) and is_null($meta)) and $meta = $severity and $severity = NULL;

		is_null($code) and $code = 0;
		is_null($meta) and $meta = [];
		is_array($meta) or $meta = (array)$meta;

		$meta['datetime']        = date('l d/m/Y H:i:s');
		$meta['time']            = time();
		$meta['microtime']       = microtime();
		$meta['microtime_float'] = microtime(true);

		if ($message instanceof BasicException)
		{
			$exception = $message;

			$meta = array_merge($exception->getMeta(), $meta);
			is_null($severity) and $severity = 'BasicException';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'BasicException';
		}
		elseif ($message instanceof Exception)
		{
			$exception = $message;

			is_null($severity) and $severity = 'Exception';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'Exception';
		}
		elseif ($message instanceof TypeError)
		{
			$exception = $message;

			is_null($severity) and $severity = 'Error';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'TypeError';
		}
		elseif ($message instanceof Error)
		{
			$exception = $message;

			is_null($severity) and $severity = 'Error';
			$meta['class'] = get_class($exception);
			$meta['class_base'] = 'Error';
		}

		if (isset($exception))
		{
			$message  = $exception->getMessage();

			is_null($filepath) and $filepath = $exception->getFile();
			is_null($line)     and $line     = $exception->getLine();
			is_null($trace)    and $trace    = $exception->getTrace();
			$code == 0         and $code     = $exception->getCode();
		}

		is_null($severity) and $severity = E_USER_NOTICE;

		$severity = isset($error_levels[$severity]) ? $error_levels[$severity] : $severity;

		is_null($message) and $message = '[NULL]';

		if (in_array($message, $_alertas_omitidas))
		{
			return;
		}

		if (is_null($trace))
		{
			$trace = debug_backtrace(false);
		}

		$trace = (array)$trace;
		$trace = array_values($trace);

		$trace = array_map(function($arr) use ($_directories) {
			if (isset($arr['file']))
			{
				foreach($_directories as $_directory => $label)
				{
					$arr['file'] = str_replace($_directory, $label, $arr['file']);
				}
			}

			return $arr;
		}, $trace);

		$trace_original = $trace;

		while(count($trace) > 0 and (
			( ! isset($trace[0]['file']))    or 
			(   isset($trace[0]['file'])     and str_replace(JAPIPATH, '', $trace[0]['file']) <> $trace[0]['file']) or 
			(   isset($trace[0]['function']) and in_array   ($trace[0]['function'], ['logger', '_handler_exception', '_handler_error', 'trigger_error']))
		))
		{
			array_shift($trace);
		}

		if (isset($trace[0]))
		{
			if (str_replace(JAPIPATH, '', $filepath) <> $filepath)
			{
				$line = $trace[0]['line'];
				$filepath = $trace[0]['file'];
			}

			isset($trace[0]['class'])    and ! isset($meta['class'])    and $meta['class']    = $trace[0]['class'];
			isset($trace[0]['function']) and ! isset($meta['function']) and $meta['function'] = $trace[0]['function'];
		}

		foreach($_directories as $_directory => $label)
		{
			$filepath = str_replace($_directory, $label, $filepath);
		}

		$SER = [];
		foreach($_SERVER as $x => $y)
		{
			if (preg_match('/^((GATEWAY|HTTP|QUERY|REMOTE|REQUEST|SCRIPT|CONTENT)\_|REDIRECT_URL|REDIRECT_STATUS|PHP_SELF|SERVER\_(ADDR|NAME|PORT|PROTOCOL))/i', $x))
			{
				$SER[$x] = $y;
			}
		}

		$meta['server'] = $SER;

		try
		{
			$url = url('array');
			$meta['url'] = $url;
		}
		catch (\BasicException $e){}
		catch (\Exception      $e){}
		catch (\TypeError      $e){}
		catch (\Error          $e){}
		finally
		{
			$meta['URL_loadable'] = isset($url);
		}

		try
		{
			$ip_address = ip_address('array');
			$meta['ip_address'] = $ip_address;
		}
		catch (\BasicException $e){}
		catch (\Exception      $e){}
		catch (\TypeError      $e){}
		catch (\Error          $e){}
		finally
		{
			$meta['IPADRESS_loadable'] = isset($url);
		}

		$meta[cdkdsp] = isset($_COOKIE[cdkdsp])  ? $_COOKIE[cdkdsp]  : NULL; // Código de Dispositivo

		$trace_slim = $trace;
		$trace_slim = array_filter($trace_slim, function($arr){
			return isset($arr['file']) and isset($arr['line']);
		});
		$trace_slim = array_map(function($arr) use ($_directories) {
			return $arr['file'] . '#' . $arr['line'];
		}, $trace_slim);
		$meta['trace_slim'] = $trace_slim;
		$meta['trace_original'] = $trace_original;
		$meta['instant_buffer'] = ob_get_contents();

		$_codigo = md5(json_encode([
			$message,
			$severity,
			$code,
			$filepath,
			$line,
			$trace_slim
		]));

		$data = [
			'codigo'   => $_codigo,
			'message'  => $message,
			'severity' => $severity,
			'code'     => $code,
			'filepath' => $filepath,
			'line'     => $line,
			'trace'    => $trace,
			'meta'     => $meta,
		];
		APP() -> action_apply('SaveLogger', $data);
	}

	public static function _handler_error ($severity, $message, $filepath, $line)
	{
		if (($severity & static :: error_reporting) !== $severity)
			return;

		$is_error = ((static :: errors & $severity) === $severity);

		$is_error and
		http_response_code(500);

		static :: logger($message, $severity, $severity, [], $filepath, $line);

		if ($is_error)
			exit(1);
	}

	public static function _handler_exception ($exception)
	{
		static :: logger($exception);

		ISCOMMAND or
		http_response_code(500);

		exit(1);
	}

	public static function _handler_last_error_on_shutdown ()
	{
		$last_error = error_get_last();

		if ( isset($last_error) && ($last_error['type'] & static :: errors))
			static :: _handler_error($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
	}
}