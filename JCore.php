<?php
/**
 * JCore.php
 * @filesource
 */

defined('SRCPATH') or exit(2); # Acceso directo no autorizado

/** Variables base */
defined('DS') or define ('DS', DIRECTORY_SEPARATOR);
defined('BS') or define ('BS', '\\');

/** Variables de directorios */
defined('HOMEPATH')  or define('HOMEPATH',  SRCPATH); # Ruta Pública
defined('ROOTPATH')  or define('ROOTPATH',  SRCPATH); # Ruta base de todos los entornos
defined('COREPATH')  or define('COREPATH',  SRCPATH); # Ruta de código prioritario
defined('APPPATH')   or define('APPPATH',   SRCPATH . DS . '$compiled');
defined('JCorePATH') or define('JCorePATH', __DIR__);

/** Registrar Autoload para el Compilador JCore */
if ( ! function_exists('_autoload_JCore'))
{
	function _autoload_JCore (string $class):void
	{
		$class = trim($class, BS);
		$parts = explode(BS, $class);
		$nbase = $parts[0];
		$dbase = null;

		if ($parts[0] === 'JCore')
		{
			array_shift($parts); # Quitar el JCore como directorio
			$dbase = __DIR__;
		}
		else
		{
			$dbase = __DIR__ . DS . 'BaseAPP' . DS . 'classes';
		}

		$filename = $dbase . DS . implode(DS, $parts) . '.php';
		if ( ! file_exists($filename))
			return; # Next Autoload

		require_once $filename;
	}

	spl_autoload_register('_autoload_JCore', true, true);
}

/**
 * JCore
 * Compilador de la aplicación
 *
 * JCore :: compile();
 */
use JCore\JPart\Directories as DirectoriesPart;

class JCore
{
	use DirectoriesPart;

	//=================================================================================//
	//==== VARIABLES ESTÁTICAS — JCA\*                                            =====//
	//=================================================================================//

	/**
	 * $RECOMPILAR
	 *
	 * Puede ser una @callable o un @bool
	 *
	 * Si es un @callable debe retornar TRUE para comfirmar una recompilación
	 * |- Los parámetros que se envian a esta función es:
	 * |  + JCA :: $METADATA_COMPILED	(JSON de la metadata de la última compilación)
	 * |  + JCA :: METADATA_COMPILED	(Archivo en el cual se aloja el metadata)
	 * |  + JCA :: PATH					(Directorio donde se encuentra alojado el JCA)
	 * |  + JCoreInstance				(Esta clase)
	 *
	 * Si es un @bool entonces si es TRUE se recompilará el JCA
	 *
	 * Cualquier otro caso será omitido
	 */
	public static $RECOMPILAR = NULL;

	/**
	 * $AUTOLOAD_ROUTES
	 * Aloja los namespaces y su directorio para las clases pre-procesadoras de los REQUEST.
	 * Permiten cambiar el URI (Route).
	 *
	 * > El orden indicado es el modo con el cual serán leído.
	 *
	 * 'Namespace' => '/Directory',
	 */
	public static $AUTOLOAD_ROUTES = [
		'ObjRoute'   => '/objroutes',   # Permite comprobar la existencia de los objetos con las IDs recibidas
		'ReRoute'    => '/reroutes',    # Permite modificar la URI por otra que se haya enmascarado
		'AlwRoute'   => '/alwroutes',   # Permite comprobar permisos del usuario logueado
		'PreRequest' => '/prerequests', # Permite ejecutar alguna acción previo al proceso oficila del request
	];

	/**
	 * $AUTOLOAD_REQUEST_DIR
	 * Aloja el directorio para el namespace `Request`
	 * (Procesador del Request)
	 */
	public static $AUTOLOAD_REQUEST_DIR = '/requests';

	/**
	 * $AUTOLOAD_RESPONSE_DIR
	 * Aloja el directorio para el namespace `Response`
	 * (Pantallas HTML)
	 */
	public static $AUTOLOAD_RESPONSE_DIR = '/responses';

	/**
	 * $AUTOLOAD_STRUCTURE_DIR
	 * Aloja el directorio para el namespace `Structure`
	 * (Estructuras de las Pantallas HTML)
	 */
	public static $AUTOLOAD_STRUCTURE_DIR = '/structures';

	/**
	 * $AUTOLOAD_PROCESSES_DIR
	 * Aloja el directorio para el namespace `Process`
	 * (Procesos internos)
	 */
	public static $AUTOLOAD_PROCESSES_DIR = '/processes';

	/**
	 * $AUTOLOAD_NAMESPACES
	 * Aloja múltiples namespaces y su directorio respectivo
	 *
	 * 'Namespace' => '/Directory',
	 */
	public static $AUTOLOAD_NAMESPACES = [];

	/**
	 * $AUTOLOAD_DIRS
	 * Aloja múltiples directorio en la cual poder buscar algunas clases solicitadas
	 * Todos al ser compilados serán copiados en un solo directorio
	 */
	public static $AUTOLOAD_DIRS = [
		### Posibles clases añadidas
		'/classes',
		'/configs/classes',

		### Posibles librerías añadidas
		'/libs',
		'/configs/libs',
	];

	/**
	 * $COMPILER_EXTRA_DIRS
	 * Identifica todo posible directorio requerido a compilar en el JCA
	 */
	public static $COMPILER_EXTRA_DIRS = [];

	//=================================================================================//
	//==== CONSTRUCTORES                                                          =====//
	//=================================================================================//

	/**
	 * init ()
	 * Procesa el núcleo y el Request
	 */
	public static function compile()
	{
		//=== Ejecutar la función JCoreInit si existe
		if (function_exists('JCoreInit'))
			JCoreInit ();

		//=== Registrar los directorios iniciales (El directorio del JCore no se registra)
		static :: addDirectory (COREPATH, 1, 'COREPATH');
		static :: addDirectory (SRCPATH, 999, 'SRCPATH');

		//=== Restaurar el buffer de salida a 1
		while (ob_get_level())
			ob_end_clean();

		ob_start();

		die(__FILE__ . '#' . __LINE__);
//		$JCore = static :: instance();
//		$JCore -> load ('JCA\Compiler');
	}

	public function load (string $component)
	{
		static $_componentes = [];

		if ( ! isset($_componentes[$component]))
		{
			$class = 'JCore' . BS . 'Component' . BS . $component;

			if ( ! class_exists($class))
			{
				trigger_error('Componente ' . $component . ' de JCore no encontrado.', E_USER_WARNING);
				return;
			}

			$_componentes[$component] = $class :: instance ();
		}

		return $_componentes[$component];
	}
}