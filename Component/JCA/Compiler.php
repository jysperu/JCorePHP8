<?php
/**
 * JCore/Component/JCA/Compiler.php
 * @filesource
 */

namespace JCore\Component\JCA;
defined('JCA_PATH') or exit(0); // Se requiere la ruta del JCore Compiled Aplication

use JCore;
use JCore\JCA;
use Exception;
use JCore\ComponenteTrait;
use JCore\Controller\Directories as DirectoriesTrait;
use JCore\Helper;
use JCore\ComposerJSON;
use Phar;
use Symfony;
use Composer;

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

	public function init ()
	{
		$JCore = JCore :: instance();

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
		$JCore = JCore :: instance();

		isset($_dirs) or
		$_dirs = $JCore :: getDirectories();

		return $_dirs;
	}

	public function getAutoloadsNamespace ():array
	{
		$JCore = JCore :: instance();

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
		$JCore = JCore :: instance();

		return (array) $JCore :: $AUTOLOAD_DIRS;
	}

	public function getDirectoriesToCompile ():array
	{
		$JCore = JCore :: instance();

		return (array) $JCore :: $COMPILER_EXTRA_DIRS;
	}

	public function compilar (string $COMPILER_BY = null)
	{
		$JCore = JCore :: instance();

		$json =& JCA :: $METADATA_COMPILED;
		$json['$C'] = [
			'B'	=> $COMPILER_BY ?? 'MANUALATTEMP',
			'T' => filemtime(__FILE__),
			'S' => [microtime(true), memory_get_usage()],
		];

		//=== Prevenir que el requests se caiga y no se complete la compilación
		ignore_user_abort(true);
		set_time_limit(0);

		//=== Si no existe la carpeta crearla
		file_exists(JCA_PATH) or
		mkdir(JCA_PATH, 0777, true);

		//=== Alojar temporalmente un JSON indicando que se ha iniciado la compilación para prevenir que otro REQUEST haga lo mismo
		$file = JCA :: METADATA_COMPILED;
//		file_put_contents($file, json_encode(['compiling' => time()]));

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
			[DS . 'classes'],
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

				if ($subdirectory === DS . 'classes')
				{
					foreach($AUTOLOAD_DIRS as $subdirectory)
					{
						if (file_exists($directory . $subdirectory) and is_dir($directory . $subdirectory))
						{
							$total_archivos_copiados = static :: copyDirectory (
								$directory . $subdirectory, 
								JCA_PATH . DS . 'classes', 
								false, # El contenido ya ha sido eliminado previamente
								true   # Sobreescribir los archivos
							);
						}
					}
				}
			}
		}

		//=== Compilar $config
		$this -> _compilarConfig ($directories_1_999);

		//=== Compilar composer.json
		$this -> _compilarComposer ($directories_1_999);

		//=== Filtrando los autoloads que si tienen archivos en sus directorios
		$directories_with_files = array_unique($directories_with_files);

		$AUTOLOAD_NAMESPACES = array_filter($AUTOLOAD_NAMESPACES, function ($directory) use ($directories_with_files) {
			return in_array($directory, $directories_with_files);
		});
		$PREREQUESTS_CLASSES = array_keys($JCore :: $AUTOLOAD_ROUTES);
		$PREREQUESTS_CLASSES = array_filter($PREREQUESTS_CLASSES, function ($clase) use ($AUTOLOAD_NAMESPACES) {
			return isset($AUTOLOAD_NAMESPACES[$clase]);
		});
		$PREREQUESTS_CLASSES = array_values($PREREQUESTS_CLASSES);

		$json['AUTOLOAD_NAMESPACES'] = $AUTOLOAD_NAMESPACES;
		$json['PREREQUESTS_CLASSES'] = $PREREQUESTS_CLASSES;

		JCA :: $METADATA_COMPILED = $json;
		file_put_contents($file, json_encode($json));

		//=== Compilar index.php
		$this -> _compilarIndexPHP ();

		die(__FILE__ . '#' . __LINE__);
	}

	/**
	 * _compilarComposer()
	 * Genera un nuevo composer.json basado en los composer.json encontrados en los directorios de los módulos
	 *
	 * Se clonan los datos siguientes:
	 * - name				string			APPPATH
	 * - description		string			APPPATH
	 * - version			version			APPPATH
	 * - type				string			APPPATH
	 * - keywords			array(string)	Todos
	 * - homepage			url				APPPATH
	 * - time				datetime		APPPATH		YYYY-MM-DD HH:MM:SS
	 * - license			array(string)	Todos
	 * - authors			array(object)	Todos		['name', 'email', 'homepage', 'role']
	 * - support			object			APPPATH		['email', 'issues', 'forum', 'wiki', 'irc', 'source', 'docs', 'rss', 'chat']
	 * - funding			array(object)	Todos		['type', 'url']
	 * - require			object|array	Todos		repository => version@flag#ref		||		repository		||	php => version		|| 		ext-{extension} => version
	 * - minimum-stability	string			APPPATH		stable		||		dev, alpha, beta, RC
	 * - prefer-stable		bool			APPPATH		true
	 * - repositories		array(object)	Todos		['type', 'url', 'options', 'package']
	 * - config				object			APPPATH
	 *
	 * Los datos siguientes son omitidos:
	 * - readme				file			APPPATH
	 * - require-dev		object|array	Todos		repository => version@flag#ref		||		repository		||	php => version		|| 		ext-{extension} => version
	 * - conflict			array(object)	Todos
	 * - replace			array(object)	Todos
	 * - provide			array(object)	Todos
	 * - suggest			object			Todos		repository => message
	 * - autoload[psr-4]	object			APPPATH		namespace => directory		||		namespace => [directories]
	 * - autoload[psr-0]	object			APPPATH		namespace => directory		||		namespace => [directories]
	 * - autoload[classmap]	array			APPPATH		directories		||		patter of directory
	 * - autoload[files]	array			APPPATH		files
	 * - autoload[exclude-from-classmap]
	 * - autoload-dev[psr-4]
	 * - autoload-dev[psr-0]
	 * - autoload-dev[classmap]
	 * - autoload-dev[files]
	 * - autoload-dev[exclude-from-classmap]
	 * - scripts
	 * - extra
	 */
	protected function _compilarComposer (array $directories):void
	{
		$composer_json = new ComposerJSON();

		$APPPATH = defined('APPPATH') ? APPPATH : null;

		foreach ($directories as $directory)
		{
			if ($json_file = $directory . DS . 'composer.json' and file_exists($json_file) and $json = json_decode(file_get_contents($json_file), true) and is_array($json))
			{
				$is_apppath = $directory === $APPPATH;
				$composer_json -> mergeConfig ($json, $is_apppath);
			}
		}

		$composer_json = $composer_json -> getConfig ();

		$composer_file = JCA_PATH . DS . 'composer.json';
		@mkdir(dirname($composer_file), 0777, true);

		file_put_contents($composer_file, json_encode($composer_json, JSON_PRETTY_PRINT));

		$composer_dir = $this -> _download_composer ();
		if (is_null($composer_dir))
			return;

		set_time_limit(-1);
    	putenv('COMPOSER_HOME=' . $composer_dir);

		require_once($composer_dir . DS . 'vendor' . DS . 'autoload.php');

		file_exists(JCA_PATH . DS . 'vendor') and 
		static :: unlinkDirectory (JCA_PATH . DS . 'vendor');

		file_exists(JCA_PATH . DS . 'composer.lock') and 
		unlink (JCA_PATH . DS . 'composer.lock');

		$input  = new Symfony\Component\Console\Input\StringInput('update -n -o -d ' . str_replace('\\', '\\\\', JCA_PATH));
		$output = new Symfony\Component\Console\Output\StreamOutput(fopen($composer_dir . DS . 'compile.log','w'));
        $app    = new Composer\Console\Application();
        $app -> run($input, $output);
	}

	protected function _compilarIndexPHP  ():void
	{
		$content = '<?' . 'php' . PHP_EOL;
		$content.= '/**' . PHP_EOL;
		$content.= ' * JCore Compiled Aplication' . PHP_EOL;
		$content.= ' * Generado el ' . date('Y-m-d h:i:s A') . PHP_EOL;
		$content.= ' *' . PHP_EOL;
		$content.= ' * @filesource' . PHP_EOL;
		$content.= ' */' . PHP_EOL;
		$content.= PHP_EOL;


		$content.= '/** Corrigiendo directorio base */' . PHP_EOL;
		$content.= 'chdir(__DIR__);' . PHP_EOL;
		$content.= PHP_EOL;


		$content.= '/** Restaurar el buffer de salida a 1 */' . PHP_EOL;
		$content.= 'while (ob_get_level())' . PHP_EOL;
		$content.= '	ob_end_clean();' . PHP_EOL;
		$content.= PHP_EOL;



		$content.= '/** Definiendo variables básicas */' . PHP_EOL;
		$content.= 'defined(\'DS\') or define(\'DS\', DIRECTORY_SEPARATOR);' . PHP_EOL;
		$content.= 'defined(\'BS\') or define(\'BS\', \'\\\\\');' . PHP_EOL;
		$content.= PHP_EOL;

		$content.= '/** Definiendo rutas del sistema (informativo) */' . PHP_EOL;
		$content.= 'defined(\'HOMEPATH\') or define(\'HOMEPATH\', \'' . HOMEPATH . '\');' . PHP_EOL;
		$content.= 'defined(\'ROOTPATH\') or define(\'ROOTPATH\', \'' . ROOTPATH . '\');' . PHP_EOL;
		$content.= 'defined(\'COREPATH\') or define(\'COREPATH\', \'' . COREPATH . '\');' . PHP_EOL;
		$content.= 'defined(\'SRCPATH\')  or define(\'SRCPATH\',  \'' . SRCPATH . '\');' . PHP_EOL;
		$content.= 'defined(\'APPPATH\')  or define(\'APPPATH\',  __DIR__);' . PHP_EOL;
		$content.= PHP_EOL;



		$METADATA   = JCA :: $METADATA_COMPILED;
		$AUTOLOAD_NAMESPACES = $METADATA['AUTOLOAD_NAMESPACES'];
		$AUTOLOAD_DIRS       = $METADATA['AUTOLOAD_DIRS'];

		$content.= '/** Registrar el autoload de la aplicación */' . PHP_EOL;
		$content.= 'spl_autoload_register(function(string $class){' . PHP_EOL;
		$content.= '	$class = trim($class, BS);' . PHP_EOL;
		$content.= '	$parts = explode(BS, $class);' . PHP_EOL;
		$content.= '	$nbase = $parts[0];' . PHP_EOL;
		$content.= '' . PHP_EOL;

		if (count($AUTOLOAD_NAMESPACES) > 0)
		{
			$content.= '	/** Buscar en los namespaces definidos */' . PHP_EOL;
			$content.= '	$namespaces = [' . PHP_EOL;
			foreach ($AUTOLOAD_NAMESPACES as $k => $v)
			{
				$content.= '		\'' . htmlspecialchars($k) . '\' => \'' . htmlspecialchars($v) . '\',' . PHP_EOL;
			}
			$content.= '	];' . PHP_EOL;
			$content.= '' . PHP_EOL;
			$content.= '	if (isset($namespaces[$nbase]))' . PHP_EOL;
			$content.= '	{' . PHP_EOL;
			$content.= '		$directory = $namespaces[$nbase];' . PHP_EOL;
			$content.= '		array_shift($parts); # Quitar el namespace base' . PHP_EOL;
			$content.= '' . PHP_EOL;
			$content.= '		$filename = JCA_PATH . $directory . DS . implode(DS, $parts) . \'.php\';' . PHP_EOL;
			$content.= '		if ( ! file_exists($filename))' . PHP_EOL;
			$content.= '			return; # Next Autoload' . PHP_EOL;
			$content.= '' . PHP_EOL;
			$content.= '		require_once $filename;' . PHP_EOL;
			$content.= '		return;' . PHP_EOL;
			$content.= '	}' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		{
			$content.= '	/** Buscar en el directorio classes */' . PHP_EOL;
			$content.= '	$filename = JCA_PATH . DS . \'classes\' . DS . implode(DS, $parts) . \'.php\';' . PHP_EOL;
			$content.= '	if ( ! file_exists($filename))' . PHP_EOL;
			$content.= '		return; # Next Autoload' . PHP_EOL;
			$content.= '' . PHP_EOL;
			$content.= '	require_once $filename;' . PHP_EOL;
			$content.= '	return;' . PHP_EOL;
		}

		$content.= '});' . PHP_EOL;
		$content.= '' . PHP_EOL;



		$content.= '/** Proteger el REQUEST de todo posible ataque */' . PHP_EOL;
		$content.= 'XONK :: protect();' . PHP_EOL;
		$content.= '' . PHP_EOL;



		if ($file = JCA_PATH . DS . 'vendor' . DS . 'autoload.php' and file_exists($file))
		{
			$content.= '/** Leer el `vendor/autoload.php` */' . PHP_EOL;
			$content.= 'require_once \'vendor' . DS . 'autoload.php\';' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		if ($file = JCA_PATH . DS . 'configs' . DS . 'functions.php' and file_exists($file))
		{
			$content.= '/** Leer el `configs/functions.php` */' . PHP_EOL;
			$content.= 'require_once \'configs' . DS . 'functions.php\';' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		if ($file = JCA_PATH . DS . 'configs' . DS . 'config.php' and file_exists($file))
		{
			$content.= '/** Leer el `configs/config.php` */' . PHP_EOL;
			$content.= 'require_once \'configs' . DS . 'config.php\';' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		if ($file = JCA_PATH . DS . 'configs' . DS . 'init.php' and file_exists($file))
		{
			$content.= '/** Leer el `configs/init.php` */' . PHP_EOL;
			$content.= 'require_once \'configs' . DS . 'init.php\';' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}



		$content.= '/** Escuchar todos los errores generados para poder gestionarlos y corregirlos */' . PHP_EOL;
		$content.= 'ErrorControl :: listen();' . PHP_EOL;
		$content.= '' . PHP_EOL;



		if ($file = JCA_PATH . DS . 'processs' . DS . 'Auth.php' and file_exists($file))
		{
			$content.= '/** Ejecutar `Process\Auth` */' . PHP_EOL;
			$content.= 'new Process\\Auth ();' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		if ($file = JCA_PATH . DS . 'processs' . DS . 'Authenticate.php' and file_exists($file))
		{
			$content.= '/** Ejecutar `Process\Authenticate` */' . PHP_EOL;
			$content.= 'new Process\\Authenticate ();' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}


		file_put_contents(JCA_PATH . DS . 'index.php', $content);
	}

	protected function _download_composer  ():string
	{
		$basedir = defined('ROOTPATH') ? ROOTPATH : (JCore :: instance() -> getJCoreDir());
		$dir     = defined('COMPOSER_PATH') ? COMPOSER_PATH : ($basedir . DS . 'Composer');

		if (file_exists($dir) and filemtime($dir) >= (time() - (60*60*24*7*4)))
		{
			return $dir;
		}

		@mkdir($dir, 0777, true);

		$installerURL  = 'https://getcomposer.org/installer';
		$installerFile = $dir . DS . 'installer.php';
		$ComposerPhar  = $dir . DS . 'composer.phar';

		ob_start(function($buffer) use ($dir){
			file_put_contents($dir . DS . 'install.log', $buffer);
		});

		chdir($dir);

		echo '[' . date('Y-m-d h:i:s A') . ']', 'Starting' . PHP_EOL;
		echo '[' . date('h:i:s A') . ']', 'Downloading ' . $installerURL . PHP_EOL;

		$ch = curl_init($installerURL);
//		curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_FILE, fopen($installerFile, 'w+'));

		if ( ! curl_exec($ch))
		{
			echo '[' . date('h:i:s A') . ']', 'Error downloading ' . $installerURL . PHP_EOL;
			return null;
		}

		echo '[' . date('h:i:s A') . ']', 'Installer found : ' . $installerFile . PHP_EOL;

		echo '[' . date('h:i:s A') . ']', 'Preventing Exit...' . PHP_EOL;
		$installerFile_content = file_get_contents($installerFile);
		$installerFile_content = preg_replace('/ exit\((.*)\);/', ' echo \'[\' . date(\'h:i:s A\') . \']\' , ($1);return;', $installerFile_content);
		file_put_contents($installerFile, $installerFile_content);

		echo '[' . date('h:i:s A') . ']', 'Starting installation...' . PHP_EOL;

		$argv = [];
		include $installerFile;
		echo '[' . date('h:i:s A') . ']', 'End installation...' . PHP_EOL;

		echo '[' . date('h:i:s A') . ']', 'Extracting composer.phar' . PHP_EOL;
		$composer = new Phar($ComposerPhar);
        $composer->extractTo($dir);
        echo 'Extraction complete.' . PHP_EOL;

		echo '[' . date('h:i:s A') . ']', 'Preventing Exit 2...' . PHP_EOL;
		$aplicationFile = $dir . DS . 'src/Composer/Console/Application.php';
		$aplicationFile_content = file_get_contents($aplicationFile);
		$aplicationFile_content = preg_replace('/exit\(([0-9]+)\);/', 'return;', $aplicationFile_content, 1);
		$aplicationFile_content.= PHP_EOL . '# Modificado by JCore';
		file_put_contents($aplicationFile, $aplicationFile_content);

		echo '[' . date('h:i:s A') . ']', 'Preventing Exit 3...' . PHP_EOL;
		$aplicationFile = $dir . DS . 'vendor/Symfony/console/Application.php';
		$aplicationFile_content = file_get_contents($aplicationFile);
		$aplicationFile_content = str_replace('autoExit = true;', 'autoExit = false;', $aplicationFile_content);
		$aplicationFile_content.= PHP_EOL . '# Modificado by JCore';
		file_put_contents($aplicationFile, $aplicationFile_content);

		echo '[' . date('h:i:s A') . ']', 'All Correct' . PHP_EOL;

		ob_end_clean();

		return $dir;
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
		$config_content.= '<?' . 'php' . ' # Compilado el ' . date('d/m/Y h:i:s A') . PHP_EOL;
		$config_content.= '' . PHP_EOL;
		$config_content.= '$config = ' . '[];' . PHP_EOL;

		$config_file = JCA_PATH . DS . 'configs' . DS . 'config.php';
		@mkdir(dirname($config_file), 0777, true);

		file_put_contents($config_file, $config_content);
	}
}