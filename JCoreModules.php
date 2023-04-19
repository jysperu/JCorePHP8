<?php
/*!
 * autoload.modules.php
 * @filesource
 */
defined('APPPATH') or die('APPPATH no definido');


/** Variables base */
defined('DS') or define ('DS', DIRECTORY_SEPARATOR);
defined('BS') or define ('BS', '\\');


/** Variables de directorios */
defined('JCorePATH') or define('JCorePATH', __DIR__);
defined('ROOTPATH')  or define('ROOTPATH',  APPPATH);
defined('HOMEPATH')  or define('HOMEPATH',  APPPATH);

/** Directorio de cache compartida */
defined('CACHEPATH') or define('CACHEPATH', APPPATH .DS. 'cache');


/** Time & Memory (Execution) */
defined('execution_start_time')   or define('execution_start_time',   microtime(true));
defined('execution_start_memory') or define('execution_start_memory', memory_get_usage());


/** Corrigiendo directorio base */
chdir(APPPATH);


/** Modo mantenimiento */
if (file_exists('maintenance.admin.php'))
	return require_once('maintenance.admin.php');
elseif (file_exists('maintenance.php'))
	return require_once('maintenance.php');


/** Ejecutar la función JCoreInit si existe */
if (function_exists('JCoreInit'))
	JCoreInit ();


/** Agregar directorios BASE */
JCoreModules :: addDirectory(APPPATH, 999, 'APPPATH');
JCoreModules :: addDirectory(JCorePATH, 0, 'JCorePATH');

/** Leer todos los directorios para establecer */
JCoreModules :: loadDirectories();

$modules_directories = JCoreModules :: getDirectories ();

/** Registrar el autoload de la aplicación */
spl_autoload_register(function(string $class) use ($modules_directories) {
	$class = trim($class, BS);
	$parts = explode(BS, $class);
	$nbase = $parts[0];

	/** Buscar en los namespaces definidos */
	$namespaces  = JCoreModules :: getAutoloadsNamespace ();

	if (isset($namespaces[$nbase]))
	{
		$namespace_directory = $namespaces[$nbase];
		array_shift($parts); # Quitar el namespace base

		$filename = null;
		foreach ($modules_directories as $directory)
		{
			$filename = $directory . $namespace_directory  . DS . implode(DS, $parts) . '.php';
			if (file_exists($filename))
				break;
			$filename = null;
		}

		if ( ! is_null($filename))
			require_once $filename;
		return;
	}


	/** Buscar en el directorio classes */
	$namesdirs = JCoreModules :: getAutoloadsDirectories ();

	$filename = null;
	foreach ($modules_directories as $directory)
	{
		foreach ($namesdirs as $namedir)
		{
			$filename = $directory . $namedir  . DS . implode(DS, $parts) . '.php';
			if (file_exists($filename))
				break 2;
			$filename = null;
		}
	}

	if ( ! is_null($filename))
		require_once $filename;
}, true, true);

/** Proteger el REQUEST de todo posible ataque */
XONK :: protect();

/** Escuchar todos los errores generados para poder gestionarlos y corregirlos */
ErrorControl :: listen();

/** Leer el `vendor/autoload.php` */
foreach ($modules_directories as $directory)
	if ($file = $directory . DS . 'vendor' . DS . 'autoload.php' and file_exists($file))
		require_once $file;

/** Leer el `configs/functions/*.php` */
foreach ($modules_directories as $directory)
{
	$files = glob($directory . DS . 'configs' . DS . 'functions' . DS . '*.php');
	foreach ($files as $file)
		require_once $file;
}

/** Instanciar APP */
APP :: instance();


/** Registrar el punto de inicio */
BenchMark :: registerGlobalPoint();


/** Carga las validaciones asíncronas */
XONK :: prepareAsyncValidations();


/** Leer el `configs/init.php` */
foreach ($modules_directories as $directory)
	if ($file = $directory . DS . 'configs' . DS . 'init.php' and file_exists($file))
		require_once $file;


/** Ejecutar las validaciones de autenticación */
foreach ($modules_directories as $directory)
	if ($file = $directory . DS . 'configs' . DS . 'auth.php' and file_exists($file))
		require_once $file;

APP :: $PREREQUESTS_CLASSES = array_keys(JCoreModules :: $AUTOLOAD_ROUTES);


/**
 * JCoreModules
 * Una clase configurable que permite auto-cargar las clases de forma rápida
 * leyendo todos los directorios de los módulos
 */
class JCoreModules
{
	//=================================================================================//
	//==== VARIABLES ESTÁTICAS PÚBLICAS - Autoloads y directorios                 =====//
	//=================================================================================//

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
	 * $AUTOLOAD_DRIVERS_DIR
	 * Aloja el directorio para el namespace `Driver`
	 * (Procesos internos)
	 */
	public static $AUTOLOAD_DRIVERS_DIR = '/drivers';

	/**
	 * $AUTOLOAD_PROCESSES_DIR
	 * Aloja el directorio para el namespace `Proceso`
	 * (Procesos internos)
	 */
	public static $AUTOLOAD_PROCESSES_DIR = '/procesos';

	/**
	 * $AUTOLOAD_MODELS_DIR
	 * Aloja el directorio para el namespace `Modelo`
	 * (Procesos internos)
	 */
	public static $AUTOLOAD_MODELS_DIR = '/modelos';

	/**
	 * $AUTOLOAD_OBJECTS_DIR
	 * Aloja el directorio para el namespace `Objeto`
	 * (Procesos internos)
	 */
	public static $AUTOLOAD_OBJECTS_DIR = '/objetos';

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
		### Posibles traits añadidas
		'/traits',

		### Posibles clases añadidas
		'/classes',
		'/configs/classes',

		### Posibles librerías añadidas
		'/libs',
		'/configs/libs',
	];

	/**
	 * $INSTALL_FILES_AND_DIRS
	 * Se enlista todos los archivos que serán clonados para la carpeta de instalación
	 * El compilado ejecuta la función glob para copiar los archivos y directorios coincidentes en cada carpeta de aplicación
	 * Si hay duplicidad, serán reemplazados
	 */
	public static $INSTALL_FILES_AND_DIRS = [
		'/install',
	];


	//=================================================================================//
	//==== VARIABLES ESTÁTICAS PROTEGIDAS - Información de Directorios Fuente     =====//
	//=================================================================================//

	/**
	 * $_directories_by_priority
	 * Aloja todas los directorios de aplicaciones con la llave primaria equivalente a la prioridad asignada
	 */
	protected static $_directories_by_priority = [];

	/**
	 * $_directories_orden
	 * Aloja todas los directorios como llaves y el orden como valor
	 * Ayuda a identificar si se ha cargado ya un directorio de aplicación no esté en mas de 1 orden
	 */
	protected static $_directories_orden = [];

	/**
	 * $_directories_label
	 * Aloja todas los directorios en el orden generado por la prioridad
	 */
	protected static $_directories_label = [];

	/**
	 * $_directories_ordered_list
	 * Aloja todas los directorios en el orden generado por la prioridad
	 */
	protected static $_directories_ordered_list = [];

	/**
	 * $_directories_recents_added
	 * Aloja todas los nuevos directorios agregados
	 * Ayuda a identificar si ha habido nuevos directorios agregados previo a un proceso
	 */
	protected static $_directories_recents_added = [];


	//=================================================================================//
	//==== FUNCIONES - Gestión de Directorios Fuente                              =====//
	//=================================================================================//

	/** loadDirectories()
	 */
	public static function loadDirectories ()
	{
		static $_loaded = false;

		if ($_loaded)
			return $this;
		$_loaded = true;


		$revisados = [];

		do
		{
			$procesados  = 0;
			$directories = JCoreModules :: getDirectories ();

			foreach ($directories as $directory)
			{
				if (in_array($directory, $revisados))
					continue; # Ya ha sido revisado

				$revisados[] = $directory;

				$load_php    = $directory . DS . 'load.php';
				if ( ! file_exists($load_php))
					continue; # No hay archivo load.php en el directorio

				try
				{
					require_once $load_php;
				}
				catch (Exception $e)
				{}
				finally
				{
					$procesados++;
				}
			}
		}
		while($procesados > 0);

		unset($procesados, $revisados);
	}

	/**
	 * addDirectory ()
	 * Función que permite añadir directorios de aplicación las cuales serán usados para buscar y procesar 
	 * la información para la solicitud del usuario
	 *
	 * @param String	$directory Directorio a añadir
	 * @param Integer	$prioridad Prioridad de lectura del directorio
	 * @param String	$label Etiqueta del directorio
	 * @return void
	 */
	public static function addDirectory (string $directory, int $prioridad = 500, string $label = null):void
	{
		//=== Validar la existencia del directorio
		$directory = static :: regularizeDirectory ($directory);

		if (is_null($directory))
			return; # La ruta es inválida o no existe

		//== Guardar la etiqueta del directorio (En caso exista se actualiza)
		is_null($label) and 
		$label = $directory;

		static :: $_directories_label[$directory] = $label;

		//=== Comprobar la existencia del directorio
		if ( ! isset(static :: $_directories_orden[$directory]))
		{
			$existencia = 0; # No existe
		}
		elseif ($prioridad_actual = static :: $_directories_orden[$directory] and $prioridad_actual !== $prioridad)
		{
			$existencia = 1; # Existe pero el órden es diferente
		}
		else
		{
			return; # Existe y es el mismo orden
		}

		//=== Existe en un órden diferente así que eliminarlo de esa órden
		if ($existencia === 1 and ($index = array_search($directory, static :: $_directories_by_priority[$prioridad_actual])) !== false)
		{
			unset(static :: $_directories_by_priority[$prioridad_actual][$index]);
			static :: $_directories_by_priority[$prioridad_actual] = array_values(static :: $_directories_by_priority[$prioridad_actual]);
		}

		//=== Validando que la lista de la prioridad exista
		isset(static :: $_directories_by_priority[$prioridad]) or
		static :: $_directories_by_priority[$prioridad] = [];

		//=== Añadiendo el directorio a la orden
		static :: $_directories_by_priority[$prioridad][] = $directory;
		static :: $_directories_orden[$directory]         = $prioridad;
		static :: $_directories_recents_added[]           = $directory;

		//=== Cachear lista (Considerar que de prioridad mas alto es el número mayor)
		$_directories_ordered_list = [];

		$_directories_by_priority  = static :: $_directories_by_priority;
		ksort($_directories_by_priority);

		foreach($_directories_by_priority as $prioridad => $_directories)
		{
			foreach($_directories as $_directory)
			{
				$_directories_ordered_list[] = $_directory;
			}
		}

		$_directories_ordered_list = array_reverse($_directories_ordered_list); # Invertir ya que el de mayor número es prioritario

		static :: $_directories_ordered_list = $_directories_ordered_list;
	}

	/**
	 * getDirectories ()
	 * Función que retorna los directorios de aplicación
	 *
	 * @param Boolean	$prioridad_menor_primero Indica si se retornará la lista de manera invertida
	 * @return Array
	 */
	public static function getDirectories (bool $prioridad_menor_primero = FALSE):array
	{
		$lista = static :: $_directories_ordered_list;

		$prioridad_menor_primero and
		$lista = array_reverse($lista);

		return $lista;
	}

	/**
	 * mapDirectories ()
	 * Función que ejecuta una función establecida con todos los directorios de aplicación como parametro
	 *
	 * @param Callable	$callback Función a ejecutar
	 * @param Boolean	$prioridad_menor_primero Indica si la función a ejecutar se hará a la lista invertida
	 * @return Array
	 */
	public static function mapDirectories (callable $callback, bool $prioridad_menor_primero = FALSE):array
	{
		$lista = static :: getDirectories ($prioridad_menor_primero);
		return array_map($callback, $lista);
	}

	/**
	 * getDirectoriesLabels ()
	 * Función que retorna los directorios y sus nombres
	 *
	 * @param Boolean	$prioridad_menor_primero Indica si se retornará la lista de manera invertida
	 * @return Array
	 */
	public static function getDirectoriesLabels (bool $prioridad_menor_primero = FALSE):array
	{
		$lista  = static :: $_directories_ordered_list;
		$labels = static :: $_directories_label;
		
		$prioridad_menor_primero and 
		$lista = array_reverse($lista);

		$lista = array_combine($lista, array_map(function($o) use ($labels) {
			return $labels[$o];
		}, $lista));

		return $lista;
	}

	/**
	 * thereIsRecentsAddedDirectories ()
	 * Indica si hay algún directorio recientemente añadido
	 *
	 * @param Boolean	$clean Limpia el historial de añadidos recientemente
	 * @return Boolean
	 */
	public static function thereIsRecentsAddedDirectories (bool $clean = false):bool
	{
		$valor = count(static :: $_directories_recents_added);
		$clean and static :: $_directories_recents_added = [];
		return $valor;
	}



	public static function getInitialDirectories ():array
	{
		static $_dirs;
		isset ($_dirs) or $_dirs = static :: getDirectories();
		return $_dirs;
	}

	public static function getAutoloadsNamespace ():array
	{
		return array_merge (
			(array) static :: $AUTOLOAD_NAMESPACES,
			(array) static :: $AUTOLOAD_ROUTES,

			[
				'Request'   => (string) static :: $AUTOLOAD_REQUEST_DIR,
				'Response'  => (string) static :: $AUTOLOAD_RESPONSE_DIR,
				'Structure' => (string) static :: $AUTOLOAD_STRUCTURE_DIR,
				'Driver'    => (string) static :: $AUTOLOAD_DRIVERS_DIR,
				'Proceso'   => (string) static :: $AUTOLOAD_PROCESSES_DIR,
				'Modelo'    => (string) static :: $AUTOLOAD_MODELS_DIR,
				'Objeto'    => (string) static :: $AUTOLOAD_OBJECTS_DIR,
			],

			[]
		);
	}

	public static function getAutoloadsDirectories ():array
	{
		return array_merge (
			(array) static :: $AUTOLOAD_DIRS,

			[]
		);
	}

	public static function regularizeDirectory (string $ruta)
	{
		if (($_temp = realpath($ruta)) !== FALSE)
		{
			$ruta = $_temp;
		}
		else
		{
			$ruta = strtr(
				rtrim($ruta, '/\\'),
				'/\\',
				DS . DS
			);
		}

		if ( ! file_exists($ruta) ||  ! is_dir($ruta))
		{
			return null;
		}

		return $ruta;
	}

	public static function getFilesOnDir (string $directorio):array
	{
		if ( ! file_exists($directorio) or ! is_dir($directorio))
			return [];

		$data = scandir($directorio);
		$data = array_filter($data, function($o){
			return ! in_array($o, ['.', '..']);
		});
		$data = array_values($data);

		$data = array_map(function($o) use ($directorio) {
			return $directorio . DS . $o;
		}, $data);

		$files = array_filter($data, function ($o) {
			return ! is_dir($o);
		});
		$files = array_values($files);

		$dirs = array_filter($data, function ($o) {
			return is_dir($o);
		});
		$dirs = array_values($dirs);

		foreach ($dirs as $dir)
		{
			$files = array_merge($files, static :: getFilesOnDir($dir));
		}

		$files = array_unique($files);
		$files = array_values($files);

		return $files;
	}
}