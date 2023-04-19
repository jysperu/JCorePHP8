<?php

/**
 * ErrorControl
 */
use Modelo\ErrorControlSaver;

class ErrorControl implements ErrorControlSaver
{
	use ErrorControl\Saver;

	const display_errors  = 0;
	const error_reporting = E_ALL; // E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED; ## Recomendado para producción

	const errors = E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR | E_CORE_WARNING | E_COMPILE_WARNING;

	protected static $_listening = false;

	protected static $_auto_logger = true;

	protected static function _init ()
	{
		static $_initialized = false;

		if ($_initialized)
			return;

		register_shutdown_function('ErrorControl::_handler_last_error_on_shutdown');
	}

	public static function silence (bool $silencio = true)
	{
		static :: setAutoLogger ( ! $silencio);
	}

	public static function setAutoLogger (bool $auto_logger)
	{
		static :: $_auto_logger = $auto_logger;
	}

	protected static $_orig_display_errors;
	protected static $_orig_error_reporting;

	public static function stop ()
	{
		static :: _init();

		if ( ! static::$_listening)
			return;

		static::$_listening = false;

		restore_error_handler    ();
		restore_exception_handler();

		@ini_set('display_errors', static :: $_orig_display_errors);
		@error_reporting(static :: $_orig_error_reporting);
	}

	public static function listen ()
	{
		static :: _init();

		if (static::$_listening)
			return;

		static::$_listening = true;

		static :: $_orig_display_errors  = @ini_get('display_errors');
		static :: $_orig_error_reporting = error_reporting();

		@ini_set('display_errors', static :: display_errors);
		@error_reporting(static :: error_reporting);

		set_error_handler    ('ErrorControl::_handler_error'    );
		set_exception_handler('ErrorControl::_handler_exception');
	}

	/**
	 * Listado de Levels de Errores
	 * @static
	 * @global
	 */
	const error_levels = [
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

	/**
	 * logger()
	 * Función que procesa los logs
	 *
	 * @param MetaException|Exception|TypeError|Error	Exception o Error ocurrido
	 *
	 * @param string
	 * @param int|null 		$code		(Optional) El código del error
	 * @param string|null	$severity	(Optional) La severidad del error
	 * @param array|null 	$meta		(Optional) Los metas del error
	 * @param string|null 	$filepath	(Optional) El archivo donde se produjo el error
	 * @param int|null 		$line		(Optional) La linea del archivo donde se produjo el error
	 * @param array|null 	$trace		(Optional) La ruta que tomó la ejecución hasta llegar al error
	 * @return void
	 */
	public static function logger (mixed $exception)
	{
		$hash      = null;
		$message   = null;
		$code      = null;
		$severity  = null;
		$metadata  = null;
		$filepath  = null;
		$fileline  = null;
		$trace     = null;
		$clase     = null;
		$baseclase = null;

			if ($exception instanceof MetaException)		# (Exception)		Personalized Exception to establish metadata
		{
			$hash      = $exception -> getHash();
			$metadata  = $exception -> getMetaData();
			$baseclase = 'MetaException';
			$severity  = 'Exception';
		}
		elseif ($exception instanceof DivisionByZeroError)	# (ArithmeticError)	DivisionByZeroError is thrown when an attempt is made to divide a number by zero.
		{
			$baseclase = 'DivisionByZeroError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof ParseError)			# (CompileError)	ParseError is thrown when an error occurs while parsing PHP code, such as when eval() is called.
		{
			$baseclase = 'ParseError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof ArgumentCountError)	# (TypeError)		ArgumentCountError is thrown when too few arguments are passed to a user-defined function or method.
		{
			$baseclase = 'ArgumentCountError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof ArithmeticError)		# (extends Error)	ArithmeticError is thrown when an error occurs while performing mathematical operations.
		{
			$baseclase = 'ArithmeticError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof AssertionError)		# (extends Error)	AssertionError is thrown when an assertion made via assert() fails.
		{
			$baseclase = 'AssertionError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof CompileError)			# (extends Error)	CompileError is thrown for some compilation errors, which formerly issued a fatal error.
		{
			$baseclase = 'CompileError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof ValueError)			# (extends Error)	A ValueError is thrown when the type of an argument is correct but the value of it is incorrect.
		{
			$baseclase = 'ValueError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof UnhandledMatchError) 	# (extends Error)	An UnhandledMatchError is thrown when the subject passed to a match expression is not handled by any arm of the match expression.
		{
			$baseclase = 'UnhandledMatchError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof FiberError) 			# (extends Error)	FiberError is thrown when an invalid operation is performed on a Fiber.
		{
			$baseclase = 'FiberError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof TypeError)			# (extends Error)	A TypeError
		{
			$baseclase = 'TypeError';
			$severity  = 'Error';
		}
		elseif ($exception instanceof ErrorException)		# (Exception)		An Error Exception.
		{
			$severity = $exception -> getSeverity();
			$baseclase = 'ErrorException';
			$severity  = 'Exception';
		}
		elseif ($exception instanceof Exception)			# (Throwable)		Exception is the base class for all user exceptions.
		{
			$baseclase = 'Exception';
			$severity  = 'Exception';
		}
		elseif ($exception instanceof Error)				# (Throwable)		Error is the base class for all internal PHP errors.
		{
			$baseclase = 'Error';
			$severity  = 'Error';
		}
		elseif ($exception instanceof Throwable)
		{
			$baseclase = 'Throwable';
			$severity  = 'Exception';
		}
		else
		{
			$params = func_get_args();
			$exception = null;

			if (count($params) === 1 and is_array($params[0]))
				$params = $params[0];

			switch(count($params))
			{
				case 1: ## logger(string $message)
					$message = (string) array_shift($params);
					break;

				case 2: ## logger(string $message, int $code)  |  logger(string $message, string $severity)  |  logger(string $message, array $metadata)
					$message = (string) array_shift($params);

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;
					break;

				case 3: ## logger(string $message, int $code, string $severity)  |  logger(string $message, string $severity, int $code)  |  logger(string $message, array $metadata, int $code)  |  logger(string $message, array $metadata, string $severity)
					$message = (string) array_shift($params);

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;
					break;

				case 4: ## logger(string $message, int $code, string $severity, array $metadata)  |  variacion de parametros excepto el message
					$message = (string) array_shift($params);

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;
					break;

				default: ## logger(string $message, int $code, string $severity, array $metadata, string $filepath = NULL, int $line = NULL, array $trace = NULL)
					$message = (string) array_shift($params);

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;

					$param  = array_shift($params);
					if (is_array($param))
						$metadata = $param;
					elseif (is_numeric($param))
						$code = $param;
					else
						$severity = $param;

					$filepath = array_shift($params);
					$line     = array_shift($params);
					$trace    = array_shift($params);
					break;
			}
		}

		if ( ! is_null($exception))
		{
			$message  = $exception -> getMessage();
			$code     = $exception -> getCode();
			$filepath = $exception -> getFile();
			$fileline = $exception -> getLine();
			$trace    = $exception -> getTrace();
			$clase    = get_class($exception);
		}
		else
		{
			$message  = (string) $message;
			$clase    = 'String';
			$baseclase= 'PHP' . phpversion();

			if (is_null($code))
			{
				$error_levels_flipped = array_flip(static :: error_levels);

				if ( ! is_null($severity) and isset($error_levels_flipped[$severity]))
					$code = $error_levels_flipped[$severity];
				else
					$code = E_USER_WARNING;
			}

			if (is_null($severity))
			{
				$error_levels = static :: error_levels;

				if ( ! is_null($code) and isset($error_levels[$code]))
					$severity = $error_levels[$code];
				else
					$severity = 'Warning';
			}

			if (is_null($trace))
				$trace = debug_backtrace(false);
		}

		$metadata = (array) $metadata;
		$trace    = (array) $trace;

		## Momento
		$metadata['__fecha_hora'] = date('D Y-m-d H:i:s');
		$metadata['__time']       = time();
		$metadata['__microtime']  = microtime(true);
		$metadata['__gmdate']     = gmdate('D Y-m-d H:i:s');

		## Protect directories name
		$message = static :: _mask_base_directories($message);

		## Process Trace
		$trace = array_map(function($arr){
			isset($arr['function']) or $arr['function'] = '[NO FUNCTION]';

			isset($arr['file']) or $arr['file'] = '[NO FILE]';
			$arr['file'] = static :: _mask_base_directories($arr['file']);

			isset($arr['line']) or $arr['line'] = '';
			$arr['file_line'] = $arr['file'] . (empty($arr['line']) ? '' : '#') . $arr['line'];

			if (isset($arr['object']))
				$arr['object'] = 'Object provided but it have been hidden';

			if (isset($arr['args']))
				$arr['args'] = static :: _eliminarObjetosYDirectorios($arr['args']);

			return $arr; ## function (string), file (string), line (int), file_line (string), class (string), type (string), args (array)
		}, $trace);

		$thisfile = static :: _mask_base_directories(__FILE__);
		$trace_omitido = [];
		while(count($trace) > 0 and (
			str_replace($thisfile, '', $trace[0]['file_line']) <> $trace[0]['file_line'] 
			or
			(isset($trace[0]['class']) and $trace[0]['class'] === 'ErrorControl')
		))
			$trace_omitido[] = array_shift($trace); ## Omitir el trace si ha sido generado desde una función para guardar el log

		if (isset($trace[0]))
		{
			if (is_null($filepath))
				$filepath = $trace[0]['file'];

			if (is_null($fileline))
				$fileline = $trace[0]['line'];

			if (isset($trace[0]['function']) and ! empty($trace[0]['function']) and $trace[0]['function'] <> '[NO FUNCTION]')
				$metadata['__function'] = $trace[0]['function'];

			if (isset($trace[0]['class']) and ! empty($trace[0]['class']))
				$metadata['__class'] = $trace[0]['class'];
		}

		## Código de Dispositivo
		if(defined('cookie4_device'))
			$metadata['__' . cookie4_device] = isset($_COOKIE[cookie4_device])  ? $_COOKIE[cookie4_device]  : NULL;

		## APP metadata
		if (class_exists('APP'))
		{
			static :: catchAll (function() use (&$metadata){
				$APP_metadata = APP :: debugInfo();
				$APP_metadata = static :: _eliminarObjetosYDirectorios($APP_metadata);

				foreach ($APP_metadata as $k => $v)
				{
					$metadata[isset($metadata['__' . $k]) ? ('__app_' . $k) : ('__' . $k)] = $v;
				}
			});
		}

		## XONK metadata
		if (class_exists('XONK'))
		{
			static :: catchAll (function() use (&$metadata){
				$XONK_metadata = XONK :: debugInfo();
				foreach ($XONK_metadata as $k => $v)
				{
					$metadata[isset($metadata['__' . $k]) ? ('__xonk_' . $k) : ('__' . $k)] = $v;
				}
			});
		}

		## BenchMark metadata
		if (class_exists('BenchMark'))
		{
			static :: catchAll (function() use (&$metadata){
				$BenchMark_metadata = BenchMark :: getHistory();
				$metadata['__BenchMark'] = $BenchMark_metadata;
			});
		}

		## SERVER metadata
		$metadata['__server'] = [];
		foreach($_SERVER as $x => $y)
		{
			if (preg_match('/^((GATEWAY|HTTP|QUERY|REMOTE|REQUEST|SCRIPT|CONTENT)\_|REDIRECT_URL|REDIRECT_STATUS|PHP_SELF|SERVER\_(ADDR|NAME|PORT|PROTOCOL))/i', $x))
			{
				$metadata['__server'][$x] = $y;
			}
		}

		## Trace Omitido
		if (count($trace_omitido) > 0)
		{
			$metadata['__trace_omited'] = $trace_omitido;
		}

		## Instant Buffer
		$metadata['instant_buffer'] = ob_get_contents();

		## Hash del log
		if (is_null($hash))
		{
			$hash = md5(json_encode([
				$message,
				$code,
				$severity,
				$filepath,
				$fileline,
				$trace
			]));
		}

		static :: saveLog ($hash, $message, $code, $severity, $metadata, $filepath, $fileline, $trace, $clase, $baseclase);
	}

	private static function _eliminarObjetosYDirectorios (mixed $var)
	{
		if (is_array($var))
		{
			foreach($var as $k => $v)
			{
				$var[$k] = static :: _eliminarObjetosYDirectorios($v);
			}
			return $var;
		}

		if (is_object($var))
		{
			return get_class($var) . ' Instance';
		}

		if (is_string($var))
			return static :: _mask_base_directories($var);

		return $var;
	}

	public static function _handler_error ($severity, $message, $filepath, $line)
	{
		if (($severity & static :: error_reporting) !== $severity)
			return;

		$is_error = ((static :: errors & $severity) === $severity);

		$is_error and
		http_response_code(500);

		if (static :: $_auto_logger)
			static :: logger($message, $severity, $severity, [], $filepath, $line);

		if ($is_error)
			exit(1);
	}

	public static function _handler_exception ($exception)
	{
		if (static :: $_auto_logger)
			static :: logger($exception);

		ISCOMMAND or
		http_response_code(500);

		exit(1);
	}

	public static function _handler_last_error_on_shutdown ()
	{
		if ( ! static::$_listening)
			return;

		$last_error = error_get_last();

		if ( isset($last_error) && ($last_error['type'] & static :: errors))
			static :: _handler_error($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
	}

	public static function catchAll (callable $function, callable $onerror = null, callable $always = null)
	{
		$call_onerror = function($e) use ($onerror) {
			if ( ! is_null($onerror))
				$onerror($e);
		};

		$return = null;

		try
		{
			$return = $function();
		}
		catch (MetaException       $e) { $call_onerror($e); }
		catch (DivisionByZeroError $e) { $call_onerror($e); }
		catch (ParseError          $e) { $call_onerror($e); }
		catch (ArgumentCountError  $e) { $call_onerror($e); }
		catch (ArithmeticError     $e) { $call_onerror($e); }
		catch (AssertionError      $e) { $call_onerror($e); }
		catch (CompileError        $e) { $call_onerror($e); }
		catch (ValueError          $e) { $call_onerror($e); }
		catch (UnhandledMatchError $e) { $call_onerror($e); }
		catch (FiberError          $e) { $call_onerror($e); }
		catch (TypeError           $e) { $call_onerror($e); }
		catch (ErrorException      $e) { $call_onerror($e); }
		catch (Exception           $e) { $call_onerror($e); }
		catch (Error               $e) { $call_onerror($e); }
		catch (Throwable           $e) { $call_onerror($e); }

		if ( ! is_null($always))
			$always($return);

		return $return;
	}

	private static function _mask_base_directories (string $var)
	{
		if (function_exists('mask_base_directories'))
			return mask_base_directories($var);
		return str_replace(APPPATH, 'APPPATH', $var);
	}

}