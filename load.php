<?php
/**
 * load.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Se requiere la ruta donde se encuentra la Aplicación

/** Prevenir que no sea leído doble vez */
if (class_exists('JCore', false))
	return JCore :: getAPP();

/** Autoload (Cargar componentes del JCore) */
if ( ! function_exists('_autoload_JCore'))
{
	function _autoload_JCore (string $class):void
	{
		static $_bs = '\\'; // BackSlash

		$class = trim($class, $_bs);
		$parts = explode($_bs, $class);

		if ($parts[0] !== 'JCore')
			return; // Next Autoload

		if (count($parts) > 1)
			array_shift($parts); # Quitar el JCore como directorio

		$filename = __DIR__ . DS . implode(DS, $parts) . '.php';
		if ( ! file_exists($filename))
			return; // Next Autoload

		require_once $filename;
	}
}

spl_autoload_register('_autoload_JCore', true, true);

/** Procesar el Request */
return JCore :: getAPP();