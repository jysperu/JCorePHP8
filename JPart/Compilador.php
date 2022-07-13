<?php
namespace JCore\JPart;

use Exception;
use Phar;
use JCore\JPart\ComposerJSON;
use Symfony;
use Composer;
use JCore\JPart\Helper as HelperTrait;

trait Compilador
{
	use HelperTrait;

	public static function requiereCompilar ():int
	{
		//=== Validar si existe el archivo JSON
		if ($file = static :: JCA_json and ! file_exists($file))
			return __LINE__;


		//=== El archivo existe -> cargar la metadata del archivo (JSON)
		$json = file_get_contents($file);
		$json = json_decode($json, true);


		//=== Si el contenido no es JSON entonces GENERAR ARCHIVO
		if (is_null($json))
			return __LINE__;


		//=== Comprobar los directorios iniciales (HASH MD5)
		$MD5_INITDIRS = md5(json_encode(static :: getInitialDirectories ()));

		if ( ! isset($json['MD5_INITDIRS']) or $json['MD5_INITDIRS'] !== $MD5_INITDIRS)
			return __LINE__;

		//=== Comprobar las fechas de todos los directorios (FILEMTIME)
		if ( ! isset($json['DIRECTORIES_MTIME']))
			return __LINE__;

		$directories = $json['DIRECTORIES_MTIME'];
		foreach ($directories as $directory => $filemtime)
		{
			if ( ! file_exists($directory))
				return __LINE__; # El directorio debería existir

			if ( ! is_dir($directory))
				return __LINE__; # La ruta indicada no es directorio

			if (filemtime($directory) > $filemtime)
				return __LINE__; # El directorio ha sido modificado
		}

		//=== Comprobar las rutas de los $AUTOLOAD
		$MD5_JCORECNFG = md5(json_encode([
			static :: getAutoloadsNamespace  (), 
			static :: getAutoloadsDirectories(), 
			static :: getDirectoriesToCompile(),
		]));

		if ( ! isset($json['MD5_JCORECNFG']) or $json['MD5_JCORECNFG'] !== $MD5_JCORECNFG)
			return __LINE__;

		//=== Ejecutar una función de comprobación asignada manualmente
		$function_or_bool = static :: $RECOMPILAR;

		if (is_bool($function_or_bool) and $function_or_bool === TRUE)
			return __LINE__;

		if (is_callable($function_or_bool))
		{
			$result = call_user_func_array($function_or_bool, [
				$json,   # Metadata de la última compilación
				$file,   # Archivo JSON donde aloja la metadata
				APPPATH, # Directorio donde se encuentra alojado el JCA
			]);

			if ($result)
				return __LINE__;
		}

		return 0; # No requiere compilar
	}

	public static function getInitialDirectories ():array
	{
		static $_dirs;
		isset ($_dirs) or $_dirs = static :: getDirectories();
		return $_dirs;
	}

	public static function getAutoloadsNamespace ():array
	{
		return array_merge (
			(array) static :: $AUTOLOAD_NAMESPACES,
			(array) static :: $AUTOLOAD_ROUTES,

			[
				'Request'   => (string) static :: $AUTOLOAD_REQUEST_DIR,
				'Response'  => (string) static :: $AUTOLOAD_RESPONSE_DIR,
				'Structure' => (string) static :: $AUTOLOAD_STRUCTURE_DIR,
				'Process'   => (string) static :: $AUTOLOAD_PROCESSES_DIR,
				'Driver'    => (string) static :: $AUTOLOAD_DRIVERS_DIR,
			],

			[]
		);
	}

	public static function getAutoloadsDirectories ():array
	{
		return array_merge (
			(array) static :: $AUTOLOAD_DIRS,

			[]
		);
	}

	public static function getDirectoriesToCompile ():array
	{
		return array_merge (
			(array) static :: $COMPILER_EXTRA_DIRS,

			[]
		);
	}

	protected static function compileLoads ():void
	{
		static :: maintenance('<b>Compilando...</b><br>Leyendo los archivos "load.php".', 60); # Máximo 60s

		$revisados = [];

		//=== Agregar el directorio base del JCore
		static :: addDirectory(JCorePATH . DS . 'BaseAPP', 0, 'JCoreBaseAPP');

		do
		{
			$procesados  = 0;
			$directories = static :: getDirectories ();

			foreach ($directories as $directory)
			{
				if (in_array($directory, $revisados))
					continue; # Ya ha sido revisado

				$revisados[] = $directory;
				$load_php    = $directory . DS . 'load.php';

				if ( ! file_exists($load_php))
					continue; # No hay archivo load.php en el directorio

				try
				{
					require_once $load_php;
				}
				catch (Exception $e)
				{
					
				}
				finally
				{
					$procesados++;
				}
			}
		}
		while($procesados > 0);
	}

	protected static function compileDirectoriesMtime (array &$json):void
	{
		static :: maintenance('<b>Compilando...</b><br>Cacheando el "mtime" de los directorios leídos.', 10); # Máximo 10s

		$json['DIRECTORIES_MTIME'] = [];
		$directories = static :: getDirectories ();

		//=== Obtener los filemtime de todos los directorios 
		//    Solo cambian si el mismo directorio cambia o algún archivo dentro cambia
		//    (no afectan los archivos dentro de los subdirectorios que contiene)

		foreach ($directories as $directory)
		{
			if ( ! file_exists($directory) or ! is_dir($directory))
				continue;

			$json['DIRECTORIES_MTIME'][$directory] = filemtime($directory);
		}
	}

	protected static function compileDirectoriesCopy (array &$AUTOLOAD_NAMESPACES, array $AUTOLOAD_DIRS, array $COMPILER_EXTRA_DIRS, array $AUTOLOAD_NAMESPACES_dirs, array $directories):void
	{
		static :: maintenance('<b>Compilando...</b><br>Copiando archivos al directorio compilado.', 60); # Máximo 60s

		$directories_with_files = [];
		$classes_directory = DS . 'classes';

		//=== Estableciendo los directorios a copiar (Todos deben tener el slash al inicio pero no al final)
		$directories_to_copy = array_unique( array_merge(
			$AUTOLOAD_NAMESPACES_dirs,
			[$classes_directory],
			$COMPILER_EXTRA_DIRS
		) );


		//=== Eliminar archivos existentes de los directorios a copiar dentro del APPPATH
		foreach ($directories_to_copy as $subdirectory)
		{
			static :: unlinkDirectory (APPPATH . $subdirectory);
		}

		//=== Copiando los archivos de los directorios de $AUTOLOAD_NAMESPACES, $AUTOLOAD_DIRS y $COMPILER_EXTRA_DIRS
		foreach ($directories as $directory)
		{
			foreach ($directories_to_copy as $subdirectory)
			{
				if (file_exists($directory . $subdirectory) and is_dir($directory . $subdirectory))
				{
					$total_archivos_copiados = static :: copyDirectory (
						$directory . $subdirectory, 
						APPPATH . $subdirectory, 
						false, # El contenido ya ha sido eliminado previamente
						true   # Sobreescribir los archivos
					);

					$total_archivos_copiados > 0 and
					$directories_with_files[] = $subdirectory;
				}

				if ($subdirectory === $classes_directory)
				{
					foreach($AUTOLOAD_DIRS as $subdirectory)
					{
						if (file_exists($directory . $subdirectory) and is_dir($directory . $subdirectory))
						{
							$total_archivos_copiados = static :: copyDirectory (
								$directory . $subdirectory, 
								APPPATH . $classes_directory, 
								false, # El contenido ya ha sido eliminado previamente
								true   # Sobreescribir los archivos
							);
						}
					}
				}
			}
		}

		//=== Filtrando los autoloads que si tienen archivos en sus directorios
		$directories_with_files = array_unique($directories_with_files);
		$AUTOLOAD_NAMESPACES = array_filter($AUTOLOAD_NAMESPACES, function ($directory) use ($directories_with_files) {
			return in_array($directory, $directories_with_files);
		});
	}

	protected static function compileComposer (array $directories):void
	{
		$composer_dir = static :: getComposerDirectory ();
		if (is_null($composer_dir))
			return;

		static :: maintenance('<b>Compilando...</b><br>Generando único archivo de composer.', 30); # Máximo 30s

		$composer_json = new ComposerJSON();

		foreach ($directories as $directory)
		{
			if ($json_file = $directory . DS . 'composer.json' and file_exists($json_file) and $json = json_decode(file_get_contents($json_file), true) and is_array($json))
			{
				$all = $directory === SRCPATH;
				$composer_json -> mergeConfig ($json, $all);
			}
		}

		$composer_json = $composer_json -> getConfig ();
		$composer_file = APPPATH . DS . 'composer.json';

		file_put_contents($composer_file, json_encode($composer_json, JSON_PRETTY_PRINT));

		static :: maintenance('<b>Compilando...</b><br>Ejecutando composer update -n -o.', 60); # Máximo 60s

		putenv('COMPOSER_HOME=' . $composer_dir);
		require_once($composer_dir . DS . 'vendor' . DS . 'autoload.php');

//		file_exists(APPPATH . DS . 'vendor') and 
//		static :: unlinkDirectory (APPPATH . DS . 'vendor');

//		file_exists(APPPATH . DS . 'composer.lock') and 
//		unlink (APPPATH . DS . 'composer.lock');

		$input  = new Symfony\Component\Console\Input\StringInput('update -n -o -d ' . str_replace('\\', '\\\\', APPPATH));
		$output = new Symfony\Component\Console\Output\StreamOutput(fopen($composer_dir . DS . 'compile.log','w'));
        $app    = new Composer\Console\Application();
        $app -> run($input, $output);

		static :: maintenance('<b>Compilando...</b><br>Comando composer ejecutado.', 5); # Máximo 5s
	}

	protected static function getComposerDirectory ():string
	{
		$dir =  defined('COMPOSER_PATH') ?
				COMPOSER_PATH : 
				((defined('ROOTPATH') ? ROOTPATH : JCorePATH) . DS . 'Composer');

		$min = time() - (60 * 60 * 24 * 7 * 4 * 3); # 03 meses de antigüedad
		if (file_exists($dir) and filemtime($dir) >= $min)
		{
			return $dir;
		}

		static :: maintenance('<b>Compilando...</b><br>Descargar instalador de composer.', 120); # Máximo 120s

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
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_FILE, fopen($installerFile, 'w+'));

		if ( ! curl_exec($ch))
		{
			echo '[' . date('h:i:s A') . ']', 'Error downloading ' . $installerURL . PHP_EOL;
			static :: maintenance('<b>Compilando...</b><br>Error al descargar instalador de composer.', 5); # Máximo 5s
			return null;
		}

		echo '[' . date('h:i:s A') . ']', 'Installer found : ' . $installerFile . PHP_EOL;

		echo '[' . date('h:i:s A') . ']', 'Preventing Exit...' . PHP_EOL;
		$installerFile_content = file_get_contents($installerFile);
		$installerFile_content = preg_replace('/ exit\((.*)\);/', ' echo \'[\' . date(\'h:i:s A\') . \']\' , ($1);return;', $installerFile_content);
		file_put_contents($installerFile, $installerFile_content);

		echo '[' . date('h:i:s A') . ']', 'Starting installation...' . PHP_EOL;
		static :: maintenance('<b>Compilando...</b><br>Instalando composer.', 60); # Máximo 60s

		$argv = [];
		include $installerFile;
		echo '[' . date('h:i:s A') . ']', 'End installation...' . PHP_EOL;

		echo '[' . date('h:i:s A') . ']', 'Extracting composer.phar' . PHP_EOL;
		static :: maintenance('<b>Compilando...</b><br>Extrayendo composer.phar.', 20); # Máximo 20s

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
		static :: maintenance('<b>Compilando...</b><br>Composer descargado e instalado correctamente.', 5); # Máximo 5s
		ob_end_clean();

		return $dir;
	}

	protected static function var_export_string ($str):string
	{
		$str = htmlspecialchars($str);
		$str = str_replace('\\', '\\\\', $str);
		$str = str_replace('\'', '\\\'', $str);
		return '\'' . $str . '\'';
	}

	protected static function var_export_number ($str):string
	{
		$valor = (double)$str * 1;

		if ($valor <= PHP_FLOAT_MAX  and $valor >= -PHP_FLOAT_MAX)
		{
			return '\'' . $str . '\'';
		}

		return $valor;
	}

	protected static function var_export (mixed $dato, int $_level = 1):string
	{
		$tab  = str_repeat("\t", ($_level - 1));
		$tab2 = str_repeat("\t", $_level);

		if (is_null($dato))
		{
			return 'NULL';
		}

		if (is_bool($dato))
		{
			return $dato ? 'true' : 'false';
		}

		if (is_array($dato))
		{
			if (static :: isList ($dato, true) and isset($dato[0]))
			{ # Es lista con los números correlativos y comienza desde cero
				$return = '[' . PHP_EOL;
				foreach($dato as $v)
				{
					$return .= $tab2 . static :: var_export($v, $_level +1) . ',' . PHP_EOL;
				}
				$return.= $tab . ']';

				return $return;
			}

			if (static :: isList ($dato))
			{ # Es lista con índices numéricos pero no seguidos
				$return = '[' . PHP_EOL;
				foreach($dato as $k => $v)
				{
					$return .= $tab2 . $k . ' => ' . static :: var_export($v, $_level +1) . ',' . PHP_EOL;
				}
				$return.= $tab . ']';

				return $return;
			}

			{ # No Es lista
				$return = '[' . PHP_EOL;
				foreach($dato as $k => $v)
				{
					$return .= $tab2 . static :: var_export_string($k) . ' => ' . static :: var_export($v, $_level +1) . ',' . PHP_EOL;
				}
				$return.= $tab . ']';

				return $return;
			}
		}

		if (preg_match('/^(?:[\+\-])[0-9]+(?:\.[0-9]+)$/', $dato))
		{
			return static :: var_export_number($dato);
		}

		{
			return static :: var_export_string($dato);
		}
	}

	protected static function compileConfig (array $directories):void
	{
		static :: maintenance('<b>Compilando...</b><br>Generando único archivo de configuración.', 30); # Máximo 30s

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

		if (count($config) === 0)
			return;

		$config_content = '';
		$config_content.= '<?' . 'php' . ' # Compilado el ' . date('d/m/Y h:i:s A') . PHP_EOL;
		$config_content.= 'defined(\'HOMEPATH\') or exit(2); # Acceso directo no autorizado' . PHP_EOL;
		$config_content.= '' . PHP_EOL;
		$config_content.= 'return ' . static :: var_export($config) . ';' . PHP_EOL;

		$config_file = APPPATH . DS . 'configs' . DS . 'config.php';
		@mkdir(dirname($config_file), 0777, true);
		file_put_contents($config_file, $config_content);
	}

	protected static function compileInit (array $directories):void
	{
		static :: maintenance('<b>Compilando...</b><br>Generando único archivo init.php.', 30); # Máximo 30s

		$CONTENT = '<?' . 'php' . ' # Compilado el ' . date('d/m/Y h:i:s A') . PHP_EOL;
		$CONTENT.= 'defined(\'HOMEPATH\') or exit(2); # Acceso directo no autorizado' . PHP_EOL;
		$CONTENT.= '' . PHP_EOL;

		$CONTENT_META = [];
		$CONTENT_lines = count(explode(PHP_EOL, $CONTENT));

		foreach ($directories as $directory)
		{
			foreach ([
				DS . 'init.php',
				DS . 'configs' . DS . 'init.php',
				DS . 'init-dist.php',
				DS . 'configs' . DS . 'init-dist.php',
			] as $filename)
			{
				if ($init_file = $directory . $filename and file_exists($init_file))
				{
					$content_part = file_get_contents($init_file);
					$content_part = trim($content_part);

					if ( ! empty($content_part))
					{
						$content_part = explode('<?php', $content_part, 2);
						$content_part = $content_part[1]; ## Quitando la apertura del PHP inicial (si hay algún contenido antes es eliminado)

						$content_part = explode('?>', $content_part);
						if (count($content_part) > 1)
						{
							$content_part_end = end($content_part);
							$content_part_end = explode('<?php', $content_part_end, 2);
							if (count($content_part_end) === 1)
								array_pop($content_part); ## Hay un cierre pero no una apertura (todo el contenido siguiente es eliminado)
						}

						$content_part = implode('?>', $content_part);
						$content_part = trim($content_part);

						$content_part = explode("\n", $content_part);
						$content_part = implode(PHP_EOL, $content_part);

						if ( ! empty($content_part))
						{
							$content_part_lines = count(explode(PHP_EOL, $content_part));
							$CONTENT_META[] = [
								'file'  => $init_file,
								'from'  => $CONTENT_lines,
								'to'    => $CONTENT_lines + $content_part_lines - 1,
								'lines' => $content_part_lines,
							];

							$CONTENT.= $content_part . PHP_EOL . PHP_EOL;
							$CONTENT_lines += $content_part_lines + 1;
						}
					}
				}
			}
		}

		if (count($CONTENT_META) === 0)
			return;

		$init_file = APPPATH . DS . 'configs' . DS . 'init.php';
		@mkdir(dirname($init_file), 0777, true);
		file_put_contents($init_file, $CONTENT);
		file_put_contents($init_file . '.json', json_encode($CONTENT_META, JSON_PRETTY_PRINT));
	}

	protected static function compileFunctions (array $directories):void
	{
		static :: maintenance('<b>Compilando...</b><br>Generando único archivo functions.php.', 30); # Máximo 30s

		$CONTENT = '<?' . 'php' . ' # Compilado el ' . date('d/m/Y h:i:s A') . PHP_EOL;
		$CONTENT.= 'defined(\'HOMEPATH\') or exit(2); # Acceso directo no autorizado' . PHP_EOL;
		$CONTENT.= '' . PHP_EOL;

		$CONTENT_META = [];
		$CONTENT_lines = count(explode(PHP_EOL, $CONTENT));

		foreach ($directories as $directory)
		{
			$files = [];
			$dir   = $directory . DS . 'configs-dist' . DS . 'functions'     and $files = array_merge($files, static :: getFilesOnDir($dir));
			$dir   = $directory . DS . 'configs' . DS . 'functions-dist'     and $files = array_merge($files, static :: getFilesOnDir($dir));
			$dir   = $directory . DS . 'configs' . DS . 'functions'          and $files = array_merge($files, static :: getFilesOnDir($dir));
			$file  = $directory . DS . 'configs' . DS . 'functions-dist.php' and file_exists($file) and $files[] = $file;
			$file  = $directory . DS . 'functions-dist.php'                  and file_exists($file) and $files[] = $file;
			$file  = $directory . DS . 'configs' . DS . 'functions.php'      and file_exists($file) and $files[] = $file;
			$file  = $directory . DS . 'functions.php'                       and file_exists($file) and $files[] = $file;

			$files = array_unique($files);
			$files = array_values($files);

			foreach ($files as $funcs_file)
			{
				$content_part = file_get_contents($funcs_file);
				$content_part = trim($content_part);

				if ( ! empty($content_part))
				{
					$content_part = explode('<?php', $content_part, 2);
					$content_part = $content_part[1]; ## Quitando la apertura del PHP inicial (si hay algún contenido antes es eliminado)

					$content_part = explode('?>', $content_part);
					if (count($content_part) > 1)
					{
						$content_part_end = end($content_part);
						$content_part_end = explode('<?php', $content_part_end, 2);
						if (count($content_part_end) === 1)
							array_pop($content_part); ## Hay un cierre pero no una apertura (todo el contenido siguiente es eliminado)
					}

					$content_part = implode('?>', $content_part);
					$content_part = trim($content_part);

					$content_part = explode("\n", $content_part);
					$content_part = implode(PHP_EOL, $content_part);

					if ( ! empty($content_part))
					{
						$content_part_lines = count(explode(PHP_EOL, $content_part));
						$CONTENT_META[] = [
							'file'  => $funcs_file,
							'from'  => $CONTENT_lines,
							'to'    => $CONTENT_lines + $content_part_lines - 1,
							'lines' => $content_part_lines,
						];

						$CONTENT.= $content_part . PHP_EOL . PHP_EOL;
						$CONTENT_lines += $content_part_lines + 1;
					}
				}
			}
		}

		// Incluir el reemplazador de nombres de los directorios base
		// Incluir el buscador de la clase específica
		
//		if (count($CONTENT_META) === 0)
//			return;

		$funcs_file = APPPATH . DS . 'configs' . DS . 'functions.php';
		@mkdir(dirname($funcs_file), 0777, true);
		file_put_contents($funcs_file, $CONTENT);
		file_put_contents($funcs_file . '.json', json_encode($CONTENT_META, JSON_PRETTY_PRINT));
	}

	protected static function compileIndex (array $json):void
	{
		static :: maintenance('<b>Compilando...</b><br>Generando archivo index.php.', 5); # Máximo 5s

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

		$content.= '/** Modo mantenimiento */' . PHP_EOL;
		$content.= 'if (file_exists(\'maintenance.php\'))' . PHP_EOL;
		$content.= '	return require_once(\'maintenance.php\');' . PHP_EOL;
		$content.= 'elseif (file_exists(\'maintenance.admin.php\'))' . PHP_EOL;
		$content.= '	return require_once(\'maintenance.admin.php\');' . PHP_EOL;
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


		$content.= '/** Registrar el autoload de la aplicación */' . PHP_EOL;
		$content.= 'spl_autoload_register(function(string $class){' . PHP_EOL;
		$content.= '	$class = trim($class, BS);' . PHP_EOL;
		$content.= '	$parts = explode(BS, $class);' . PHP_EOL;
		$content.= '	$nbase = $parts[0];' . PHP_EOL;
		$content.= '' . PHP_EOL;

		$AUTOLOAD_NAMESPACES = $json['AUTOLOAD_NAMESPACES'];
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
			$content.= '		$filename = APPPATH . $directory . DS . implode(DS, $parts) . \'.php\';' . PHP_EOL;
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
			$content.= '	$filename = APPPATH . DS . \'classes\' . DS . implode(DS, $parts) . \'.php\';' . PHP_EOL;
			$content.= '	if ( ! file_exists($filename))' . PHP_EOL;
			$content.= '		return; # Next Autoload' . PHP_EOL;
			$content.= '' . PHP_EOL;

			$content.= '	require_once $filename;' . PHP_EOL;
			$content.= '	return;' . PHP_EOL;
		}

		$content.= '}, true, true);' . PHP_EOL;
		$content.= '' . PHP_EOL;

		$content.= '/** Proteger el REQUEST de todo posible ataque */' . PHP_EOL;
		$content.= 'XONK :: protect();' . PHP_EOL;
		$content.= '' . PHP_EOL;

		$content.= '/** Escuchar todos los errores generados para poder gestionarlos y corregirlos */' . PHP_EOL;
		$content.= 'ErrorControl :: listen();' . PHP_EOL;
		$content.= '' . PHP_EOL;

		if ($file = APPPATH . DS . 'vendor' . DS . 'autoload.php' and file_exists($file))
		{
			$content.= '/** Leer el `vendor/autoload.php` */' . PHP_EOL;
			$content.= 'require_once \'vendor' . DS . 'autoload.php\';' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		if ($file = APPPATH . DS . 'configs' . DS . 'functions.php' and file_exists($file))
		{
			$content.= '/** Leer el `configs/functions.php` */' . PHP_EOL;
			$content.= 'require_once \'configs' . DS . 'functions.php\';' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		$content.= '/** Instanciar APP */' . PHP_EOL;
		$content.= 'APP :: instance();' . PHP_EOL;
		$content.= '' . PHP_EOL;

		if ($file = APPPATH . DS . 'configs' . DS . 'init.php' and file_exists($file))
		{
			$content.= '/** Leer el `configs/init.php` */' . PHP_EOL;
			$content.= 'require_once \'configs' . DS . 'init.php\';' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		if ($file = APPPATH . DS . 'processes' . DS . 'Auth.php' and file_exists($file))
		{
			$content.= '/** Ejecutar `Process\Auth` */' . PHP_EOL;
			$content.= 'new Process\\Auth ();' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		if ($file = APPPATH . DS . 'processes' . DS . 'Authenticate.php' and file_exists($file))
		{
			$content.= '/** Ejecutar `Process\Authenticate` */' . PHP_EOL;
			$content.= 'new Process\\Authenticate ();' . PHP_EOL;
			$content.= '' . PHP_EOL;
		}

		$content.= '' . PHP_EOL;
		$content.= '' . PHP_EOL;
		$content.= '' . PHP_EOL;
		$content.= '' . PHP_EOL;
		$content.= '' . PHP_EOL;
		$content.= '' . PHP_EOL;
		$content.= '' . PHP_EOL;
		$content.= '' . PHP_EOL;

		$debug = time() + 5;
		$content.= '$diff = ' . (time() + 27) . ' - time();' . PHP_EOL;
		$content.= 'if ($diff < 0)' . PHP_EOL;
		$content.= '{' . PHP_EOL;
		$content.= '	@unlink(__FILE__);' . PHP_EOL;
		$content.= '	$diff = 0;' . PHP_EOL;
		$content.= '}' . PHP_EOL;
		$content.= 'echo \'<br><b>DEBUG</b>: Se borrará el archivo index.php para recompilar la aplicación.<br>\', __FILE__ . \'#\' . __LINE__, \'<br><small>Tiempo Restante: \', (string) $diff, \'s</small><script>setTimeout(function(){location.reload()}, \', ($diff * 1000 + 1), \');</script>\';' . PHP_EOL;

		file_put_contents(APPPATH . DS . 'index.php', $content);
	}
}