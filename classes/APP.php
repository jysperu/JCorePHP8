<?php
/*!
 * classes/APP.php
 * @filesource
 */
defined('APPPATH') or exit(0); ## Acceso directo no autorizado

/**
 * APP
 * Núcleo del sistema
 */
defined('APPNAME') or define('APPNAME', 'JCoreAPP');

defined('DS') or define('DS', DIRECTORY_SEPARATOR);
defined('BS') or define('BS', '\\');

class APP extends JArray
{
	## Declaración de los Traits a usar
	use Intanceable;
	use APP\Helper;
	use APP\Config;
	use APP\Session;
	use APP\URI;
	use APP\Cache;
	use APP\Proceso;
	use APP\Response;

	## Declaración de las constantes requeridas por los traits
	const ResponseTypeHtml   = 'html';
	const ResponseTypeBody   = 'body';
	const ResponseTypeJson   = 'json';
	const ResponseTypeCli    = 'cli';
	const ResponseTypeManual = 'manual';
	const ResponseTypeFile   = 'file';
	const ResponseTypeEventStream = 'event_stream'; ## Para eventsource

	/**
	 * $META
	 * Variable estática pública de la aplicación
	 * Sirve para alojar datos de manera temporal y ser leídos por distintos procesos 
	 * en distintos niveles de ejecución sin requerir declarar variables globales
	 */
	public static $META = [];

	/**
	 * isShutdownHookRegistered()
	 * Función que permite conocer si la aplicación ya insertó el Hook de salida 
	 * para personalizar el buffer y añadirle la estructura html o json según sea
	 * el caso
	 */
	protected static $_isShutdownHookRegistered = false;
	public static function isShutdownHookRegistered ():bool
	{
		return static :: $_isShutdownHookRegistered;
	}

	/** _init() */
	protected function _init ()
	{
		//=== Variable pública que permite alojar data temporal
		static :: $META = new JArray();

		//=== Leer archivo de configuración
		static :: loadConfig();

		//=== Iniciar $_SESSION
		static :: startSessions();

		//=== Procesar la información del REQUEST
		static :: parseUrl();

		//=== Detectar Lenguaje
		static :: detectLang();

		//=== Detectar Timezone
		static :: detectTimezone();

		//=== Establecer el CHARSET
		static :: setDefaultCharset();

		//=== Restaurar el buffer de salida a 1
		static :: clearBuffer();

		ob_start();

		//=== Todo buffer desde ahora será formateado
		register_shutdown_function('APP::_send_response_on_shutdown');
		static :: $_isShutdownHookRegistered = true;;

		//=== Revisar si el URI tiene un cache almacenado
		static :: checkPageCache();

		//=== Install if have the installer
		static :: install();

		//=== Preparar datos para el proceso
		static :: prepararProceso();

		action_apply('APP/Init');
	}

	/** install() */
	protected static function install ()
	{
		static $_dir = APPPATH . DS . 'installer';

		if ( ! file_exists($_dir))
			return; // No tiene instalador

		$_dir_tmp = $_dir . '-' . time();
		rename($_dir, $_dir_tmp);

		static $_files = [
			DS . 'install.php', // install  (Primera fase de instalación — Puede requerirse configuraciones extras a los ya añadidos)
			DS . 'objects.php', // objects  (Instalación de Objetos en las diferentes conecciones de base datos)
			DS . 'tables.php',  // tables   (Tablas generales no específicamente de objetos)
			DS . 'extras.php',  // extras   (Extra Instalación)
			DS . 'data.php',    // data     (Inserción de DATA por defecto)
			DS . 'updates.php', // updates  (Actualizaciones varias)
		];

		foreach ($_files as $_file)
		{
			if ( ! file_exists($_dir_tmp . $_file))
				continue;

			require_once $_dir_tmp . $_file;
		}

		rename($_dir_tmp, $_dir . '-backup'); ## No hay problema si se llama directo a la carpeta ya que todos los archivos deben permitir ser incluídos directo desde el APP()
		action_apply('APP::installed');
	}

	/** get_static_vars() */
	protected static function get_static_vars()
	{
		$result = [];
		$var    = get_class_vars(get_called_class());

		foreach ($var as $name => $default)
			if (isset(static::$$name))
				$result[$name] = $default;

		return $result;
	}

	/** debugInfo() */
	public static function debugInfo ():array
	{
		$data = static :: get_static_vars();

		unset($data['_config_info'], $data['doctypes']);

		$data = array_combine(array_map(function($key){
			return preg_replace('/^_/', '', $key);
		}, array_keys($data)), array_values($data));

		return $data;
	}


	public static $PREREQUESTS_CLASSES = [];

	/**
	 * process()
	 * @static
	 */
	public static function process ()
	{
		/** Procesar PreRequests */

		$PREREQUESTS_CLASSES = static :: $PREREQUESTS_CLASSES;

		if (count($PREREQUESTS_CLASSES) > 0)
		{
			$point = mark('CORE/Processing the prerequests');

			foreach ($PREREQUESTS_CLASSES as $class)
				static :: procesar($class);

			$point -> end();
		}


		/** Procesar Request */

		$point = mark('CORE/Processing the request');

		static :: procesar('Request');

		$point -> end();


		/** Exit if type not is "html" or "body" */
		$_response_type = static :: getResponseType ();
		if ( ! in_array($_response_type, ['html', 'body']))
			exit;


		/** Procesar Response */
		$point = mark('CORE/Processing the response');

		static :: procesar('Response');

		$point -> end();

		/** Se formateará al HTML o al BODY según sea el tipo de response */
		exit;
	}
}