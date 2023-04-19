<?php
/**
 * Helper/Directories.php
 * @filesource
 */

namespace Helper;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * Helper\Directories
 *
 * Funciones Estáticas Disponibles:
 * regularizeDirectory(string):string|null	La ruta devuelta tiene el formato correcto del directorio (Devuelve NULL en caso no exista el directorio)
 * cleanDirectory(string, bool):bool		Limpia todos los archivos y directorios de una carpeta
 * unlinkDirectory(string):bool				Elimina por completo un directorio
 * copyDirectory(string, string, bool):int	Copia un directorio a otro destino
 * getFilesOnDir(string):array				Obtener un listado de archivos
 * mkdir2(string, string?):string			Crear un directorio usando una base
 */
class Directories
{
	public static function regularizeDirectory (string $ruta)
	{
		if (($_temp = realpath($ruta)) !== FALSE)
		{
			$ruta = $_temp;
		}
		else
		{
			$ruta = strtr(
				rtrim($ruta, '/\\'),
				'/\\',
				DS . DS
			);
		}

		if ( ! file_exists($ruta) ||  ! is_dir($ruta))
		{
			return null;
		}

		return $ruta;
	}

	public static function cleanDirectory (string $directorio, bool $delete_subdirs = true):bool
	{
		if ( ! file_exists($directorio) or ! is_dir($directorio))
			return true;

		//=== Limpiando archivos y carpetas dentro del directorio
		$files_in_directory = scandir($directorio);

		foreach ($files_in_directory as $filename)
		{
			if (in_array($filename, ['.', '..']))
				continue;

			$file_or_dir = $directorio . DS . $filename;

			if (is_dir($file_or_dir))
			{
				static :: cleanDirectory ($file_or_dir);

				$delete_subdirs and 
				@rmdir ($file_or_dir);
			}

			if (file_exists($file_or_dir) and ! is_dir($file_or_dir))
				@unlink ($file_or_dir);
		}

		return true;
	}

	public static function unlinkDirectory (string $directorio):bool
	{
		if ( ! file_exists($directorio) or ! is_dir($directorio))
			return true;

		//=== Limpiando archivos y carpetas dentro del directorio
		static :: cleanDirectory ($directorio, true);

		//=== Eliminando la carpeta
		@rmdir ($directorio);

		return true;
	}

	public static function copyDirectory (string $origen, string $destino, bool $eliminar_contenido = false, bool $sobreescribir_archivos = true, $permisos = 0777):int
	{
		if ( ! file_exists($origen) or ! is_dir($origen))
			return -1;

		$eliminar_contenido and
		static :: cleanDirectory ($destino, true);

		$files_in_origen = scandir($origen);
		$total_copias = 0;

		foreach ($files_in_origen as $filename)
		{
			if (in_array($filename, ['.', '..']))
				continue;

			$file_or_dir_origen  = $origen  . DS . $filename;
			$file_or_dir_destino = $destino . DS . $filename;

			if (is_dir($file_or_dir_origen))
			{
				$total_copias += static :: copyDirectory ($file_or_dir_origen, $file_or_dir_destino, false, $permisos);
				continue;
			}

			file_exists($destino) or
			mkdir($destino, 0777, true); # Crear el destino en caso no exista

			if (file_exists($file_or_dir_destino) and ! $sobreescribir_archivos)
				continue;

			@copy($file_or_dir_origen, $file_or_dir_destino);
			$total_copias++;
		}

		chmod($origen, $permisos);

		return $total_copias;
	}

	public static function getFilesOnDir (string $directorio):array
	{
		if ( ! file_exists($directorio) or ! is_dir($directorio))
			return [];

		$data = scandir($directorio);
		$data = array_filter($data, function($o){
			return ! in_array($o, ['.', '..']);
		});
		$data = array_values($data);

		$data = array_map(function($o) use ($directorio) {
			return $directorio . DS . $o;
		}, $data);

		$files = array_filter($data, function ($o) {
			return ! is_dir($o);
		});
		$files = array_values($files);

		$dirs = array_filter($data, function ($o) {
			return is_dir($o);
		});
		$dirs = array_values($dirs);

		foreach ($dirs as $dir)
		{
			$files = array_merge($files, static :: getFilesOnDir($dir));
		}

		$files = array_unique($files);
		$files = array_values($files);

		return $files;
	}

	public static function mkdir (string $path, string $base = APPPATH):string
	{
		$_chars = ['/','.','*','+','?','|','(',')','[',']','{','}','\\','$','^','-'];
		$path = preg_replace('/^' . preg_replace('/(\\' . implode('|\\', $_chars).')/', "\\\\$1", $base) . '/i', '', $path);

		$path = strtr($path, '/\\', DS . DS);
		$path = trim ($path);
		$path = trim ($path, DS);

		$return = realpath($base);

		if (empty($path))
			return $return;

		$path = explode(DS, $path);

		foreach ($path as $subdir)
		{
			if (empty($subdir))
				continue;

			$return .= DS . $subdir;

			if ( ! file_exists($return))
				mkdir($return);

			if ( ! file_exists($return . DS . 'index.htm'))
				file_put_contents($return . DS . 'index.htm', '');
		}

		return $return;
	}
}