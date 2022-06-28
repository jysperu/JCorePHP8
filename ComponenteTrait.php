<?php
/**
 * JCore/ComponenteTrait.php
 * @filesource
 */

namespace JCore;
defined('APPPATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore as JCoreInstance;

trait ComponenteTrait
{
	/**
	 * instance()
	 * @static
	 * @return JCore
	 */
	final public static function instance (JCoreInstance $JCoreInstance = null)
	{
		static $_instance;

		$_instance or 
		$_instance = (new static ()) -> init ($JCoreInstance);

		return $_instance;
	}

	protected function init (JCoreInstance $JCore = null)
	{}
}