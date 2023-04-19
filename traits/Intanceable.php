<?php
/*!
 * APPPATH/traits/APP.php
 * @filesource
 */
defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * Intanceable
 * La clase asociada solo puede ser instanciada una sola vez
 */
trait Intanceable
{
	/**
	 * instance()
	 * Devuelve la única instancia generada
	 * @return	Intanceable
	 */
	public static function instance ()
	{
		static $_instance;

		if ( ! isset($_instance))
		{
			$_instance = new static ();
			$_instance -> _init();
		}

		return $_instance;
	}

	/**
	 * __construct()
	 * El constructor de la clase ahora es protegido y solo puede ser llamado por la misma clase
	 */
	protected function __construct ()
	{}

	/**
	 * _init()
	 * Función que se ejecutará inmediatamente tras la generación de la instancia.
	 */
	protected function _init ()
	{}
}