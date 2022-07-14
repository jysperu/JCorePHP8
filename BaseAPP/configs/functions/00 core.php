<?php

if ( ! function_exists('APP'))
{
	function APP ()
	{
		return APP :: instance();
	}
}

if ( ! function_exists('config'))
{
	function config (string $key, string ...$subkeys)
	{
		$return = APP() -> getConfig($key);
		foreach ($subkeys as $subkey)
		{
			$return = $return[$subkey];
		}
		return $return;
	}
}

if ( ! function_exists('use_structure'))
{
	function use_structure (string $clase, array $option = [])
	{
		$clase = 'Structure\\' . $clase;
		
	}
}

if ( ! function_exists('use_theme'))
{
	function use_theme (string $clase, array $option = [])
	{
		return use_structure($clase, $option);
	}
}

if ( ! function_exists('force_exit'))
{
	function force_exit (int $status = null)
	{
		exit ($status);
	}
}