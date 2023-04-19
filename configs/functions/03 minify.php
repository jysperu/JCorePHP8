<?php

use Helper\Compressor;

if ($class = 'MatthiasMullie\Minify\JS' and ! class_exists($class))
	trigger_error('Clase no leída `' . $class . '`');

if ($class = 'MatthiasMullie\Minify\CSS' and ! class_exists($class))
	trigger_error('Clase no leída `' . $class . '`');

if ( ! function_exists('html_compressor'))
{
	function html_compressor($buffer)
	{
		Compressor :: html ($buffer);
	}
}

if ( ! function_exists('js_compressor'))
{
	function js_compressor (string $content, $options = [])
	{
		Compressor :: JS ($content, $options);
	}
}

if ( ! function_exists('css_compressor'))
{
	function css_compressor (string $content, $options = [])
	{
		Compressor :: CSS ($content, $options);
	}
}

if ( ! function_exists('json_compressor'))
{
	function json_compressor ($content)
	{
		Compressor :: json ($content);
	}
}