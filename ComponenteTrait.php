<?php
/**
 * JCore/ComponenteTrait.php
 * @filesource
 */

namespace JCore;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

trait ComponenteTrait
{
	/**
	 * instance()
	 * @static
	 * @return JCore
	 */
	final public static function instance ()
	{
		static $_instance;

		$_instance or 
		$_instance = (new static ()) -> init ();

		return $_instance;
	}

	protected function init ()
	{}
}