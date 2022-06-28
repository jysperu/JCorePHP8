<?php
/**
 * JCore/Module/XONK.php
 * @filesource
 */

namespace JCore\Module;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore\ComponenteTrait;
use JCore as JCoreInstance;

use JCore\Controller\IP        as IpControlTrait;
use JCore\Controller\UserAgent as UaControlTrait;
use JCore\Controller\Crypt     as CryptTrait;

use JCore\Helper\Random;

/**
 * XONK
 * Protege el Requests de posibles intentos de hackeo
 * Almacena la información en una base datos (sqlite3 o mongodb)
 *
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
	use ComponenteTrait;
	use IpControlTrait;
	use UaControlTrait;
	use CryptTrait;

	public function init (JCoreInstance $JCore)
	{
		//=== Obtener la IP
		static :: setIp(
			static :: detectRequestIp()
		);

		//=== Obtener el UserAgent
		static :: setUa(
			static :: detectRequestUa()
		);

		//=== Si es comando entonces no filtra las conecciones
		if (ISCOMMAND) # Constante definido en la clase JCore
			return;

		//=== Establecer la cookie de identificación de dispositivo
		$cookie4_device = $JCore :: $COOKIE4_DEVICE;
		defined('cookie4_device') or define('cookie4_device', $cookie4_device);

		if ( ! isset($_COOKIE[cookie4_device]))
		{
			$_COOKIE[cookie4_device] = Random :: salt (
				64      # digitos
				, true  # min
				, true  # may
				, true  # num
				, false # tildes
				, false # sym
				, false # spaces
			);

			setcookie(cookie4_device, $_COOKIE[cookie4_device], time() + (60 * 60 * 24 * 28 * 12 * 10), '/'); # 10 años
		}
	}
}