<?php
/**
 * APPPATH/classes/XONK.php
 * @filesource
 */
defined('APPPATH') or exit(2); # Acceso directo no autorizado



/**
 * cookie4_device
 * Establece la cookie de identificación de dispositivo
 */
defined('cookie4_device') or define('cookie4_device', 'cdkdsp');



/**
 * XONK
 * Protege al sistema de potenciales intrusiones
 * e intentos de hackeo bloqueando REQUEST malignos
 *
 * > Se recomienda el almacenamiento de la información en una base datos mongodb o sqlite3
 *
 * Uso:
 * XONK :: protect ();
 *
 * Funciones Estáticas Disponibles:
 * getIP():string				Retorna la IP establecida en la clase
 * setIP(string):void			Establecer una IP incluso si no es la detectada
 * getIpHostname():string				Retorna el hostname de la IP
 * setIpHostname(string):void			Establece un hostname para la IP
 * getHostnameOfIP(string):string	Detecta el hostname de la IP enviada en el parámetro
 * detectRequestIP():string		Detecta la IP del REQUEST
 * getUA():string				Retorna el USER-AGENT establecida en la clase
 * setUA(string):void			Establece  un USER-AGENT incluso si no es el detectado
 * detectRequestUa():string		Detecta el USER-AGENT del REQUEST
 *
 * cleanInvisibleCharacter(string):string		Limpia los caractéres invisibles
 *
 * Acción Por Defecto Filtro IP:
 * Es la acción que debe considerarse por defecto al filtrar una IP
 * Por defecto: "Sin Acción"
 * Los IPs con Acción "Bloquear" se sincronizan a nivel global
 *     + Todos cuentan con un motivo correspondiente a un filtro automático
 *     + La lista funciona para los firewalls `csf.blocklists`
 *     + Se deben eliminar de la lista cada cierto periodo
 *
 *
 * Flujo de filtros:
 * 01.	Identifica la IP
 * 		- Si no tiene IP, se detiene el REQUEST y se muestra un mensaje indicando el motivo. 
 *		  (No se ejecutan los filtros en los requests mediante comandos)
 *
 * 02.	Se busca la acción para la IP
 * 		- Si no tiene asignado una acción se asume la "Acción Por Defecto Filtro IP"
 * 		- Si la acción es "Permitir", se detiene el FLUJO y continúa el proceso del request. (Lista Blanca)
 * 		- Si la acción es "Bloquear", se detiene el REQUEST y se muestra un mensaje indicando el motivo. (Lista Negra)
 * 		- Si la acción es "Sin Acción", continúa el flujo del filtro
 *
 * 03.	Obtiene la información de la IP
 *      - Se busca en la DB interna
 *      - Se obtiene desde algún proveedor remoto
 *        * Puede definirse drivers de proveedores remotos
 *
 * 04.	Se busca la acción para el país identificado de la IP
 * 		- Si no tiene asignado una acción se asume la "Sin Acción"
 * 		- Si la acción es "Bloquear", se detiene el REQUEST y se muestra un mensaje indicando el motivo. (Lista Negra)
 * 		- Si la acción es "Sin Acción", continúa el flujo del filtro
 * 		- No existe la acción "Permitir" para el filtro de País
 *
 * 05.	Identifica el UserAgent
 * 		- Si no tiene UserAgent, se detiene el REQUEST y se muestra un mensaje indicando el motivo. 
 * 		- Si contiene palabras de crawlers dañinos, se detiene el REQUEST y se muestra un mensaje indicando el motivo. 
 *
 * 06.	Se busca la acción para el md5(UserAgent)
 * 		- Si no tiene asignado una acción se asume la "Acción Por Defecto Filtro UserAgent"
 * 		- Si la acción es "Bloquear", se detiene el REQUEST y se muestra un mensaje indicando el motivo. (Lista Negra)
 * 		- Si la acción es "Sin Acción", continúa el flujo del filtro
 * 		- No existe la acción "Permitir" para el filtro de UserAgent
 *
 * 07.	Obtiene la información del UserAgent
 *      - Se busca en la DB interna
 *      - Se obtiene desde el método get_browser
 *      - Se obtiene desde algún proveedor remoto
 *        * Puede definirse drivers de proveedores remotos
 *
 * 08.	Si es un crawler, Se busca la acción para el proveedor del crawler
 * 		- Si no tiene asignado una acción se asume la "Sin Acción"
 * 		- Si la acción es "Bloquear", se detiene el REQUEST y se muestra un mensaje indicando el motivo. (Lista Negra)
 * 		- Si la acción es "Sin Acción", continúa el flujo del filtro
 * 		- No existe la acción "Permitir" para el filtro de Crawlers
 *
 * 09.	Filtro COMMON_WORDS_ON_URI
 *		Analiza el URI en busqueda de palabras usadas para intentar hackear alguna plataforma como WordPress u otro.
 *		Si se encuentra:
 *		  + Se guarda el registro de filtro
 *		  + Se detiene el REQUEST y se muestra un mensaje indicando el motivo.
 *
 * 10.	Filtro SQL_INYECTION
 * 		Analiza los datos recibidos ($_GET, $_POST, php://input y php://stdin) para buscar coincidencias de ataque
 *		  + Se guarda el registro de filtro
 *		  + Se detiene el REQUEST y se muestra un mensaje indicando el motivo.
 *
 * 11.	Filtro XSS_ATTACK
 * 		Analiza los datos recibidos ($_GET, $_POST, php://input y php://stdin) para buscar coincidencias de ataque
 *		  + Se guarda el registro de filtro
 *		  + Se detiene el REQUEST y se muestra un mensaje indicando el motivo.
 *
 * 12.	Filtro DOR_ATTACK
 * 		Analiza los datos recibidos ($_GET, $_POST, php://input y php://stdin) para buscar coincidencias de ataque
 *		  + Se guarda el registro de filtro
 *		  + Se detiene el REQUEST y se muestra un mensaje indicando el motivo.
 *
 *
 *
 * Flujo de la función shutdown:
 *		- (Filtro MULTIPLE_404) Si el código de respuesta del RESPONSE es 404,
 *		  + Se guarda el registro de filtro
 *		- Si hay 03 registros en menos de 05 minutos de un mismo filtro entonces Banea la IP
 *		- Si hay 03 registros en menos de 15 minutos de diferentes filtros entonces Banea la IP
 *
 *
 * Baneo de IP por Filtro:
 * + Se envía la información a la lista global para su analisis
 *   - Todos los registros (FILTRO, Fecha y Hora, URI, REQUEST_METHOD, $_GET / $_POST / php://input, ...)
 *
 *
 * > Si hay intentos erróneos de logueo deben enviarse a guardar el registro como Filtro LOGIN_ERROR
 *
 * > Debe sincronizar periódicamente la lista de los IPs baneados (se puede desactivar la sincronización usando el request del usuario para cambiarlo por un cron JCorePATH/index.php --xonk-cron)
 *
 * > Si el handler es SQLITE3 entonces el archivo se almacena en la carpeta JCore
 *
 * > EL bloqueo a ataques DOS es mediante Proxies como CloudFlare
 */
class XONK
{
	public const db_version = '1.0';
	public const db_structure = [
		'lista_ip' => [
			[
				'name'    => 'ip_md5',
				'type'    => 'char',
				'length'  => 40,
				'notnull' => true,
				'pk'      => true,
			],
			[
				'name'    => 'ip',
				'type'    => 'text',
			],
			[
				'name'    => 'accion',
				'type'    => 'text',
			],
			[
				'name'    => 'motivo',
				'type'    => 'text',
			],
			[
				'name'    => 'info',
				'type'    => 'json',
			],
			[
				'name'    => 'registrado_el',
				'type'    => 'integer',
			],
			[
				'name'    => 'registrado_por',
				'type'    => 'text',
			],
		],
		'lista_ua' => [
			[
				'name'    => 'ua_md5',
				'type'    => 'char',
				'length'  => 40,
				'notnull' => true,
				'pk'      => true,
			],
			[
				'name'    => 'ua',
				'type'    => 'text',
			],
			[
				'name'    => 'accion',
				'type'    => 'text',
			],
			[
				'name'    => 'motivo',
				'type'    => 'text',
			],
			[
				'name'    => 'info',
				'type'    => 'json',
			],
			[
				'name'    => 'registrado_el',
				'type'    => 'integer',
			],
			[
				'name'    => 'registrado_por',
				'type'    => 'text',
			],
		],
	];

	use XONK\DB;
	use XONK\BlackListManager;
	use XONK\IpInfo;
	use XONK\UserAgentInfo;

	public static function protect ()
	{
		static $_setted = false;
		if ($_setted) return;
		$_setted = true;

		//=== Obtener la IP
		static :: detectRequestIP();

		//=== Obtener el UserAgent
		static :: detectRequestUa();

		//=== Establecer si el REQUEST es mediante comando
		defined('ISCOMMAND') or define('ISCOMMAND', (substr(PHP_SAPI, 0, 3) === 'cli' ? 'cli' : defined('STDIN')));

		//=== Si es comando entonces no filtra las conecciones
		if (ISCOMMAND) # Constante definido en la clase JCore
			return;

		//=== Establecer la cookie de identificación de dispositivo
		if ( ! isset($_COOKIE[cookie4_device]))
		{
			$_COOKIE[cookie4_device] = static :: _random();
			setcookie(cookie4_device, $_COOKIE[cookie4_device], time() + (60 * 60 * 24 * 28 * 12 * 10), '/'); # 10 años
		}

		//=== Iniciar conección con la DB
		static :: dbConnect();

		//=== Proceder con los filtros de protección
		
		// 
	}

	public static function prepareAsyncValidations ()
	{
		
	}

	protected static function detectRequestIP ():void
	{
		$SERVER_ADDR = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
		$keys        = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR', 'SERVER_ADDR'];

		foreach ($keys as $key)
		{
			if ( ! isset($_SERVER[$key]))
				continue; # No se encontró el valor

			$val = $_SERVER[$key];
			$val = str_replace('::ffff:', '', $val); # Some errors on engintron

			if (empty($val))
				continue; # El valor está vacío

			if ( ! filter_var($val, FILTER_VALIDATE_IP))
				continue; # IP no válido (IPV4 o IPV6)

			if ($val === $SERVER_ADDR)
				continue; # IP es del servidor (probablemente proveniente por un proxy interno)

			static :: setIP ($val);
			break;
		}
	}

	protected static function detectRequestUa ():void
	{
		$keys = ['HTTP_X_USER_AGENT', 'HTTP_USER_AGENT'];

		foreach ($keys as $key)
		{
			if ( ! isset($_SERVER[$key]))
				continue; # No se encontró el valor

			$val = $_SERVER[$key];

			if (empty($val))
				continue; # El valor está vacío

			static :: setUA($val);
			break;
		}
	}

	private static function _random ()
	{
		if (class_exists('Helper\Random'))
		return 'R' . \Helper\Random :: salt (
			64      # digitos
			, true  # min
			, true  # may
			, true  # num
			, false # tildes
			, false # sym
			, false # spaces
		);

		return 'U' . uniqid(md5(__FILE__), true);
	}



	//=== Funciones de IP
	protected static $_ip_info = [
		'ip'       => null,
		'hostname' => null,
		'city'     => null,
		'region'   => null,
		'country'  => null,
		'loc'      => null,
		'org'      => null,
		'postal'   => null,
		'timezone' => null,
	];

	public static function getIpInfo ():array
	{
		return static :: $_ip_info;
	}

	public static function getIP ():string
	{
		return static :: $_ip_info['ip'];
	}

	public static function setIP (string $ip):void
	{
		static :: $_ip_info['ip'] = $ip;

        static :: setIpHostname (
			static :: getHostnameOfIP($ip)
		);

		static :: obtainIpInfo ();
	}

	public static function setIpHostname ($hostname):void
	{
		static :: $_ip_info['hostname'] = $hostname;
	}

	public static function getIpHostname ()
	{
		return static :: $_ip_info['hostname'];
	}

	public static function getHostnameOfIP (string $ip):string
	{
		if (empty($ip))
			return 'empty-ip';

		if (in_array(substr($ip, 0, 8), ['192.168.', '127.0.']))
			return 'localhost';

		return gethostbyaddr($ip);
	}

	public static function setIpCity ($city):void
	{
		static :: $_ip_info['city'] = $city;
	}

	public static function getIpCity ()
	{
		return static :: $_ip_info['city'];
	}

	public static function setIpRegion ($region):void
	{
		static :: $_ip_info['region'] = $region;
	}

	public static function getIpRegion ()
	{
		return static :: $_ip_info['region'];
	}

	public static function setIpCountry ($country):void
	{
		static :: $_ip_info['country'] = $country;
	}

	public static function getIpCountry ()
	{
		return static :: $_ip_info['country'];
	}

	public static function setIpLoc ($loc):void
	{
		static :: $_ip_info['loc'] = $loc;
	}

	public static function getIpLoc ()
	{
		return static :: $_ip_info['loc'];
	}

	public static function setIpOrg ($org):void
	{
		static :: $_ip_info['org'] = $org;
	}

	public static function getIpOrg ()
	{
		return static :: $_ip_info['org'];
	}

	public static function setIpPostal ($postal):void
	{
		static :: $_ip_info['postal'] = $postal;
	}

	public static function getIpPostal ()
	{
		return static :: $_ip_info['postal'];
	}

	public static function setIpTimezone ($timezone):void
	{
		static :: $_ip_info['timezone'] = $timezone;
	}

	public static function getIpTimezone ()
	{
		return static :: $_ip_info['timezone'];
	}



	//=== Funciones de USER-AGENT
	protected static $_ua_info = [
		'user_agent' => '',

		'platform'                    => null,
    	'platform_version'            => null,
		'platform_maker'              => null,
		'platform_bits'               => null,
		'platform_description'        => null,

		'browser'                     => null,
		'version'                     => null,
		'majorver'                    => null,
		'minorver'                    => null,
		'browser_maker'               => null,
		'browser_bits'                => null,
		'browser_type'                => null,
		'browser_modus'               => null,
		'comment'                     => null,

		'device_name'                 => null,
		'device_type'                 => null,
		'device_pointing_method'      => null,
		'device_code_name'            => null,
		'device_type'                 => null,
		'device_pointing_method'      => null,
		'device_maker'                => null,
		'device_brand_name'           => null,

		'renderingengine_name'        => null,
		'renderingengine_version'     => null,
		'renderingengine_maker'       => null,
		'renderingengine_description' => null,

		'ismobiledevice'              => null,
		'istablet'                    => null,
		'crawler'                     => null,
		'issyndicationreader'         => null,
		'isfake'                      => null,
		'isanonymized'                => null,
		'ismodified'                  => null,

		'win64'                       => null,
		'win32'                       => null,
		'win16'                       => null,

		'frames'                      => null,
		'iframes'                     => null,
		'tables'                      => null,
		'cookies'                     => null,
		'javascript'                  => null,
		'cssversion'                  => null,
		'aolversion'                  => null,
		'backgroundsounds'            => null,
		'vbscript'                    => null,
		'javaapplets'                 => null,
		'activexcontrols'             => null,
		'alpha'                       => null,
		'beta'                        => null,
	];

	public static function getUaInfo ():array
	{
		return static :: $_ua_info;
	}

	public static function getUA ():string
	{
		return static :: $_ua_info['user_agent'];
	}

	public static function setUA (string $user_agent):void
	{
		static :: $_ua_info['user_agent'] = $user_agent;
	}

	public static function getUaPlatform (string $key = null)
	{
		$attr = 'platform';
		empty($key) or $attr .= '_' . $key;

		if ( ! isset(static::$_ua_info[$attr]))
			return null;

		return static::$_ua_info[$attr];
	}

	public static function getUaBrowser(string $key = null)
	{
		$attr = 'browser';
		empty($key) or $attr .= '_' . $key;

		if (in_array($key, ['version', 'version', 'majorver', 'minorver', 'comment']))
			$attr = $key;

		if ( ! isset(static::$_ua_info[$attr]))
			return null;

		return static::$_ua_info[$attr];
	}

	public static function getUaDevice(string $key = null)
	{
		empty($key) or $key = 'name';

		$attr = 'device';
		$attr.= '_' . $key;

		if ( ! isset(static::$_ua_info[$attr]))
			return null;

		return static::$_ua_info[$attr];
	}

	public static function getUaRenderEngine(string $key = null)
	{
		empty($key) or $key = 'name';

		$attr = 'renderingengine';
		$attr.= '_' . $key;

		if ( ! isset(static::$_ua_info[$attr]))
			return null;

		return static::$_ua_info[$attr];
	}

	public static function isUaMobileDevice()
	{
		$attr = 'ismobiledevice';

		if ( ! isset(static::$_ua_info[$attr]))
			return null;

		return (bool) static::$_ua_info[$attr];
	}

	public static function isUaTablet()
	{
		$attr = 'istablet';

		if ( ! isset(static::$_ua_info[$attr]))
			return null;

		return (bool) static::$_ua_info[$attr];
	}

	public static function isUaCrawler()
	{
		$attr = 'crawler';

		if ( ! isset(static::$_ua_info[$attr]))
			return null;

		return (bool) static::$_ua_info[$attr];
	}

	public static function isUaDesktop()
	{
		foreach([
			'ismobiledevice',
			'istablet',
			'crawler',
			'issyndicationreader',
			'isfake',
			'isanonymized',
			'ismodified',
		] as $attr)
		{
			if ( ! isset(static::$_ua_info[$attr]))
				continue;

			$val = (bool) static::$_ua_info[$attr];

			if ($val)
				return false;
		}

		return true;
	}



	//=== Funciones de STRING
	public static function cleanInvisibleCharacter (string $str, bool $url_encoded = true)
	{
		$non_displayables = [];

		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ($url_encoded)
		{
			$non_displayables[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/i';	// url encoded 16-31
			$non_displayables[] = '/%7f/i';	// url encoded 127
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do
		{
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);

		return $str;
	}

	public static function debugInfo ():array
	{
		$info = [];
		$_ip_info = static :: getIpInfo ();
		$_ua_info = static :: getUaInfo ();

		$info['ip'] = $_ip_info['ip']; unset($_ip_info['ip']);
		$info['ip_info'] = array_filter($_ip_info, function ($v){
			return ! is_null($v);
		});

		$info['user_agent'] = $_ua_info['user_agent']; unset($_ua_info['user_agent']);
		$info['ua_info'] = array_filter($_ua_info, function ($v){
			return ! is_null($v);
		});

		return $info;
	}
}