<?php
/*!
 * classes/APP.php
 * @filesource
 */
defined('APPPATH') or exit(0); ## Acceso directo no autorizado

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
 * routes_nmsps
 * @internal
 * Variable que indica todos los routes a pasar antes del Request
 * (Valores separados por una barra (|) y en el orden que seran buscados)
 */
defined('routes_nmsps') or define('routes_nmsps', 'ObjRoute|ReRoute|AlwRoute|PreRequest');

/**
 * APP
 */
class APP
{
	use Intanceable;

	public static $Config;

	protected function _init (): void
	{
		/** Corrigiendo directorio base */
		chdir(APPPATH);

		/** Revisar si se encuentra en modo mantenimiento */
		if (file_exists('maintenance.admin.php')) ## Nivel de administración
		{
			require_once('maintenance.admin.php');
			return;
		}

		if (file_exists('maintenance.php')) ## Nivel genérico
		{
			require_once('maintenance.php');
			return;
		}

		/** Antes de ejecutar alguna acción, proteger el REQUEST de un posible ataque */
		Driver\XONK :: protect();

		/** Escuchar todos los errores generados para poder gestionarlos y corregirlos */
		Driver\ErrorControl :: listen();

		/** Registrar puntos de control */
		Driver\BenchMark :: markFirstPoint ();

		/** */
		static :: $Config = APP\Config :: instance();

		/** Parsear la URL (URL amigable), detectar el idioma y el timezone */
		

		/** Ejecutar el proceso Autenticación */
		if (class_exists('Proceso\Authenticate'))
			Proceso\Authenticate :: check ();
		else
		if (class_exists('Proceso\Auth'))
			Proceso\Auth :: check ();
	}

	public static function process ()
	{
		$APP = static :: instance ();
		
		var_dump(APP :: $Config);
	}
}