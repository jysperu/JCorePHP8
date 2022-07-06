<?php
/**
 * JCore/load.php
 * @filesource
 */

/** HOMEPATH */
defined('HOMEPATH') or exit(1);

/**
 * SRCPATH
 * Ruta donde se encuentra el código base de la aplicación
 */
defined('SRCPATH') or define ('SRCPATH', HOMEPATH);

/**
 * APPPATH
 * Ruta donde se encuentra la Aplicación Compilada
 * El contenido de este directorio alojará todos los archivos compilados desde el JCore
 * por lo que inicialment debe encontrarse vacío.
 * En cada compilación, todos los archivos son eliminados
 */
defined('APPPATH') or define ('APPPATH', SRCPATH . DIRECTORY_SEPARATOR . '$compiled');

if (defined('_APPINDX'))
	return; # Prevent Duplicate

define ('_APPINDX', APPPATH . DIRECTORY_SEPARATOR . 'index.php');

/**
 * Leer Aplicación Compilada para procesar el REQUEST
 * Si el sistema detecta que ya hay un index.php en el APPPATH entonces lo lee directamente
 * ya que no requiere de la lectura del JCore para ser procesado.
 * En caso no encontrarse aún el archivo; entonces, el JCore compilará la aplicación 
 * en aquella ruta para que un siguiente REQUEST lea directamente el contenido compilado.
 *
 * > La aplicación compilada intentará actualizarse de manera asíncrona utilizando el JCore 
 */
if (file_exists(_APPINDX))
{
	return require_once _APPINDX;
}

/**
 * Iniciar el compilador JCore
 */
chdir(__DIR__);
require_once 'JCore.php';

JCore :: compile();

/** Procesar el Request */
return require_once _APPINDX;