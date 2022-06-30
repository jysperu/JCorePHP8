<?php
/**
 * JCore.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Acceso directo no autorizado

use JCore\ComponenteTrait;
use JCore\Controller\Directories as DirectoriesTrait;
use JCore\Controller\Command 	 as CommandTrait;

/**
 * JCore
 *
 * Ciclo del Request:
 * 01.	Proceso Inicial
 * |	Se ejecuta el la función `JCoreInit` en caso exista y se realiza configuración inicial básica
 *
 * 02.	Lee el módulo `XONK`
 * | 	Protege el Requests de posibles intentos de hackeo
 *
 * 03.	Lee el módulo `ErrorControl`
 * |	Controla todo tipo de error y/o exception que se genere en el flujo del sistema
 *
 * 04.	Lee el módulo `JCA\Compiler`
 * |	Encargado de compilar toda la aplicación
 *
 * 05.	Lee el módulo `JCA\Processor`
 * |	Encargado de procesar el request usando la aplicación compilada
 */
class JCore
{
	use ComponenteTrait;
	use DirectoriesTrait;
	use CommandTrait;

	/**
	 * $JCA_PATH
	 * La constanste `JCA_PATH` guarda la ruta del "JCore Compiled Aplication"
	 * Se puede definir incluso previo a leer esta clase
	 * En caso aún no haya sido definida, se tomará este valor como ruta por defecto
	 * Si este valor es NULO entonces se tomará como ruta `APPPATH/$compiled`
	 */
	public static $JCA_PATH = NULL;

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
	//==== VARIABLES ESTÁTICAS — XONK                                             =====//
	//=================================================================================//

	/**
	 * $COOKIE4_DEVICE
	 * Cookie en el cual se aloja el identificador del dispositivo
	 */
	public static $COOKIE4_DEVICE = 'cdkdsp';

	//=================================================================================//
	//==== VARIABLES ESTÁTICAS — SesionManager                                    =====//
	//=================================================================================//

	/** $SESSION_NAME */
	public static $SESSION_NAME = 'JCore';

	/**
	 * $DIR4_SESSION
	 * Directorio donde se alojarán los archivos de sesión
	 *
	 * Los $DIR4_X se encontrarán dentro de la carpeta JCA_PATH 
	 * (añadir slash al inicio, omitirlo al final)
	 */
	public static $DIR4_SESSION = '/tmpdata/sesiones';

	//=================================================================================//
	//==== VARIABLES ESTÁTICAS — CacheManager                                     =====//
	//=================================================================================//

	/**
	 * $DIR4_SESSION
	 * Directorio donde se alojarán los archivos de cache
	 *
	 * Los $DIR4_X se encontrarán dentro de la carpeta JCA_PATH 
	 * (añadir slash al inicio, omitirlo al final)
	 */
	public static $DIR4_CACHE   = '/tmpdata/cache';

	//=================================================================================//
	//==== CONSTRUCTORES                                                          =====//
	//=================================================================================//

	/**
	 * __construct()
	 */
	final protected function __construct ()
	{
		//=== Ejecutar la función JCoreInit si existe
		if (function_exists('JCoreInit'))
			JCoreInit ($this);

		//=== Declaración de variables
		defined('DS') or define('DS', DIRECTORY_SEPARATOR);
		defined('BS') or define('BS', '\\');
		defined('HOMEPATH') or define('HOMEPATH', APPPATH);
		defined('COREPATH') or define('COREPATH', APPPATH);

		//=== Definiendo la RUTA PARA
		defined('JCA_PATH') or 
		define ('JCA_PATH', static :: $JCA_PATH ?? APPPATH . DS . '$compiled');

		//=== Comprobando si es comando
		defined('ISCOMMAND') or 
		define ('ISCOMMAND', static :: isCommand());

		//=== Registrar los directorios iniciales (El directorio del JCore no se registra)
		defined('COREPATH') and static :: addDirectory (COREPATH, 1, 'COREPATH');
		defined('APPPATH')  and static :: addDirectory (APPPATH, 999, 'APPPATH');

		//=== Restaurar el buffer de salida a 1
		while (ob_get_level())
			ob_end_clean();

		ob_start();
	}

	/**
	 * init ()
	 * Procesa el núcleo y el Request
	 */
	protected function init():JCore
	{
		$this -> load ('XONK');          # WAF (Si es COMANDO no filtra las conecciones)
		$this -> load ('ErrorControl');  # Control de Errores
		$this -> load ('JCA\Compiler');  # Compila y lee la metadata del JCore Compiled Aplication (JCA)
		$this -> load ('JCA\Processor'); # Procesa el requests usando el JCore Compiled Aplication (JCA)

		return $this;
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

	//=================================================================================//
	//==== DIRECTORIOS DE APLICACIÓN                                              =====//
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

	/**
	 * addDirectory ()
	 * Función que permite añadir directorios de aplicación las cuales serán usados para buscar y procesar 
	 * la información para la solicitud del usuario
	 *
	 * @param String $directory 	Directorio a añadir
	 * @param Integer $prioridad	Prioridad de lectura del directorio
	 * @return self
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
	 * @param Boolean $prioridad_menor_primero Indica si se retornará la lista de manera invertida
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
	 * @param Callable $callback Función a ejecutar
	 * @param Boolean $prioridad_menor_primero Indica si la función a ejecutar se hará a la lista invertida
	 * @return self
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
	 * @param $prioridad_menor_primero Boolean Indica si se retornará la lista de manera invertida
	 * @return Array
	 */
	public static function getDirectoriesLabels ($prioridad_menor_primero = FALSE):array
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

	public static function thereIsRecentsAddedDirectories (bool $clean = false):bool
	{
		$valor = count(static :: $_directories_recents_added);
		$clean and static :: $_directories_recents_added = [];
		return $valor;
	}

	public static function getJCoreDir ():string
	{
		return __DIR__;
	}
}