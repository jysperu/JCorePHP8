<?php
/**
 * JCore/Controller/Command.php
 * @filesource
 */

namespace JCore\Controller;
defined('APPPATH') or exit(0); // Se requiere la ruta donde se encuentra la Aplicación

/**
 * Command
 *
 * isCommand():string
 */
trait Command
{
	public static function isCommand ():bool
	{
		static $_is_command;

		if (isset($_is_command))
			return $_is_command;

		$_is_command = (substr(PHP_SAPI, 0, 3) === 'cli' ? 'cli' : defined('STDIN'));
		return $_is_command;
	}
}