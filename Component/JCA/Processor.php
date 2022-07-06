<?php
/**
 * JCore/Component/JCA/Processor.php
 * @filesource
 */

namespace JCore\Component\JCA;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore;
use JCore\JCA;
use JCore\ComponenteTrait;
use Process;

/**
 * Processor
 * Encargado de procesar el request usando la aplicación compilada
 *
 * Directorio `JCA_PATH/`
 *
 * Flujo:
 * 01.	Leer el archivo `JCA_PATH/vendor/autoload.php`
 * 02.	Registrar el autoload de la aplicación
 *		Buscará las clases dentro de la carpeta `JCA_PATH`
 *		(ObjRoute, ReRoute, ...)
 * 03.	Leer el archivo `JCA_PATH/configs/functions.php`
 * 04.	Leer el archivo `JCA_PATH/configs/config.php`
 * 05.	Leer el archivo `JCA_PATH/configs/init.php`
 * 06.	Registrar la función `shutdown` para completar el proceso del REQUEST
 *		+ Si el tipo de RESPONSE es 'FILE' o 'manual'
 *		  - Se omite la ejecución ya que el buffer puede que se envíe al navegador por bloques
 *		+ Si el tipo de RESPONSE es 'JSON'
 *		  - Retorna la data en formato json minificado
 *		+ Si el tipo de RESPONSE es 'JS' o 'CSS'
 *		  - El buffer es comprimido (buscar cache)
 *		+ Si el tipo de RESPONSE es 'IMAGE'
 *		  - Se efectúa posibles procesos previo a enviar el buffer
 *		    tales como reducción de tamaño, corte de medidas u otros. (se cachea el resultado)
 *		+ Si el tipo de RESPONSE es 'BodyContent'
 *		  - Se retorna solo el buffer
 *		+ Por defecto el tipo de RESPONSE es 'HTML'
 *		  - Se carga la estructura utilizada por lo que se añade los tags <html><header><body>
 *
 * 07.	Establecer el tipo de RESPONSE por defecto que retornará
 *	 	Considerar el tipo de RESPONSE por defecto:
 * 			+ JSON,			si se envió un $_GET['json'] o es un REQUEST ejecutado por comando o se detecta una extensión en la URI igual a 'json'
 * 			+ BodyContent,	si se envió un $_GET['_'] === 'co' o $_GET['_'] === 'bc'
 * 			+ JS,			si se detecta una extensión en la URI igual a 'js'
 * 			+ CSS,			si se detecta una extensión en la URI igual a 'css'
 * 			+ IMAGE,		si se detecta una extensión de imagen en la URI (png, jpg, jpeg, ico, gif, svg, webp, ...)
 * 		Cada vez que se cambie el tipo de RESPONSE:
 * 			+ Se comprueba si hay contenido en el buffer; en caso haya, enviarlo al ErrorControl como alerta
 * 			+ Limpiar y detener la captura del buffer
 * 			+ Capturar el buffer de salida excepto si el tipo de RESPONSE es 'FILE' o 'manual'
 *
 * 08.	Limpiar la URI de los IDs que contiene (comprobar el listado de formatos URI para detectar posibles IDs tipo texto)
 * 09.	Ejecutar `Process\Auth` y `Process\Authenticate` 		para solicitar la Comprobación de Sesión de Usuario
 * 10.	Ejecutar `ObjRoute\[namespace y clase basada en URI]` 	para comprobar la existencia de las IDs recibidas
 * 11.	Ejecutar `ReRoute\[namespace y clase basada en URI]` 	para modificar la URI por otra con el que se continúa el proceso
 *		Comprobar el WWW y el HTTPS aquí
 * 12.	Ejecutar `AlwRoute\[namespace y clase basada en URI]` 	para comprobar si el usuario requiere de permisos para el request solicitado
 * 13.	Ejecutar `PreRequest\[namespace y clase basada en URI]` para ejecutar alguna acción previo al proceso oficila del request
 * 14.	Ejecutar `Request\[namespace y clase basada en URI]` 	para ejecutar el proceso del Request
 * 15.	Si tipo de RESPONSE es diferente a 'BodyContent' y 'HTML' efectuar un `exit`
 * 16.	Ejecutar `Response\[namespace y clase basada en URI]` 	para retornar la pantalla HTML correspondiente al Request
 */
class Processor
{
	use ComponenteTrait;

	public function init ()
	{
		//=== Corrigiendo directorio base cuando se ejecuta como comando
		ISCOMMAND and
		chdir(JCA_PATH);

		//=== Leer el archivo `JCA_PATH/vendor/autoload.php`
		if ($file = JCA_PATH . DS . 'vendor' . DS . 'autoload.php' and file_exists($file))
		{
			require_once $file;
		}

		//=== Registrar el autoload de la aplicación
		spl_autoload_register([$this, '_autoload'], true, true);

		//=== Leer el archivo `JCA_PATH/configs/functions.php`
		if ($file = JCA_PATH . DS . 'configs' . DS . 'functions.php' and file_exists($file))
			require_once $file;

		//=== Leer el archivo `JCA_PATH/configs/config.php`
		if ($file = JCA_PATH . DS . 'configs' . DS . 'config.php' and file_exists($file))
			require_once $file;

		//=== Leer el archivo `JCA_PATH/configs/init.php`
		if ($file = JCA_PATH . DS . 'configs' . DS . 'init.php' and file_exists($file))
			require_once $file;

		//=== Registrar la función `shutdown` para completar el proceso del REQUEST
		/*
		 *		+ Si el tipo de RESPONSE es 'FILE' o 'manual'
		 *		  - Se omite la ejecución ya que el buffer puede que se envíe al navegador por bloques
		 *		+ Si el tipo de RESPONSE es 'JSON'
		 *		  - Retorna la data en formato json minificado
		 *		+ Si el tipo de RESPONSE es 'JS' o 'CSS'
		 *		  - El buffer es comprimido (buscar cache)
		 *		+ Si el tipo de RESPONSE es 'IMAGE'
		 *		  - Se efectúa posibles procesos previo a enviar el buffer
		 *		    tales como reducción de tamaño, corte de medidas u otros. (se cachea el resultado)
		 *		+ Si el tipo de RESPONSE es 'BodyContent'
		 *		  - Se retorna solo el buffer
		 *		+ Por defecto el tipo de RESPONSE es 'HTML'
		 *		  - Se carga la estructura utilizada por lo que se añade los tags <html><header><body>
		 */

		//=== Establecer el tipo de RESPONSE por defecto que retornará
		/*
		 *	 	Considerar el tipo de RESPONSE por defecto:
		 * 			+ JSON,			si se envió un $_GET['json'] o es un REQUEST ejecutado por comando o se detecta una extensión en la URI igual a 'json'
		 * 			+ BodyContent,	si se envió un $_GET['_'] === 'co' o $_GET['_'] === 'bc'
		 * 			+ JS,			si se detecta una extensión en la URI igual a 'js'
		 * 			+ CSS,			si se detecta una extensión en la URI igual a 'css'
		 * 			+ IMAGE,		si se detecta una extensión de imagen en la URI (png, jpg, jpeg, ico, gif, svg, webp, ...)
		 * 		Cada vez que se cambie el tipo de RESPONSE:
		 * 			+ Se comprueba si hay contenido en el buffer; en caso haya, enviarlo al ErrorControl como alerta
		 * 			+ Limpiar y detener la captura del buffer
		 * 			+ Capturar el buffer de salida excepto si el tipo de RESPONSE es 'FILE' o 'manual'
		 */
		$URI = '';
		$IDS = '';
		

		//=== Limpiar la URI de los IDs que contiene 
		/*
		 * utilizar JCA :: $METADATA['INITIAL_URI_FORMAT'] primero para obtener posibles IDs; caso contrario, buscar todos los números dentro)
		 */
		

		//=== Ejecutar `Process\Auth` y `Process\Authenticate` 		para solicitar la Comprobación de Sesión de Usuario
		if (class_exists('Process\Auth'))
			new Process\Auth ();

		if (class_exists('Process\Authenticate'))
			new Process\Authenticate ();

		//=== Ejecutar todas las clases para cada pre-procesador
		$PREREQUESTS_CLASSES = JCA :: $METADATA_COMPILED['PREREQUESTS_CLASSES'];

		foreach ($PREREQUESTS_CLASSES as $namespace_base)
		{
			$this -> executeUriClassFunction ($namespace_base);
		}

		//=== Ejecutar `Request\[namespace y clase basada en URI]` 	para ejecutar el proceso del Request
		$this -> executeUriClassFunction ('Request');

		//=== Si tipo de RESPONSE es diferente a 'BodyContent' y 'HTML' efectuar un `exit`
		if ( ! in_array(JCA :: getResponseType(), ['BodyContent', 'HTML']))
			exit;

		//=== Ejecutar `Response\[namespace y clase basada en URI]` 	para retornar la pantalla HTML correspondiente al Request
		$this -> executeUriClassFunction ('Response');
		exit;
	}

	public function executeUriClassFunction (string $namespace_base):bool
	{
		list($clase, $funcion, $parametros) = JCA :: searchUriClass ($namespace_base);

		if (is_null($clase))
			return false;

		try
		{
			$_reflect  = new ReflectionClass($clase);
			$instance = $_reflect -> newInstanceArgs($IDS);
		}
		catch(Exception $e)
		{
			// Class {Clase Llamada} does not have a constructor, so you cannot pass any constructor arguments
			if ( ! preg_match('/does not have a constructor/i', $e->getMessage()))
				throw $e;

			$instance = new $clase();
		}

		$request_method = mb_strtoupper(JCA :: getRequestMethod());
		$response_type  = mb_strtoupper(JCA :: getResponseType());

		foreach([$request_method . '_', ''] as $x)
		{
			foreach([$response_type . '_', ''] as $y)
			{
				if ($metodo = [$instance, $x . $y . $funcion] and is_callable($metodo))
				{
					call_user_func_array($metodo, $parametros);
					return true;
				}

				if ($metodo = [$instance, $y . $x . $funcion] and is_callable($metodo))
				{
					call_user_func_array($metodo, $parametros);
					return true;
				}
			}
		}

		return false; ## Se leyó la clase pero la función no ha sido encontrada
	}

	public function _autoload (string $class)
	{
		static $_bs = '\\'; // BackSlash
		static $_autoloads;

		if ( ! isset($_autoloads))
		{
			$METADATA   = JCA :: $METADATA_COMPILED;
			$_autoloads = [
				'namespaces'  => $METADATA['AUTOLOAD_NAMESPACES'],
				'directories' => $METADATA['AUTOLOAD_DIRS'],
			];
		}

		extract($_autoloads);

		$class = trim($class, $_bs);
		$parts = explode($_bs, $class);

		//=== Buscar por Namespace
		if (isset($namespaces))
		if (isset($namespaces[$parts[0]]))
		{
			$directory = $namespaces[$parts[0]];
			array_shift($parts); # Quitar el namespace base

			$filename = JCA_PATH . $directory . DS . implode(DS, $parts) . '.php';
			if ( ! file_exists($filename))
				return; // Next Autoload

			require_once $filename;
			return;
		}

		//=== Buscar en Directories
		$filename_base = DS . implode(DS, $parts) . '.php';
		if (isset($directories))
		foreach ($directories as $directory)
		{
			$filename = JCA_PATH . $directory . $filename_base;
			if ( ! file_exists($filename))
				return; // Next Autoload

			require_once $filename;
			return;
		}
	}
}