<?php
/**
 * JCore/JCA.php
 * @filesource
 */

namespace JCore;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

class JCA
{
	public const PATH				= JCA_PATH;

	public const METADATA_COMPILED	= JCA_PATH . DS . 'jca.metadata.json';

	public static $METADATA_COMPILED = [
		'INITIAL_DIRECTORIES' => [], ## Directorios iniciales
		'DIRECTORIES_MTIME'   => [], ## Versiones de todos los directorios utilizados
		'DIRECTORIES_NAMES'   => [], ## Versiones de todos los directorios utilizados
		'AUTOLOAD_NAMESPACES' => [], ## Aloja todos los namespaces y sus directorios para el autoload
		'AUTOLOAD_DIRS'       => [], ## Aloja todos los directorios a buscar las clases para el autoload
		'MD5_INITDIRS'		  => '', ## MD5 de lo Directorios iniciales
		'MD5_JCORECNFG'		  => '', ## MD5 de todos los autoloads y directorios extras a compilar
	];

//	public const METADATA_FILESOURCES = JCA_PATH . DS . 'jca.filesource.json';
//	public static $METADATA_FILESOURCES = []; ## Listado de todos los archivos copiados y su origen (Permite verificar de donde proviene algún posible error)

	private function __construct ()
	{} ## No se puede instanciar
}