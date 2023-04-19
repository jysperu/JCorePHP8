<?php

if ( ! function_exists('get_execution_time'))
{
	function get_execution_time ()
	{
		return microtime(true) - execution_start_time;
	}
}

if ( ! function_exists('get_execution_memory_consumption'))
{
	function get_execution_memory_consumption ()
	{
		return memory_get_usage(true) - execution_start_memory;
	}
}
