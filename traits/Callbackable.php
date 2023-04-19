<?php
/**
 * Callbackable.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * Callbackable
 */
trait Callbackable
{
	/**
	 * $_callbacks
	 */
	public static $_static_callbacks = [];

	/**
	 * $_callbacks
	 */
	protected $_callbacks = [];

	/**
	 * addGlobalCallback
	 */
	public static function addGlobalCallback (string $key, callable $callback):void
	{
		$callbacks =& static :: $_static_callbacks;
		isset($callbacks[$key]) or $callbacks[$key] = [];

		$callbacks[$key][] = $callback;
	}

	/**
	 * addInstanceCallback
	 */
	public function addInstanceCallback (string $key, callable $callback):Callbackable
	{
		$callbacks =& $this -> _callbacks;
		isset($callbacks[$key]) or $callbacks[$key] = [];

		$callbacks[$key][] = $callback;

		return $this;
	}

	/**
	 * execCallbacks
	 */
	public function execCallbacks (string $key, mixed $return = null, ...$params):mixed
	{
		array_unshift ($params, $return);
		$params[] = $this;
		$params[] = $key;

		$callbacks = static :: $_static_callbacks;
		$callbacks = isset($callbacks[$key]) ? $callbacks[$key] : [];
		foreach($callbacks as $callback)
		{
			try
			{
				$temp = call_user_func_array($callback, $params);
				$params[0] = $temp;
			}
			catch(Exception $e)
			{}
		}

		$callbacks = $this -> _callbacks;
		$callbacks = isset($callbacks[$key]) ? $callbacks[$key] : [];
		foreach($callbacks as $callback)
		{
			try
			{
				$temp = call_user_func_array($callback, $params);
				$params[0] = $temp;
			}
			catch(Exception $e)
			{}
		}

		return $params[0];
	}
}