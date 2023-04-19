<?php

namespace ErrorControl;

use Modelo\ErrorControlSaver;
use Helper\Directories;

/**
 * ErrorControl\Saver
 */
trait Saver ## implements ErrorControlSaver
{
	public static function saveLog (string $hash, string $message, mixed $code, mixed $severity, array $metadata, string $filepath, string $fileline, array $trace, string $clase, string $baseclase):void
	{
		$logfile = Directories :: mkdir('logs/' . str_replace('/', '_', APPNAME) . '/' . date('Y-m'), ROOTPATH) . DS . date('d') . '.log';

		if ( ! file_exists($logfile))
		{
			file_put_contents($logfile, ';; Created at ' . static :: _timestamp() . PHP_EOL);
			chmod($logfile, 0644);
		}

		static $_hash_counter = [];
		isset($_hash_counter[$hash]) or $_hash_counter[$hash] = 0;

		$_hash_counter[$hash]++;

		if ($_hash_counter[$hash] >= 10)
		{
			file_put_contents($logfile, '[' . static :: _timestamp() . '] Prevent over memmory by repeated log #' . $hash . PHP_EOL . PHP_EOL, FILE_APPEND);
			exit;
		}

		if ($_hash_counter[$hash] >= 3)
		{
			file_put_contents($logfile, '[' . static :: _timestamp() . '] Repeated log with hash ' . $hash . PHP_EOL . PHP_EOL, FILE_APPEND);
			return;
		}

		file_put_contents($logfile, str_repeat('=', 23) . PHP_EOL . '[' . static :: _timestamp() . '] Error #' . $hash . PHP_EOL . 
						  $message . PHP_EOL . 
						  $severity . '(' . $code . ') ' . $clase . ' extends ' . $baseclase . ' on ' . $filepath . '#' . $fileline . PHP_EOL . 
						  json_encode($trace, JSON_PRETTY_PRINT) . PHP_EOL . 
						  json_encode($metadata, JSON_PRETTY_PRINT) . PHP_EOL . 
						  PHP_EOL, FILE_APPEND);
	}

	private static function _timestamp()
	{
		return gmdate('Y-m-d H:i:s') . '-00:00';
	}
}