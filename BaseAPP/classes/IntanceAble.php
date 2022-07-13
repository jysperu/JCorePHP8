<?php

trait IntanceAble
{
	/**
	 * instance()
	 * @static
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
	 * @protected
	 */
	protected function __construct ()
	{}

	/**
	 * _init()
	 * @protected
	 */
	protected function _init ()
	{}
}