<?php
namespace APP;

trait Request
{
	protected static $_request_method;

	public static function setRequestMethod ($method):void
	{
		static :: $_request_method = $method;
	}

	public static function getRequestMethod ()
	{
		return static :: $_request_method;
	}
}