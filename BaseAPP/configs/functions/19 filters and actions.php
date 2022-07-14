<?php

/**
 * $_hooks_filters
 * Variable que almacena todas las funciones aplicables para los filtros
 */
$_hooks_filters = [];

/**
 * $_hooks_filters_defs
 * Variable que almacena todas las funciones aplicables para los filtros 
 * por defecto cuando no se hayan asignado alguno
 */
$_hooks_filters_defs = [];

/**
 * $_hooks_actions
 * Variable que almacena todas las funciones aplicables para los actions
 */
$_hooks_actions = [];

/**
 * $_hooks_actions_defs
 * Variable que almacena todas las funciones aplicables para los actions
 * por defecto cuando no se hayan asignado alguno
 */
$_hooks_actions_defs = [];

if ( ! function_exists('filter_add'))
{
	/**
	 * filter_add()
	 * Agrega funciones programadas para filtrar variables
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (Orden) a ejecutar la función cuando es llamado el Hook
	 * @return Boolean
	 */
	function filter_add (string $key, callable $function, int $priority = 50)
	{
		if (empty($key))
			return false;

		global $_hooks_filters;
		$_hooks_filters[$key][$priority][] = $function;
		return true;
	}
}

if ( ! function_exists('non_filtered'))
{
	/**
	 * non_filtered()
	 * Agrega funciones programadas para filtrar variables
	 * por defecto cuando no se hayan asignado alguno
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (Orden) a ejecutar la función cuando es llamado el Hook
	 * @return Boolean
	 */
	function non_filtered (string $key, callable $function, int $priority = 50)
	{
		if (empty($key))
			return false;

		global $_hooks_filters_defs;
		$_hooks_filters_defs[$key][$priority][] = $function;
		return true;
	}
}

if ( ! function_exists('filter_apply'))
{
	/**
	 * filter_apply()
	 * Ejecuta funciones para validar o cambiar una variable
	 *
	 * @param String $key Hook
	 * @param Mixed	&...$params Parametros a enviar en las funciones del Hook (Referenced)
	 * @return Mixed $params[0] || null
	 */
	function filter_apply (string $key, &...$params)
	{
		if (empty($key))
		{
			trigger_error('Hook es requerido', E_USER_WARNING);
			return null;
		}

		count($params) === 0 and $params[0] = null;

		global $_hooks_filters, $_hooks_filters_defs;

		if ( ! isset($_hooks_filters[$key]) or count($_hooks_filters[$key]) === 0)
		{
			if ( ! isset($_hooks_filters_defs[$key]) or count($_hooks_filters_defs[$key]) === 0)
			{
				return $params[0];
			}

			$functions = $_hooks_filters_defs[$key];
		}
		else
		{
			$functions = $_hooks_filters[$key];
		}

		krsort($functions);

		$params_0 = $params[0]; ## Valor a retornar
		foreach($functions as $priority => $funcs){
			foreach($funcs as $func){
				$return = call_user_func_array($func, $params);

				if ( ! is_null($return) and $params_0 === $params[0])
				{
					## El parametro 0 no ha cambiado por referencia 
					## y en cambio la función ha retornado un valor no NULO 
					## por lo tanto le asigna el valor retornado
					$params[0] = $return;
				}

				$params_0 = $params[0]; ## Valor a retornar
			}
		}

		return $params_0;
	}
}

if ( ! function_exists('filter_clear'))
{
	/**
	 * filter_clear()
	 */
	function filter_clear (string $key, int $priority = null)
	{
		global $_hooks_filters;

		if ( ! empty($priority))
			unset($_hooks_filters[$key][$priority]);
		else
			unset($_hooks_filters[$key]);
	}
}

if ( ! function_exists('nonfilter_clear'))
{
	/**
	 * nonfilter_clear()
	 */
	function nonfilter_clear (string $key, int $priority = null)
	{
		global $_hooks_filters_defs;

		if ( ! empty($priority))
			unset($_hooks_filters_defs[$key][$priority]);
		else
			unset($_hooks_filters_defs[$key]);
	}
}

if ( ! function_exists('action_add'))
{
	/**
	 * action_add()
	 * Agrega funciones programadas
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority Prioridad (orden) a ejecutar la función
	 * @return Boolean
	 */
	function action_add (string $key, callable $function, int $priority = 50)
	{
		if (empty($key))
			return false;

		global $_hooks_actions;
		$_hooks_actions[$key][$priority][] = $function;
		return true;
	}
}

if ( ! function_exists('non_actioned'))
{
	/**
	 * non_actioned()
	 * Agrega funciones programadas
	 * por defecto cuando no se hayan asignado alguno
	 *
	 * @param String $key Hook
	 * @param Callable $function Función a ejecutar
	 * @param Integer $priority	Prioridad (orden) a ejecutar la función
	 * @return Boolean
	 */
	function non_actioned (string $key, callable $function, int $priority = 50)
	{
		if (empty($key))
			return false;

		global $_hooks_actions_defs;
		$_hooks_actions_defs[$key][$priority][] = $function;
		return true;
	}
}

if ( ! function_exists('action_apply'))
{
	/**
	 * action_apply()
	 * Ejecuta las funciones programadas
	 *
	 * @param String $key Hook
	 * @param Mixed &...$params Parametros a enviar en las funciones del Hook (Referenced)
	 * @return Boolean || null
	 */
	function action_apply (string $key, &...$params)
	{
		if (empty($key))
		{
			trigger_error('Hook es requerido', E_USER_WARNING);
			return null;
		}

		$RESULT = null;

		global $_hooks_actions, $_hooks_actions_defs;

		if ( ! isset($_hooks_actions[$key]) or count($_hooks_actions[$key]) === 0)
		{
			if ( ! isset($_hooks_actions_defs[$key]) or count($_hooks_actions_defs[$key]) === 0)
				return $RESULT;

			$functions = $_hooks_actions_defs[$key];
		}
		else
		{
			$functions = $_hooks_actions[$key];
		}

		krsort($functions);

		foreach($functions as $priority => $funcs)
		{
			foreach($funcs as $func)
			{
				$RESULT = call_user_func_array($func, $params);
			}
		}

		return $RESULT;
	}
}

if ( ! function_exists('action_clear'))
{
	/**
	 * action_clear()
	 */
	function action_clear (string $key, int $priority = null)
	{
		global $_hooks_actions;

		if ( ! empty($priority))
			unset($_hooks_actions[$key][$priority]);
		else
			unset($_hooks_actions[$key]);
	}
}

if ( ! function_exists('nonaction_clear'))
{
	/**
	 * nonaction_clear()
	 */
	function nonaction_clear (string $key, int $priority = null)
	{
		global $_hooks_actions_defs;

		if ( ! empty($priority))
			unset($_hooks_actions_defs[$key][$priority]);
		else
			unset($_hooks_actions_defs[$key]);
	}
}
