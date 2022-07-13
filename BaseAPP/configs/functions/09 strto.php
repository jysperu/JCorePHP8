<?php

use Symfony\Component\String\Slugger\AsciiSlugger;

if ($class = 'Symfony\Component\String\Slugger\AsciiSlugger' and ! class_exists($class))
	trigger_error('Clase no leída `' . $class . '`');

if ( ! function_exists('strtoslug'))
{
	function strtoslug (string $string, string $separator = '-') : string
	{
		static $slugger;
		if ( ! isset($slugger))
		{
			$symbolsMap = [
				'es' => ['&' => 'y', '%' => 'por-ciento'],
			];
			$symbolsMap = filter_apply('strtoslug/symbols', $symbolsMap);
			$slugger = new AsciiSlugger(null, $symbolsMap);
		}

		return $slugger
		-> slug($string, $separator, APP()->get_LANG())
		-> lower()
		-> slice(0, 100);
	}
}

if ( ! function_exists('strtobool'))
{
	function strtobool (mixed $str, bool$onempty = FALSE)
	{
		if (is_empty($str))
			return $onempty;

		if (is_bool($str))
			return $str;

		$str = (string) $str;

		if (preg_match('/^(s|y|v|t|1)/i', $str))
			return TRUE;

		if (preg_match('/^(n|f|0)/i', $str))
			return FALSE;

		return ! $onempty;
	}
}

if ( ! function_exists('strtonumber'))
{
	function strtonumber ($str = '')
	{
		$str = (string)$str;
		$str = preg_replace('/[^0-9\.]/i', '', $str);

		$str = (double)$str;
		return $str;
	}
}
