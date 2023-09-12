<?php
/*!
 * autoload.php
 * @filesource
 */

defined('APPPATH') or die('APPPATH no definido');

if (defined(md5(__FILE__))) return; define(md5(__FILE__), __FILE__);

/**
 * DS
 * @internal
 * Separador de directorio
 */
defined('DS') or define ('DS', DIRECTORY_SEPARATOR);

/**
 * BS
 * @internal
 * Caracter BackSlash
 */
defined('BS') or define ('BS', '\\');

/**
 * JCorePATH
 * @internal
 * Directorio del núcleo JCore
 */
defined('JCorePATH') or define('JCorePATH', __DIR__);

/**
 * ROOTPATH
 * @internal
 * Directorio donde se pueden encontrar multiples carpetas de 
 * aplicaciones relacaionadas
 */
defined('ROOTPATH')  or define('ROOTPATH',  APPPATH);

/**
 * HOMEPATH
 * @internal
 * Directorio público, normalmente es la carpeta visible desde
 * la ruta web
 */
defined('HOMEPATH')  or define('HOMEPATH',  APPPATH);

/**
 * CACHEPATH
 * @internal
 * Directorio en el cual se encuentran los archivos del gestor 
 * de cache por defecto
 */
defined('CACHEPATH') or define('CACHEPATH', ROOTPATH . '/cache');

/**
 * exec_start_time
 * @internal
 * Valor utilizado para testear tiempos de procesos
 */
defined('exec_start_time')   or define('exec_start_time',   microtime(true));

/**
 * exec_start_memory
 * @internal
 * Valor utilizado para testear memoria utilizada
 */
defined('exec_start_memory') or define('exec_start_memory', memory_get_usage());

/** Registrar los directorios por defecto */
JModules :: addModule (JCorePATH, 0, 'JCorePATH');
JModules :: addModule (APPPATH, 999, 'APPPATH');

/** Registrar el autoload de la aplicación */
$modules_directories  = JModules :: getDirectories ();
$autoload_namespaces  = JConfig  :: getAutoloadNamespaces ();
$autoload_directories = JConfig  :: getAutoloadDirectories ();

spl_autoload_register(function(string $class) use ($modules_directories, $autoload_namespaces, $autoload_directories) {
	$class = trim($class, BS);
	$parts = explode(BS, $class);
	$nbase = $parts[0];

	/** Buscar la clase basado en el namespace */
	if (isset($autoload_namespaces[$nbase]))
	{
		$namespace_directory = $autoload_namespaces[$nbase];
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

	/** Buscar la clase dentro de los directorios adicionales */
	$filename = null;
	foreach ($modules_directories as $directory)
	{
		foreach ($autoload_directories as $namedir)
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

/**
 * routes_nmsps
 * @internal
 * Variable que indica todos los routes a pasar antes del Request
 */
$ROUTES_namespaces    = array_keys ((array) JConfig  :: $ROUTES);

defined('routes_nmsps') or define('routes_nmsps', implode('|', $ROUTES_namespaces));

/** Leer el `vendor/autoload.php` */
foreach ($modules_directories as $directory)
	if ($file = $directory .DS. 'vendor' .DS. 'autoload.php' and file_exists($file))
		require_once $file;

/** Leer todos los archivos `configs/functions/*.php` */
foreach ($modules_directories as $directory)
{
	$files = glob($directory .DS. 'configs' .DS. 'functions' .DS. '*.php');
	foreach ($files as $file)
		require_once $file;
}


/**
 * JModules
 * Clase que gestiona los módulos que se utilizarán en la aplicación
 */
class JModules
{
	/**
	 * $_directories_labels
	 * Aloja la etiqueta de cada directorio
	 *
	 * {
	 *    string => string,
	 *    'path' => 'label',
	 * }
	 */
	protected static $_directories_labels = [];

	/**
	 * $_directories_orders
	 * Aloja el nro de orden de cada directorio
	 *
	 * {
	 *    string => int,
	 *    'path' => order,
	 * }
	 */
	protected static $_directories_orders = [];

	/**
	 * $_order_directories
	 * Aloja todas los directorios en un arreglo de datos
	 * teniendo el key al valor $order
	 *
	 * {
	 *    int => string[],
	 *    order => [ 'path', ... ],
	 * }
	 */
	protected static $_order_directories = [];

	/**
	 * $_recently_added
	 * Arreglo que aloja todos los directorios añadidos recientemente
	 *
	 * string[]
	 */
	protected static $_recently_added = [];

	/**
	 * $_ordered_directories
	 * Listado de directorios ordenados según sus respectivos $order
	 *
	 * string[]
	 */
	protected static $_ordered_directories = [];

	/**
	 * addModule()
	 * Permite agregar un nuevo directorio que aloja todos los archivos de los módulos
	 *
	 * @param string $directory
	 * @param int|string $order	En caso el valor enviado sea string se considera como parametro $label
	 * @param string $label
	 * @param callable $onend	Función a llamar al finalizar el 
	 * @throws JModulesException
	 * @return void
	 */
	public static function addModule (string $directory, int | string $order = 500, ? string $label = null, ? callable $onend = null): void
	{
		$directory = static :: realDirectory ($directory);

		if (is_null($directory))
			throw new JModulesException ('Directorio `' . $directory . '` no existe');

		is_null($label) and is_string($order) and $label = $order and $order = 500;
		is_null($label) and $label = str_replace(ROOTPATH, '[ROOTPATH]', $directory);


		/** Agregar/Actualizar la etiqueta del directorio */
		static :: $_directories_labels [$directory] = $label;

		/** Agregar el arreglo de la $order en caso no existir */
		isset(static :: $_order_directories[$order]) or static :: $_order_directories[$order] = [];

		/** Validar si ya se ha agregado el directorio del módulo */
		if (isset(static :: $_directories_orders [$directory]))
		{
			if ($actual_order = static :: $_directories_orders[$directory] and $actual_order === $order)
				return; ## El directorio está agregado y el $order es el mismo

			## Eliminar el directorio en el orden actual para que se pueda registrar en el órden nuevo
			$index = array_search($directory, static :: $_order_directories[$actual_order]);

			if ($index === false) ## no debe suceder
				throw new JModulesException ('Se produjo un error inesperado.');

			unset(static :: $_order_directories[$actual_order][$index]);
			static :: $_order_directories[$actual_order] = array_values(static :: $_order_directories[$actual_order]);
		}

		/** Añadiendo los valores $directory y $order en las respectivas variables */
		static :: $_order_directories[$order][]    = $directory;
		static :: $_directories_orders[$directory] = $order;

		/** cachearDirectories() */ 
		static :: cachearDirectories ();

		/** Se añade el $directory como recién añadido para que el sistema pueda procesarlo en busqueda de nuevos módulos */
		static :: $_recently_added[] = $directory;

		if ( ! is_null($onend))
			call_user_func_array($onend, [ $directory, $order ]);

		/** Si existe el archivo `modules.php` dentro del directorio se lee */
		if ($modules_file = $directory .DS. 'modules.php' and file_exists($modules_file))
		{
			try
			{
				require_once $modules_file;
			}
			catch (Throwable $ex)
			{
				throw new JModulesException ($ex -> getMessage(), $ex -> getCode(), $ex);
			}
		}
	}

	/**
	 * realDirectory()
	 * Obtener la ruta real
	 *
	 * @param string $ruta
	 * @return string|null
	 */
	public static function realDirectory (string $ruta): ? string
	{
		$tmp  = realpath($ruta);
		$ruta = $tmp !== false ? $tmp : strtr( rtrim($ruta, '/' . BS), '/' . BS, DS . DS);

		if ( ! file_exists($ruta) || ! is_dir($ruta))
			return null;

		return $ruta;
	}

	/**
	 * cachearDirectories()
	 * Cachear las variables relacionadas a los directorios de los módulos
	 *
	 * @return void
	 */
	public static function cachearDirectories (): void
	{
		//=== Cachear lista (Considerar que de order mas alto es el número mayor)
		$lista = [];

		$_order_directories = static :: $_order_directories;
		ksort($_order_directories);

		foreach($_order_directories as $order => $_directories)
			foreach($_directories as $_directory)
				$lista[] = $_directory;

		$lista = array_reverse($lista); ## Invertir ya que el de mayor $order es prioritario

		static :: $_ordered_directories = $lista;
	}

	/**
	 * thereIsRecentlyAdded()
	 * Existe algún directorio añadido recientemente añadido
	 */
	public static function thereIsRecentlyAdded (bool $clean = false): bool
	{
		$total = count(static :: $_recently_added);
		$clean and static :: $_recently_added = [];
		return $total;
	}

	/**
	 * getDirectories ()
	 * Función que retorna los directorios establecidos
	 *
	 * @param boolean $order_men_may Indica si se retornará la lista de menor a mayor
	 * @return array
	 */
	public static function getDirectories (bool $order_men_may = false): array
	{
		$lista = static :: $_ordered_directories;
		$order_men_may and $lista = array_reverse($lista);

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
	public static function mapDirectories (callable $callback, bool $order_men_may = false): array
	{
		$lista = static :: getDirectories ($order_men_may);
		return array_map($callback, $lista);
	}

	/**
	 * getDirectoriesLabels ()
	 * Función que retorna los directorios y sus nombres
	 *
	 * @param boolean $order_men_may Indica si se retornará la lista de manera invertida
	 * @return array
	 */
	public static function getDirectoriesLabels (bool $order_men_may = false): array
	{
		$labels = static :: $_directories_label;

		$lista  = static :: $_directories_ordered_list;
		$order_men_may and $lista = array_reverse($lista);

		$lista = array_combine($lista, array_map(function($o) use ($labels) {
			return $labels[$o];
		}, $lista));

		return $lista;
	}
}

/**
 * JConfig
 * Clase que gestiona la configuración a nivel de compilación
 */
class JConfig
{
	/**
	 * $ROUTES
	 * Aloja los namespaces y su directorio para las clases pre-procesadoras de los REQUEST.
	 * Permiten cambiar el URI (Route).
	 *
	 * > El orden indicado es el modo con el cual serán leído.
	 *
	 * {
	 *     'Namespace' => '/directory',
	 * }
	 */
	public static $ROUTES = [
		'ObjRoute'   => '/objroutes',   # Permite comprobar la existencia de los objetos con las IDs recibidas
		'ReRoute'    => '/reroutes',    # Permite modificar la URI por otra que se haya enmascarado
		'AlwRoute'   => '/alwroutes',   # Permite comprobar permisos del usuario logueado
		'PreRequest' => '/prerequests', # Permite ejecutar alguna acción previo al proceso oficila del request
	];

	/**
	 * $REQUEST_DIR
	 * Aloja el directorio para el namespace `Request`
	 * (Procesador del Request)
	 */
	public static $REQUEST_DIR = '/requests';

	/**
	 * $RESPONSE_DIR
	 * Aloja el directorio para el namespace `Response`
	 * (Pantallas HTML)
	 */
	public static $RESPONSE_DIR = '/responses';

	/**
	 * $STRUCTURE_DIR
	 * Aloja el directorio para el namespace `Structure`
	 * (Estructuras de las Pantallas HTML)
	 */
	public static $STRUCTURE_DIR = '/structures';

	/**
	 * $MODELS_DIR
	 * Aloja el directorio para el namespace `Modelo`
	 */
	public static $MODELS_DIR = '/models';

	/**
	 * $DRIVERS_DIR
	 * Aloja el directorio para el namespace `Driver`
	 */
	public static $DRIVERS_DIR = '/drivers';

	/**
	 * $PROCESS_DIR
	 * Aloja el directorio para el namespace `Proceso`
	 */
	public static $PROCESS_DIR = '/processes';

	/**
	 * $OBJECTS_DIR
	 * Aloja el directorio para el namespace `Objeto`
	 */
	public static $OBJECTS_DIR = '/objects';

	/**
	 * $NAMESPACES
	 * Aloja múltiples namespaces y su directorio respectivo
	 *
	 * {
	 *     'Namespace' => '/Directory',
	 * }
	 */
	public static $NAMESPACES = [];

	/**
	 * $CLASS_DIRS
	 * Aloja múltiples directorio en la cual poder buscar algunas clases solicitadas
	 * Todos al ser compilados serán copiados en un solo directorio
	 */
	public static $CLASS_DIRS = [
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
	 * $INSTALL_DIR
	 * Directorio donde se encuentra alojado los archivos de instalación
	 */
	public static $INSTALL_DIR = '/install';

	/**
	 * getAutoloadNamespaces()
	 * Retorna el conjunto de clases y los directorios en el que se encuentran alojados
	 */
	public static function getAutoloadNamespaces (): array
	{
		return array_merge (
			(array) static :: $NAMESPACES,
			(array) static :: $ROUTES,

			[
				'Request'   => (string) static :: $REQUEST_DIR,
				'Response'  => (string) static :: $RESPONSE_DIR,
				'Structure' => (string) static :: $STRUCTURE_DIR,
				'Modelo'    => (string) static :: $MODELS_DIR,
				'Driver'    => (string) static :: $DRIVERS_DIR,
				'Proceso'   => (string) static :: $PROCESS_DIR,
				'Objeto'    => (string) static :: $OBJECTS_DIR,
			],

			[]
		);
	}

	/**
	 * getAutoloadDirectories()
	 * Retorna los directorios donde pueden aparecer mas clases
	 */
	public static function getAutoloadDirectories ():array
	{
		return array_merge (
			(array) static :: $CLASS_DIRS,

			[]
		);
	}
}

/**
 * JModulesException
 * Exception diferencial
 */
class JModulesException extends Exception
{}