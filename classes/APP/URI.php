<?php
/**
 * APPPATH/classes/APP/URI.php
 * @filesource
 */
namespace APP;

defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * APP\URI
 */
use Locale;
use XONK;

trait URI
{
	protected static $_url_info = [
		'request_method' => null,  ## GET, POST, PUT, DELETE ó PATCH

		'scheme'         => null,  ## https ó http
		'host'           => null,  ## www.dominio.com, dominio.com, www.sub.dominio.com ó sub.dominio.com — coman.do (en caso de cli) ó desconoci.do (en caso de no detectar host pero no cli)
		'port'           => null,  ## 443, 80, 8080 ó 22 (en caso de ser comando)
//		'user'           => null,
//		'pass'           => null,
		'path_before'    => null,  ## scheme://host{/path_before}/path
		'path'           => null,  ## scheme://host/path_before{/path}
		'query'          => null,
		'fragment'       => null,

		'is_command'     => false, ## true ó false — ISCOMMAND
		'script_fname'   => null,
		'script_args'    => null,

		'base'           => null,  ## scheme://dominio.com:port/path_before — devuelve la ruta base
		'full'           => null,  ## scheme://dominio.com:port/path_before/path — devuelve la ruta completa sin el query
		'full_wq'        => null,  ## scheme://dominio.com:port/path_before/path?query — devuelve la ruta completa con el query
		'cookie_base'    => null,  ## path_before/ — devuelve la ruta base para el cookie según el path_before (el slash debe ser únicamente al final)
		'cookie_full'    => null,  ## path_before/path/ — devuelve la ruta completa para el cookie según el path_before y el path (el slash debe ser únicamente al final y en medio de los paths)
		'www'            => false, ## true ó false — true en caso de que se detecte www en el host
		'https'          => false, ## true ó false — true en caso de que se detecte https en el scheme
		'host_base'      => null,  ## dominio.com — remueve absolutamente todos los subdominios dejando solo el dominio master y su raíz (equivale al mismo host en caso de no tener subdominio)
		'host_parent'    => null,  ## sub.dominio.com — remueve solo el primer subdominio detectado: ej. extra.sub.dominio.com -> sub.dominio.com (equivale al mismo host en caso de no tener subdominio / se excluye el www como subdominio si lo tuviere)
		'host_clean'     => null,  ## dominiocom — devuelve el host sin puntos
		'host_md5'       => null,  ## md5(dominio.com) — devuelve el md5 del host
		'port_uri'       => null,  ## :puerto — en caso de port 80 y 443 es vacío ya que el scheme manda
		'host_uri'       => null,  ## dominio.com:port — devuelve el host concatenado al port_uri
		'scheme_uri'     => null,  ## scheme://

		'lang'           => 'es-PE',        ## Idioma
		'charset'        => 'UTF-8',        ## Codificación
		'timezone'       => 'America/Lima', ## Huso Horario
		'utc'            => '-05:00',       ## Diferencia UTC
	];

	public static $_cookie_lang = 'lang';
	public static $_cookie_lang_time = 60 * 60 * 24 * 7 * 4 * 12 * 10; ## 10 años

	protected static function parseUrl ()
	{
		static :: $_url_info['request_method'] = mb_strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : (ISCOMMAND ? 'cli' : 'bad'));

		isset($_SERVER['SERVER_PORT']) or $_SERVER['SERVER_PORT'] = ISCOMMAND ? 22 : 80;
		isset($_SERVER['REQUEST_URI']) or $_SERVER['REQUEST_URI'] = '/';
		isset($_SERVER['HTTP_HOST'])   or $_SERVER['HTTP_HOST']   = (ISCOMMAND ? 'coman' : 'desconoci') .'.do';
		isset($_SERVER['SCRIPT_NAME']) or $_SERVER['SCRIPT_NAME'] = __FILE__;

		//=== Obtener https
		$_https = FALSE;

		if (
			(isset($_SERVER['HTTPS']) and ! empty($_SERVER['HTTPS']) and mb_strtolower($_SERVER['HTTPS']) !== 'off') ||
			(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and mb_strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
			(isset($_SERVER['HTTP_FRONT_END_HTTPS']) and ! empty($_SERVER['HTTP_FRONT_END_HTTPS'])   and mb_strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') ||
			(isset($_SERVER['REQUEST_SCHEME']) and $_SERVER['REQUEST_SCHEME'] === 'https')
		)
			$_https = TRUE;

		isset($_SERVER['REQUEST_SCHEME']) or $_SERVER['REQUEST_SCHEME'] = 'http' . ($_https ? 's' : '');

		$REQUEST_URI = parse_url($_SERVER['REQUEST_URI']);

		static :: $_url_info['scheme']      = mb_strtolower($_SERVER['REQUEST_SCHEME']);
		static :: $_url_info['host']        = mb_strtolower($_SERVER['HTTP_HOST']);
		static :: $_url_info['port']        = (int) $_SERVER['SERVER_PORT'];
		static :: $_url_info['path_before'] = static :: getConfig('path_before');
		static :: $_url_info['path']        = isset($REQUEST_URI['path'])  ? $REQUEST_URI['path']  : '/';
		static :: $_url_info['query']       = isset($REQUEST_URI['query']) ? $REQUEST_URI['query'] : '' ;

		if ( ! is_empty(static :: $_url_info['path_before']))
			static :: $_url_info['path'] = str_replace(static :: $_url_info['path_before'], '', static :: $_url_info['path']);

		if (ISCOMMAND)
		{
			global $argv;

			static :: $_url_info['is_command']   = true;
			static :: $_url_info['script_fname'] = array_shift($argv); ## SCRIPT_FILENAME

			count($argv) > 0 and static :: $_url_info['path'] = '/' . array_shift($argv);
			static :: $_url_info['script_args'] = $argv;
		}

		static :: $_url_info['path'] = preg_replace('#(^|[^:])//+#', '\\1/', static :: $_url_info['path']); ## reduce double slashes
		static :: $_url_info['path'] = '/' . trim((string) static :: $_url_info['path'], '/'); ## slash just in the start

		static :: generateUrlExtras ();
	}

	protected static function detectLang ()
	{
		if (isset($_SESSION['lang']) and ! is_empty($_SESSION['lang']) and static :: isAvailableLanguage($_SESSION['lang']))
			return static :: setSessionLang($_SESSION['lang']);

		$cookie_name = def_empty(static::$_cookie_lang, 'lang');
		if (isset($_COOKIE[$cookie_name]) and ! is_empty($_COOKIE[$cookie_name]) and static :: isAvailableLanguage($_COOKIE[$cookie_name]))
			return static :: setSessionLang($_COOKIE[$cookie_name]);

		$_HTTP_ACCEPT_LANGUAGE = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? $_SERVER["HTTP_ACCEPT_LANGUAGE"] : '';
		if ( ! is_empty($_HTTP_ACCEPT_LANGUAGE))
		{
			preg_match_all('~([\w-]+)(?:[^,\d]+([\d.]+))?~', mb_strtolower($_HTTP_ACCEPT_LANGUAGE), $matches, PREG_SET_ORDER);

			foreach($matches as $match)
				if (static :: isAvailableLanguage($match[1], true))
					return static :: setSessionLang($match[1]);

			foreach($matches as $match)
				if ($_lang = static :: suggestAvailableLanguage($match[1]) and ! is_null($_lang))
					return static :: setSessionLang($_lang);
		}

		$config = static :: getConfig('default_lang');
		if ( ! is_empty($config))
			return static :: setSessionLang($config);
	}

	protected static function detectTimezone ()
	{
		if (isset($_SESSION['timezone']) and ! is_empty($_SESSION['timezone']))
			return static :: setTimezone($_SESSION['timezone']);

		$xonk = XONK :: getIpTimezone();
		if ( ! is_empty($xonk))
			return static :: setTimezone($xonk);

		$config = static :: getConfig('default_timezone');
		if ( ! is_empty($config))
			return static :: setTimezone($config);
	}

	protected static function setDefaultCharset ()
	{
		$config = static :: getConfig('default_charset');
		if ( ! is_empty($config))
			return static :: setCharset($config);
	}



	protected static function generateUrlExtras ()
	{
		$_scheme_uri     = static :: $_url_info['scheme'] . '://';
		$_port_uri       = in_array(static :: $_url_info['port'], [80, 443]) ? '' : (':' . static :: $_url_info['port']);
		$_host_port_uri  = static :: $_url_info['host'] . $_port_uri;
		$_path_before_fc = trim((string) static :: $_url_info['path_before'], '/') . '/';
		$_path_fc        = trim((string) static :: $_url_info['path'], '/') . '/';
		$_has_www        = (bool) preg_match('/^www\./', static :: $_url_info['host']);
		$_has_ssl        = (bool) (static :: $_url_info['scheme'] === 'https');

		$_host_base = explode('.', static :: $_url_info['host']);
		while (count($_host_base) > 2)
			array_shift($_host_base);

		$_host_parent = explode('.', static :: $_url_info['host']);
		$_has_www and array_shift($_host_parent);
		count($_host_parent) > 2 and array_shift($_host_parent);

		static :: $_url_info['base']        = $_scheme_uri . $_host_port_uri . static :: $_url_info['path_before'];
		static :: $_url_info['full']        = $_scheme_uri . $_host_port_uri . static :: $_url_info['path_before'] . static :: $_url_info['path'];
		static :: $_url_info['full_wq']     = $_scheme_uri . $_host_port_uri . static :: $_url_info['path_before'] . static :: $_url_info['path'] . (empty(static :: $_url_info['query']) ? '' : '?') . static :: $_url_info['query'];
		static :: $_url_info['cookie_base'] = $_path_before_fc;
		static :: $_url_info['cookie_full'] = trim($_path_before_fc . $_path_fc, '/') . '/';
		static :: $_url_info['www']         = $_has_www;
		static :: $_url_info['https']       = $_has_ssl;
		static :: $_url_info['scheme_uri']  = $_scheme_uri;
		static :: $_url_info['port_uri']    = $_port_uri;
		static :: $_url_info['host_base']   = implode('.', $_host_base);
		static :: $_url_info['host_parent'] = implode('.', $_host_parent);
		static :: $_url_info['host_uri']    = $_host_port_uri;
		static :: $_url_info['host_clean']  = preg_replace('/[^a-z0-9]/i', '', static :: $_url_info['host']);
		static :: $_url_info['host_md5']    = md5(static :: $_url_info['host_clean']);
	}

	protected static function _reparoLang (string $lang)
	{
		$lang = str_replace('_', '-', $lang);
		$lang = explode('-', $lang, 3);

		if (count($lang) === 3)
			array_pop($lang);

		if (count($lang) === 2)
			$lang[1] = mb_strtoupper($lang[1]);

		return implode('-', $lang);
	}



	public static function url (string $key = 'base')
	{
		if ($key === 'array')
			return static :: $_url_info;

		if (isset(static :: $_url_info[$key]))
			return static :: $_url_info[$key];

		if ($key = str_replace('-', '_', $key) and isset(static :: $_url_info[$key]))
			return static :: $_url_info[$key];

		return null;
	}

	public static function setUrlScheme (string $scheme):void
	{
		static :: $_url_info['scheme'] = mb_strtolower($scheme);

		static :: generateUrlExtras ();
	}

	public static function getUrlScheme ()
	{
		return static :: $_url_info['scheme'];
	}

	public static function setUrlHost (string $host):void
	{
		static :: $_url_info['host'] = mb_strtolower($host);

		static :: generateUrlExtras ();
	}

	public static function getUrlHost ()
	{
		return static :: $_url_info['host'];
	}

	public static function setUrlPort (int $port):void
	{
		static :: $_url_info['port'] = $port;

		static :: generateUrlExtras ();
	}

	public static function getUrlPort ()
	{
		return static :: $_url_info['port'];
	}

	public static function setUrlPath (string $path):void
	{
		static :: $_url_info['path'] = $path;

		static :: generateUrlExtras ();
	}

	public static function getUrlPath ()
	{
		return static :: $_url_info['path'];
	}

	public static function setUrlQuery ($query):void
	{
		is_array($query) and $query = http_build_query($query);
		static :: $_url_info['query'] = (string) $query;

		static :: generateUrlExtras ();
	}

	public static function getUrlQuery ()
	{
		return static :: $_url_info['query'];
	}

	public static function setUrlFragment (string $fragment):void
	{
		static :: $_url_info['fragment'] = preg_replace('/^#/', '', $fragment);

		static :: generateUrlExtras ();
	}

	public static function getUrlFragment ()
	{
		return static :: $_url_info['fragment'];
	}

	public static function setUrlPathBefore (string $path_before)
	{
		static :: $_url_info['path_before'] = $path_before;

		static :: generateUrlExtras ();
	}

	public static function getUrlPathBefore ()
	{
		return static :: $_url_info['path_before'];
	}

	public static function getRequestText ()
	{
		$_url_info = static :: $_url_info;
		$_txt = $_url_info['request_method'] . ' ' . $_url_info['full_wq'] . PHP_EOL . '-- lang: ' . $_url_info['lang'] . PHP_EOL . '-- timezone: ' . $_url_info['timezone'];

		return $_txt;
	}

	public static function getRequestHash ()
	{
		$_txt = static :: getRequestText();
		return md5($_txt);
	}

	public static function setRequestMethod (string $method):void
	{
		static :: $_url_info['request_method'] = mb_strtoupper($method);

		action_apply('APP/RequestMethod', $method);
	}

	public static function getRequestMethod ()
	{
		return static :: $_url_info['request_method'];
	}

	public static function getAvailableLanguages ()
	{
		static $_available_languages;

		if ( ! isset($_available_languages))
		{
			$_available_languages = (array) static :: getConfig('available_languages');
			$_available_languages = array_map(function($o){
				return static :: _reparoLang($o);
			}, $_available_languages);
		}

		return $_available_languages;
	}

	public static function isAvailableLanguage (string $lang)
	{
		static $availables;
		isset($availables) or $availables = static :: getAvailableLanguages();

		$lang = static :: _reparoLang($lang);

		if (in_array($lang, $availables))
			return true;

		return false;
	}

	public static function suggestAvailableLanguage (string $lang)
	{
		static $availables;
		if ( ! isset($availables))
		{
			$availables = [
				'bases' => [],
				'subs'  => [],
			];

			$availables_all = static :: getAvailableLanguages();

			foreach ($availables_all as $temp)
			{
				$temp = explode('-', $temp);

				if (count($temp) === 2)
				{
					isset($availables['subs'][$temp[0]]) or $availables['subs'][$temp[0]] = [];
					$availables['subs'][$temp[0]][] = $temp[1];
					continue;
				}

				$availables['bases'][] = $temp[0];
			}
		}

		$lang = static :: _reparoLang($lang);
		$lang = explode('-', $lang);

		$base = array_shift($lang);
		$sub  = array_shift($lang);

		if ( ! is_null($sub) and isset($availables['subs'][$base]) and in_array($sub, $availables['subs'][$base]))
		{
			return $base . '-' . $sub;
		}

		if (in_array($base, $availables['bases']))
		{
			return $base;
		}

		if (isset($availables['subs'][$base]))
		{
			return $base . '-' . $availables['subs'][$base][0];
		}

		return null;
	}

	public static function setSessionLang (string $lang)
	{
		$lang = static :: _reparoLang($lang);

		$_SESSION['lang'] = static::$_url_info['lang'] = $lang;

		if (class_exists('Locale'))
			Locale::setDefault($lang);

		action_apply('APP/Lang', $lang);
	}

	public static function setLang (string $lang, bool $set_cookie = false, string $cookie_name = null)
	{
		$lang = static :: _reparoLang($lang);

		static::$_url_info['lang'] = $lang;

		if ( ! ISCOMMAND and $set_cookie)
		{
			$cookie_name = def_empty($cookie_name, static::$_cookie_lang, 'lang');
			setcookie($cookie_name, $lang, time() + (int) static::$_cookie_lang_time, '/');
			$_COOKIE[$cookie_name] = $lang;
		}

		if (class_exists('Locale'))
			Locale::setDefault($lang);

		action_apply('APP/Lang', $lang);
	}

	public static function getLang ()
	{
		return static::$_url_info['lang'];
	}

	public static function getLocale ()
	{
		if (class_exists('Locale'))
			return Locale::getDefault();

		return static::getLang();
	}

	public static function setCharset (string $charset):void
	{
		$charset = mb_strtoupper($charset);

		static::$_url_info['charset'] = $charset;

		ini_set('default_charset', $charset);
		ini_set('php.internal_encoding', $charset);
		mb_substitute_character('none');

		defined('UTF8_ENABLED') or 
		define ('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && $charset === 'UTF-8');

		action_apply('APP/Charset', $charset);
	}

	public static function getCharset ():string
	{
		return static::$_url_info['charset'];
	}

	public static function setTimezone (string $timezone)
	{
		$_SESSION['timezone'] = static::$_url_info['timezone'] = $timezone;

		date_default_timezone_set ($timezone);

		action_apply('APP/Timezone', $timezone);

		static::$_url_info['utc'] = $utc = calcular_utc($timezone);

		action_apply('APP/UTC', $utc);
	}

	public static function getTimezone ()
	{
		return static::$_url_info['timezone'];
	}

	public static function getUTC ()
	{
		return static::$_url_info['utc'];
	}
}