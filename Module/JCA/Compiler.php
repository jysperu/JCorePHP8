<?php
/**
 * JCore/Module/JCA/Compiler.php
 * @filesource
 */

namespace JCore\Module\JCA;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore\ComponenteTrait;
use JCore as JCoreInstance;
use JCore\JCA;
use Exception;

use JCore\Controller\Directories as DirectoriesTrait;

use JCore\Helper\Array;

/**
 * Compiler
 * Encargado de compilar toda la aplicación
 *
 * Archivo `JCA_PATH/jca.json`
 *
 * Validaciones:
 * 01.	Si no existe el archivo GENERAR ARCHIVO
 * 02.	Cargar la metadata del archivo (leerlo)
 * 03.	Comprobar los directorios iniciales,
 *		Si se han añadido o eliminado alguno; entonces, GENERAR ARCHIVO
 *		No considerar los que se añadieron al leer el archivo `/load.php` de los directorios iniciales
 * 04.	Comprobar las fechas de todos los directorios,
 *		Si alguno es diferente al de la metadata; entonces, GENERAR ARCHIVO
 * 05.	Comprobar las rutas de los $AUTOLOAD,
 *		Si han cambiado; entonces, GENERAR ARCHIVO
 * 06.	Ejecutar una función de comprobación asignada manualmente,
 *		Si retorna FALSE; entonces, GENERAR ARCHIVO
 *
 *
 * Al GENERAR ARCHIVO:
 * 01.	Se unifican los requires de los composer.json
 * 02.	Se ejecuta `composer install`:						`JCA_PATH/vendor/autoload.php`
 * 03.	Se unifican los archivos de funciones:				`JCA_PATH/configs/functions.php`
 *   	Incluir funciones por defecto de JCore
 *		(ejemplo: XONK para limpiar los datos recibidos)
 * 04.	Se copian los archivos de $AUTOLOAD_NAMESPACES:		`JCA_PATH/[directorio]/[namespace y clase].php`
 * 04.	Se copian los archivos de $AUTOLOAD_DIRS:			`JCA_PATH/[directorio]/[namespace y clase].php`
 *
 * 04.	Se unifican los archivos inicializadores:			`JCA_PATH/configs/init.php`
 * 05.	Se simplifican las configuración:					`JCA_PATH/configs/config.php`
 *
 * 06.	Se copian los pre-procesadores $AUTOLOAD_ROUTES:	`JCA_PATH/[directorio]/[namespace y clase].php`
 *		(ObjRoute, ReRoute, AlwRoute, PreRequest, ...)
 *
 * 07.	Se copian los procesadores del Request:				`JCA_PATH/[$AUTOLOAD_REQUEST_DIR]/[namespace y clase].php`
 * 08.	Se copian las pantallas HTML:						`JCA_PATH/[$AUTOLOAD_RESPONSE_DIR]/[namespace y clase].php`
 * 09.	Se copian las estructuras de las pantallas:			`JCA_PATH/[$AUTOLOAD_STRUCTURE_DIR]/[namespace y clase].php`
 *
 * 10.	Se copian los procesos:								`JCA_PATH/[$AUTOLOAD_PROCESSES_DIR]/[namespace y clase].php`
 *
 * 11.	Se compilan las tablas por cada DB
 *		Posibilidad múltiples DBs y de diferentes drivers
 *		Se deben compilar los archivos de objetos			`JCA_PATH/objects/[namespace y clase].php`
 *
 *
 * > Los objetos no se asocian a una base datos específica pero debe haber una alerta en caso de intentar guardar un registro en una base datos que no se instalo el objeto
 * > Si el objeto se instala en varias base datos entonces todos deben contar con todos los campos; caso contrario, produciría error
 * > Se comunica con el módulo XONK para controlar posibles cambios no autorizados de los archivos compilados
 */
class Compiler
{
	use ComponenteTrait;
	use DirectoriesTrait;

	protected $JCore;

	public function init (JCoreInstance $JCore)
	{
		$this -> JCore = $JCore;

	 	//=== Si no existe el archivo GENERAR ARCHIVO
		if ($file = JCA :: METADATA_COMPILED and ! file_exists($file))
			return $this -> compilar('JCA_Compiler_' . __LINE__);

		//=== El archivo existe, Cargar la metadata del archivo (JSON)
		$json = file_get_contents($file);
		$json = json_decode($json, true);

		//=== Si el contenido no es JSON entonces GENERAR ARCHIVO
		if (is_null($json))
			return $this -> compilar('JCA_Compiler_' . __LINE__);

		//=== Es probable que múltiples requests intenten acceder mientras se está compilando
		if (
			isset($json['compiling']) and 
			$json['compiling'] >= (time() - (60 * 5)) # El proceso no debe pasar de 05 minutos
		)
		{
			exit('<b>En mantenimiento</b><br>En breve el sistema se habrá compilado en su totalidad.');
		}

	 	//=== Comprobar los directorios iniciales (HASH MD5)
		$MD5_INITDIRS = md5(json_encode($this -> getInitialDirectories ()));
		if ( ! isset($json['MD5_INITDIRS']) or $json['MD5_INITDIRS'] !== $MD5_INITDIRS)
			return $this -> compilar('JCA_Compiler_' . __LINE__);

	 	//=== Comprobar las fechas de todos los directorios (FILEMTIME)
		if ( ! isset($json['DIRECTORIES_MTIME']))
			return $this -> compilar('JCA_Compiler_' . __LINE__);

		foreach ($json['DIRECTORIES_MTIME'] as $directory => $filemtime)
		{
			if ( ! file_exists($directory) or ! is_dir($directory) or filemtime($directory) > $filemtime)
				return $this -> compilar('JCA_Compiler_' . __LINE__);
		}

		//=== Comprobar las rutas de los $AUTOLOAD
		$MD5_JCORECNFG = md5(json_encode([$this -> getAutoloadsNamespace (), $this -> getAutoloadsDirectories (), $this -> getDirectoriesToCompile ()]));
		if ( ! isset($json['MD5_JCORECNFG']) or $json['MD5_JCORECNFG'] !== $MD5_JCORECNFG)
			return $this -> compilar('JCA_Compiler_' . __LINE__);

		//=== Ejecutar una función de comprobación asignada manualmente
		$function_or_bool = $JCore :: $RECOMPILAR;

		if (is_bool($function_or_bool) and $function_or_bool === TRUE)
			return $this -> compilar('JCA_Compiler_' . __LINE__);

		if (is_callable($function_or_bool) and $function_or_bool (
			$json,    # JCA :: $METADATA_COMPILED	(JSON de la metadata de la última compilación)
			$file,    # JCA :: METADATA_COMPILED	(Archivo en el cual se aloja el metadata)
			JCA_PATH, # JCA :: PATH					(Directorio donde se encuentra alojado el JCA)
			$JCore,   # JCoreInstance
			$this
		) === TRUE)
			return $this -> compilar('JCA_Compiler_' . __LINE__);

		JCA :: $METADATA_COMPILED = $json;
	}

	public function getInitialDirectories ():array
	{
		static $_dirs;

		isset($_dirs) or
		$_dirs = $this
				 -> JCore
				 -> getDirectories();

		return $_dirs;
	}

	public function getAutoloadsNamespace ():array
	{
		$JCore = $this -> JCore;

		return array_merge (
			(array) $JCore :: $AUTOLOAD_NAMESPACES,
			(array) $JCore :: $AUTOLOAD_ROUTES,
			[
				'Request'   => (string) $JCore :: $AUTOLOAD_REQUEST_DIR,
				'Response'  => (string) $JCore :: $AUTOLOAD_RESPONSE_DIR,
				'Structure' => (string) $JCore :: $AUTOLOAD_STRUCTURE_DIR,
				'Process'   => (string) $JCore :: $AUTOLOAD_PROCESSES_DIR,
			],
			[]
		);
	}

	public function getAutoloadsDirectories ():array
	{
		$JCore = $this -> JCore;

		return (array) $JCore :: $AUTOLOAD_DIRS;
	}

	public function getDirectoriesToCompile ():array
	{
		$JCore = $this -> JCore;

		return (array) $JCore :: $COMPILER_EXTRA_DIRS;
	}

	public function compilar (string $COMPILER_BY = null)
	{
		$json = [];
		$json['$C'] = [
			'B'	=> $COMPILER_BY ?? 'MANUALATTEMP',
			'T' => filemtime(__FILE__),
			'S' => [microtime(true), memory_get_usage()],
		];

		$JCore = $this -> JCore;

		//=== Prevenir que el requests se caiga y no se complete la compilación
		ignore_user_abort(true);
		set_time_limit(0);

		//=== Si no existe la carpeta crearla
		file_exists(JCA_PATH) or
		mkdir(JCA_PATH, 0777, true);

		//=== Alojar temporalmente un JSON indicando que se ha iniciado la compilación para prevenir que otro REQUEST haga lo mismo
		$file = JCA :: METADATA_COMPILED;
		file_put_contents($file, json_encode(['compiling' => time()]));

		//=== Añadiendo los atributos INITIAL_DIRECTORIES y MD5_INITDIRS
		$json['INITIAL_DIRECTORIES'] = $this -> getInitialDirectories ();
		$json['MD5_INITDIRS']        = md5(json_encode($json['INITIAL_DIRECTORIES']));

		//=== Recorrer todos los directorios en busca del archivo `/load.php` que añada mas directorios
		$revisados = [];

		$JCore :: addDirectory($JCore :: getJCoreDir() . DS . 'BaseAPP', 0, 'JCoreBaseAPP');

		do
		{
			$procesados = 0;

			$directories = $JCore -> getDirectories ();
			foreach ($directories as $directory)
			{
				if (in_array($directory, $revisados))
					continue; # Ya ha sido revisado

				$revisados[] = $directory;

				if ($load_php = $directory . DS . 'load.php' and ! file_exists($load_php))
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

		//=== Establecer el atributo DIRECTORIES_NAMES
		$json['DIRECTORIES_NAMES'] = $JCore :: getDirectoriesLabels();

		//=== Obtener los filemtime de todos los directorios 
		//    Solo cambian si el mismo directorio cambia o algún archivo dentro cambia
		//    (no afectan los archivos dentro de los subdirectorios que contiene)
		$json['DIRECTORIES_MTIME'] = [];
		$directories = $JCore -> getDirectories ();

		foreach ($directories as $directory)
		{
			if ( ! file_exists($directory) or ! is_dir($directory))
				continue;

			$json['DIRECTORIES_MTIME'][$directory] = filemtime($directory);
		}

		//=== Considerando solo los verdaderos directorios que existen y son directorios
		$directories_999_1 = array_keys($json['DIRECTORIES_MTIME']); # Directorios de mayor prioridad primero (Permite anticipar funciones)
		$directories_1_999 = array_reverse($directories_999_1);      # Directorios de menor prioridad primero (Permite sobreescribir configuraciones o clases)

		//=== Estableciendo el atributo MD5_JCORECNFG utilizando los datos originales de AUTOLOAD_NAMESPACES y AUTOLOAD_DIRS
		$AUTOLOAD_NAMESPACES   = $this -> getAutoloadsNamespace ();
		$AUTOLOAD_DIRS		   = $this -> getAutoloadsDirectories ();
		$COMPILER_EXTRA_DIRS   = $this -> getDirectoriesToCompile ();
		$json['MD5_JCORECNFG'] = md5(json_encode([$AUTOLOAD_NAMESPACES, $AUTOLOAD_DIRS, $COMPILER_EXTRA_DIRS]));

		$AUTOLOAD_NAMESPACES_flip = array_flip  ($AUTOLOAD_NAMESPACES);
		$AUTOLOAD_NAMESPACES_dirs = array_values($AUTOLOAD_NAMESPACES);

		//=== Estableciendo los directorios a copiar (Todos deben tener el slash al inicio pero no al final)
		$directories_to_copy = array_unique( array_merge(
			$AUTOLOAD_NAMESPACES_dirs,
			$AUTOLOAD_DIRS,
			$COMPILER_EXTRA_DIRS
		) );

		$directories_with_files = [];

		//=== Eliminar archivos existentes de los directorios a copiar dentro del JCA_PATH
		foreach ($directories_to_copy as $subdirectory)
		{
			static :: unlinkDirectory (JCA_PATH . $subdirectory);
		}

		//=== Copiando los archivos de los directorios de $AUTOLOAD_NAMESPACES, $AUTOLOAD_DIRS y $COMPILER_EXTRA_DIRS
		foreach ($directories_1_999 as $directory)
		{
			foreach ($directories_to_copy as $subdirectory)
			{
				if (file_exists($directory . $subdirectory) and is_dir($directory . $subdirectory))
				{
					$total_archivos_copiados = static :: copyDirectory (
						$directory . $subdirectory, 
						JCA_PATH . $subdirectory, 
						false, # El contenido ya ha sido eliminado previamente
						true   # Sobreescribir los archivos
					);

					$total_archivos_copiados > 0 and
					$directories_with_files[] = $subdirectory;
				}
			}
		}

		$this -> compilarConfig ($directories_1_999);
			

		
		
		//=== Filtrando los autoloads que si tienen archivos en sus directorios
		$directories_with_files = array_unique($directories_with_files);

		$AUTOLOAD_NAMESPACES = array_filter($AUTOLOAD_NAMESPACES, function ($directory) use ($directories_with_files) {
			return in_array($directory, $directories_with_files);
		});
		$AUTOLOAD_DIRS       = array_filter($AUTOLOAD_DIRS, function ($directory) use ($directories_with_files) {
			return in_array($directory, $directories_with_files);
		});
		$AUTOLOAD_DIRS       = array_values($AUTOLOAD_DIRS);

		$json['AUTOLOAD_NAMESPACES'] = $AUTOLOAD_NAMESPACES;
		$json['AUTOLOAD_DIRS']       = $AUTOLOAD_DIRS;

		
		
		
		
		
		
		file_put_contents($file, json_encode($json));
		JCA :: $METADATA_COMPILED = $json;

		echo __FILE__ .'#'.__LINE__, '<br>';
	}

	protected function _compilarConfig (array $directories):void
	{
		$config = [];

		foreach ($directories as $directory)
		{
			foreach ([
				DS . 'config.php',
				DS . 'configs' . DS . 'config.php',
				DS . 'config-dist.php',
				DS . 'configs' . DS . 'config-dist.php',
			] as $filename)
			{
				if ($config_file = $directory . $filename and file_exists($config_file))
				{
					try
					{
						require_once $config_file; ## Los archivos deben modificar el valor $config
					}
					catch (Exception $e)
					{}
				}
			}
		}

		$config_content = '';
		$config_content.= '<?' . 'php' . '# Compilado el ' . date('d/m/Y h:i:s A') . PHP_EOL;
		$config_content.= '' . PHP_EOL;
		$config_content.= '$config = ' .  . PHP_EOL;

		$config_file = JCA_PATH . DS . 'configs' . DS . 'config.php';
		mkdir(dirname($config_file), 0777, true);

		file_put_contents($config_file, $config_content);
	}
}