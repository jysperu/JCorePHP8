<?php
/**
 * JCore/JCA.php
 * @filesource
 */

namespace JCore;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore\ComponenteTrait;

class JCA
{
	use ComponenteTrait;

	public const PATH				= JCA_PATH;

	public const METADATA_COMPILED	= JCA_PATH . DS . 'jca.json';

	public static $METADATA_COMPILED = [
		'INITIAL_DIRECTORIES'	=> [], ## Directorios iniciales
		'DIRECTORIES_MTIME'		=> [], ## Versiones de todos los directorios utilizados
		'DIRECTORIES_NAMES'		=> [], ## Versiones de todos los directorios utilizados
		'AUTOLOAD_NAMESPACES'	=> [], ## Aloja todos los namespaces y sus directorios para el autoload
		'AUTOLOAD_DIRS'			=> [], ## Aloja todos los directorios a buscar las clases para el autoload
		'PREREQUESTS_CLASSES'	=> [], ## Lista de todos los pre-procesadores a ejecutar
		'INITIAL_URI_FORMAT'	=> [], ## Contiene todos los formatos de URIs que se validan para obtener IDs o cambiarlo
		'URI_CLASSES'			=> [], ## Para un matcheo de rutas (Subdividido para cada Namespace base)
		'MD5_INITDIRS'			=> '', ## MD5 de lo Directorios iniciales
		'MD5_JCORECNFG'			=> '', ## MD5 de todos los autoloads y directorios extras a compilar
	];

	protected static $_URI;

	public static function setUri (string $uri)
	{
		static :: $_URI = $uri;
	}

	public static function getUri ()
	{
		return static :: $_URI;
	}

	protected static $_request_method;

	public static function setRequestMethod (string $request_method)
	{
		static :: $_request_method = $request_method;
	}

	public static function getRequestMethod ()
	{
		return static :: $_request_method;
	}

	protected static $_response_type;

	public static function setResponseType (string $response_type)
	{
		static :: $_response_type = $response_type;
	}

	public static function getResponseType ()
	{
		return static :: $_response_type;
	}

	public static function searchUriClass (string $namespace_base, string $uri = null)
	{
		$URI_CLASSES = static :: $METADATA_COMPILED['URI_CLASSES'];
		is_null($uri) and $uri = static :: $_URI;

		
		
	}
}

/** APP() */
if ( ! function_exists('APP'))
{
	function APP ()
	{
		return JCore :: instance ();
	}
}