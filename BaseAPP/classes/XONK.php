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
 * getRDns():string				Retorna el hostname de la IP
 * setRDns(string):void			Establece un hostname para la IP
 * getRDnsOfIP(string):string	Detecta el hostname de la IP enviada en el parámetro
 * detectRequestIP():string		Detecta la IP del REQUEST
 * getUa():string				Retorna el USER-AGENT establecida en la clase
 * setUa(string):void			Establece  un USER-AGENT incluso si no es el detectado
 * detectRequestUa():string		Detecta el USER-AGENT del REQUEST
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
	public static function protect ()
	{
		//=== Obtener la IP
		static :: setIP(
			static :: detectRequestIP()
		);

		//=== Obtener el UserAgent
		static :: setUa(
			static :: detectRequestUa()
		);

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

		//=== Proceder con los filtros de protección
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
	protected static $_ip   = '';
	protected static $_rdns = '';

	public static function getIP ():string
	{
		return static :: $_ip;
	}

	public static function setIP (string $ip):void
	{
		static :: $_ip = $ip;
        static :: setRDns (
			static :: getRDnsOfIP($ip)
		);
	}

	public static function getRDns ():string
	{
		return static :: $_rdns;
	}

	public static function getRDnsOfIP (string $ip):string
	{
		if (empty($ip))
			return 'empty-ip';

		if (in_array(substr($ip, 0, 8), ['192.168.', '127.0.']))
			return 'localhost';

		return gethostbyaddr($ip);
	}

	public static function setRDns (string $rdns):void
	{
		static :: $_rdns = $rdns;
	}

	public static function detectRequestIP ():string
	{
		static $ip_address = ''; # por defecto retornar vacío (NO DETECTADO)

		if ( ! empty($ip_address))
			return $ip_address;

		$SERVER_ADDR = $_SERVER['SERVER_ADDR'];
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

			$ip_address = $val;
			break;
		}

		return $ip_address;
	}



	//=== Funciones de USER-AGENT
	protected static $_ua   = '';

	public static function getUa ():string
	{
		return static :: $_ua;
	}

	public static function setUa (string $ua):void
	{
		static :: $_ua = $ua;
	}

	public static function detectRequestUa ():string
	{
		static $user_agent = ''; # por defecto retornar vacío (NO DETECTADO)

		if ( ! empty($user_agent))
			return $user_agent;

		$keys = ['HTTP_X_USER_AGENT', 'HTTP_USER_AGENT'];

		foreach ($keys as $key)
		{
			if ( ! isset($_SERVER[$key]))
				continue; # No se encontró el valor

			$val = $_SERVER[$key];

			if (empty($val))
				continue; # El valor está vacío

			$user_agent = $val;
			break;
		}

		return $user_agent;
	}
}