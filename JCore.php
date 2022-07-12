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
use JCore\JPart\Compilador  as CompiladorPart;

class JCore
{
	use DirectoriesPart;
	use CompiladorPart;

	//=================================================================================//
	//==== VARIABLES ESTÁTICAS — JCA\*                                            =====//
	//=================================================================================//

	public const JCA_json = APPPATH . DS . 'jca.json';

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
	//==== FUNCIONES DISPONIBLES                                                  =====//
	//=================================================================================//

	/**
	 * init ()
	 * Procesa el núcleo y el Request
	 */
	public static function compile($by = null)
	{
		is_null ($by) and $by = static :: requiereCompilar();

		//=== Registrar los directorios iniciales (El directorio del JCore no se registra)
		static :: addDirectory (COREPATH, 1, 'COREPATH');
		static :: addDirectory (SRCPATH, 999, 'SRCPATH');

		//=== Ejecutar la función JCoreInit si existe
		if (function_exists('JCoreInit'))
			JCoreInit ();

		//=== Restaurar el buffer de salida a 1
		while (ob_get_level())
			ob_end_clean();

		ob_start();

		//=== Prevenir que el requests se caiga y no se complete la compilación
		ignore_user_abort(true);
		set_time_limit(0);

		//=== Si no existe la carpeta crearla
		file_exists(APPPATH) or
		mkdir(APPPATH, 0777, true);

		//=== Estableciendo el modo mantenimiento
		static :: maintenance('<b>Compilando...</b><br>Se ha iniciado la compilación del JCA.', 10); # Máximo 10s

		//=== Creando la variable donde alojara toda la metadata
		$json = [];
		$json['$C'] = [
			'B'	=> $by,
			'T' => filemtime(__FILE__),
			'S' => [microtime(true), memory_get_usage()],
			'E' => [microtime(true), memory_get_usage()],
		];

		//=== Añadiendo los atributos INITIAL_DIRECTORIES y MD5_INITDIRS
		$json['INITIAL_DIRECTORIES'] = static :: getInitialDirectories ();
		$json['MD5_INITDIRS']        = md5(json_encode($json['INITIAL_DIRECTORIES']));

		//=== Recorrer todos los directorios en busqueda del archivo `load.php`
		static :: compileLoads ();

		//=== Establecer el atributo DIRECTORIES_NAMES
		$json['DIRECTORIES_NAMES'] = static :: getDirectoriesLabels();

		//=== Establecer el mtime de los directosios leídos
		static :: compileDirectoriesMtime ($json);

		//=== Considerando solo los verdaderos directorios que existen y son directorios
		$directories_999_1 = array_keys($json['DIRECTORIES_MTIME']); # Directorios de mayor prioridad primero (Permite anticipar funciones)
		$directories_1_999 = array_reverse($directories_999_1);      # Directorios de menor prioridad primero (Permite sobreescribir configuraciones o clases)

		//=== Estableciendo el atributo MD5_JCORECNFG utilizando los datos originales de AUTOLOAD_NAMESPACES y AUTOLOAD_DIRS
		$AUTOLOAD_NAMESPACES   = static :: getAutoloadsNamespace ();
		$AUTOLOAD_DIRS		   = static :: getAutoloadsDirectories ();
		$COMPILER_EXTRA_DIRS   = static :: getDirectoriesToCompile ();

		$json['MD5_JCORECNFG'] = md5(json_encode([
			$AUTOLOAD_NAMESPACES, 
			$AUTOLOAD_DIRS, 
			$COMPILER_EXTRA_DIRS,
		]));

		$AUTOLOAD_NAMESPACES_flip = array_flip  ($AUTOLOAD_NAMESPACES);
		$AUTOLOAD_NAMESPACES_dirs = array_values($AUTOLOAD_NAMESPACES);

		//=== Copiar todos los directorios
		static :: compileDirectoriesCopy ($AUTOLOAD_NAMESPACES, $AUTOLOAD_DIRS, $COMPILER_EXTRA_DIRS, $AUTOLOAD_NAMESPACES_dirs, $directories_1_999);

		$PREREQUESTS_CLASSES = array_keys(static :: $AUTOLOAD_ROUTES);
		$PREREQUESTS_CLASSES = array_filter($PREREQUESTS_CLASSES, function ($clase) use ($AUTOLOAD_NAMESPACES) {
			return isset($AUTOLOAD_NAMESPACES[$clase]);
		});
		$PREREQUESTS_CLASSES = array_values($PREREQUESTS_CLASSES);

		$json['AUTOLOAD_NAMESPACES'] = $AUTOLOAD_NAMESPACES;
		$json['PREREQUESTS_CLASSES'] = $PREREQUESTS_CLASSES;

		static :: cleanDirectory (APPPATH . DS . 'configs');

		//=== Compilar $config
		static :: compileConfig ($directories_1_999);

		//=== Compilar configs/init.php
		static :: compileInit ($directories_1_999);

		//=== Unir funciones en configs/functions.php (Todas las funciones deben tener if (function_exists) para preveneri errores)
		static :: compileFunctions ($directories_999_1);

		//=== Compilar composer.json
		static :: compileComposer ($directories_1_999);

		//=== Compilar index.php
		static :: compileIndex ($json);

		//=== Compilar Conecciones de Bases de Datos y sus Drivers
		//=== (incluír la conección a la DB para el XONK y para el ErrorControl)
		//


		//=== Las funciones de base datos (sql y sql_data) utilizan el driver de la db principal
		//

		//== Guardando archivo de metadata
		file_put_contents(static :: JCA_json, json_encode($json));

		static :: maintenanceRemove();
	}

	public static function maintenanceRemove ():void
	{
		if ( ! file_exists(APPPATH))
			return;

		$file = APPPATH . DS . 'maintenance.php';

		if ( ! file_exists($file))
			return;

		@unlink($file);
		return;
	}

	public static function maintenance ($html, int $segundos = null)
	{
		if ( ! file_exists(APPPATH))
			return;

		$file = APPPATH . DS . 'maintenance.php';
		$content = '<?' . 'php' . PHP_EOL;
		$content.= '# Setted at ' . date('Y-m-d h:i:s A') . PHP_EOL;

		if ( ! is_null($segundos))
		{
			$time = time() + $segundos;
			$content.= '$diff = ' . $time . ' - time();' . PHP_EOL;
			$content.= 'if ($diff < 0)' . PHP_EOL;
			$content.= '	@unlink(__FILE__);' . PHP_EOL;
		}

		$content.= '?>' . PHP_EOL . $html;
		$content.= '<?' . 'php echo \'<br><small>Tiempo Restante: \', (string) $diff, \'s</small><script>setTimeout(function(){location.reload()}, 750);</script>\';';

		file_put_contents(APPPATH . DS . 'maintenance.php', $content);

		if ( ! file_exists(APPPATH . DS . 'index.php'))
		{
			file_put_contents(APPPATH . DS . 'index.php', '<?' . 'php $maintenance = __DIR__ . \'/maintenance.php\'; if ( ! file_exists($maintenance)) { unlink(__FILE__); return; } require_once($maintenance);');
		}
	}
}