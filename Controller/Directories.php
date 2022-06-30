<?php
/**
 * JCore/Controller/Directories.php
 * @filesource
 */

namespace JCore\Controller;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * Directories
 *
 * regularizeDirectory():string
 */
trait Directories
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
				@unlink ($file_or_dir);
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
		@unlink ($directorio);

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
}