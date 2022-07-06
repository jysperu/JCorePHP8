<?php
/**
 * classes/JCA.php
 * @filesource
 */

defined('JCA_PATH') or exit(0); // Acceso directo no autorizado

/**
 * JCA
 */

class JCA extends JArray
{
	/**
	 * instance()
	 * @static
	 * @return JCore
	 */
	public static function instance ():JCA
	{
		static $_instance;
		isset($_instance) or $_instance = new static ();
		return $_instance;
	}

	/**
	 * __construct()
	 * @protected
	 */
	protected function __construct ()
	{
		parent :: __construct ();
	}
}