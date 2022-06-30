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

		if ( ! isset($_instance))
		{
			$_instance = new static ();
			$_instance -> init ();
		}

		return $_instance;
	}

	protected function init ()
	{}
}