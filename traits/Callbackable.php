<?php
/**
 * traits/Callbackable.php
 * @filesource
 */

defined('APPPATH') or exit(0); ## Acceso directo no autorizado

/**
 * Callbackable
 * La clase asociada puede alojar múltiples hooks que se pueden ejecutar en cualquier momento
 */
trait Callbackable
{
	/** $_static_callbacks */
	public static $_static_callbacks = [];

	/** $_callbacks */
	protected $_callbacks = [];

	/**
	 * addGlobalCallback()
	 * Agrega una función a ejecutar de manera global en cualquier instancia que se cree a nivel de clase
	 *
	 * @params string $key
	 * @params callable $callback
	 * @return void
	 */
	public static function addGlobalCallback (string $key, callable $callback):void
	{
		$callbacks =& static :: $_static_callbacks;
		isset($callbacks[$key]) or $callbacks[$key] = [];

		$callbacks[$key][] = $callback;
	}

	/**
	 * addInstanceCallback()
	 * Agrega una función a ejecutar unicamente a la instancia actual
	 *
	 * @params string $key
	 * @params callable $callback
	 * @return Callbackable
	 */
	public function addInstanceCallback (string $key, callable $callback): static
	{
		$callbacks =& $this -> _callbacks;
		isset($callbacks[$key]) or $callbacks[$key] = [];

		$callbacks[$key][] = $callback;

		return $this;
	}

	/**
	 * execCallbacks()
	 * Función que busca los hooks a ejecutar y retorna la respuesta tras la ejecución
	 *
	 * > Se ejecutan primero los callacks a nvel de clase y después a nivel de instancia
	 *
	 * @params string $key
	 * @params mixed $return Parámetro que contiene el primer valor por defecto que alojará la primera respuesta de cada hook
	 * @params mixed ...$params Parámetro adicionales que se envían a los hooks, pero solo el primero es el que se actualiza
	 * @return mixed
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