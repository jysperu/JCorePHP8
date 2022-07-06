<?php
namespace JCore\JPart;

use Controller\Directories as DirectoriesTrait;

trait Directories
{
	use DirectoriesTrait;

	//=================================================================================//
	//==== DIRECTORIOS DE APLICACIÓN                                              =====//
	//=================================================================================//

	/**
	 * $_directories_by_priority
	 * Aloja todas los directorios de aplicaciones con la llave primaria equivalente a la prioridad asignada
	 */
	protected static $_directories_by_priority = [];

	/**
	 * $_directories_orden
	 * Aloja todas los directorios como llaves y el orden como valor
	 * Ayuda a identificar si se ha cargado ya un directorio de aplicación no esté en mas de 1 orden
	 */
	protected static $_directories_orden = [];

	/**
	 * $_directories_label
	 * Aloja todas los directorios en el orden generado por la prioridad
	 */
	protected static $_directories_label = [];

	/**
	 * $_directories_ordered_list
	 * Aloja todas los directorios en el orden generado por la prioridad
	 */
	protected static $_directories_ordered_list = [];

	/**
	 * $_directories_recents_added
	 * Aloja todas los nuevos directorios agregados
	 * Ayuda a identificar si ha habido nuevos directorios agregados previo a un proceso
	 */
	protected static $_directories_recents_added = [];

	/**
	 * addDirectory ()
	 * Función que permite añadir directorios de aplicación las cuales serán usados para buscar y procesar 
	 * la información para la solicitud del usuario
	 *
	 * @param String $directory 	Directorio a añadir
	 * @param Integer $prioridad	Prioridad de lectura del directorio
	 * @return self
	 */
	public static function addDirectory (string $directory, int $prioridad = 500, string $label = null):void
	{
		//=== Validar la existencia del directorio
		$directory = static :: regularizeDirectory ($directory);

		if (is_null($directory))
			return; # La ruta es inválida o no existe

		//== Guardar la etiqueta del directorio (En caso exista se actualiza)
		is_null($label) and 
		$label = $directory;

		static :: $_directories_label[$directory] = $label;

		//=== Comprobar la existencia del directorio
		if ( ! isset(static :: $_directories_orden[$directory]))
		{
			$existencia = 0; # No existe
		}
		elseif ($prioridad_actual = static :: $_directories_orden[$directory] and $prioridad_actual !== $prioridad)
		{
			$existencia = 1; # Existe pero el órden es diferente
		}
		else
		{
			return; # Existe y es el mismo orden
		}

		//=== Existe en un órden diferente así que eliminarlo de esa órden
		if ($existencia === 1 and ($index = array_search($directory, static :: $_directories_by_priority[$prioridad_actual])) !== false)
		{
			unset(static :: $_directories_by_priority[$prioridad_actual][$index]);
			static :: $_directories_by_priority[$prioridad_actual] = array_values(static :: $_directories_by_priority[$prioridad_actual]);
		}

		//=== Validando que la lista de la prioridad exista
		isset(static :: $_directories_by_priority[$prioridad]) or
		static :: $_directories_by_priority[$prioridad] = [];

		//=== Añadiendo el directorio a la orden
		static :: $_directories_by_priority[$prioridad][] = $directory;
		static :: $_directories_orden[$directory]         = $prioridad;
		static :: $_directories_recents_added[]           = $directory;

		//=== Cachear lista (Considerar que de prioridad mas alto es el número mayor)
		$_directories_ordered_list = [];

		$_directories_by_priority  = static :: $_directories_by_priority;
		ksort($_directories_by_priority);

		foreach($_directories_by_priority as $prioridad => $_directories)
		{
			foreach($_directories as $_directory)
			{
				$_directories_ordered_list[] = $_directory;
			}
		}

		$_directories_ordered_list = array_reverse($_directories_ordered_list); # Invertir ya que el de mayor número es prioritario

		static :: $_directories_ordered_list = $_directories_ordered_list;
	}

	/**
	 * getDirectories ()
	 * Función que retorna los directorios de aplicación
	 *
	 * @param Boolean $prioridad_menor_primero Indica si se retornará la lista de manera invertida
	 * @return Array
	 */
	public static function getDirectories (bool $prioridad_menor_primero = FALSE):array
	{
		$lista = static :: $_directories_ordered_list;

		$prioridad_menor_primero and
		$lista = array_reverse($lista);

		return $lista;
	}

	/**
	 * mapDirectories ()
	 * Función que ejecuta una función establecida con todos los directorios de aplicación como parametro
	 *
	 * @param Callable $callback Función a ejecutar
	 * @param Boolean $prioridad_menor_primero Indica si la función a ejecutar se hará a la lista invertida
	 * @return self
	 */
	public static function mapDirectories (callable $callback, bool $prioridad_menor_primero = FALSE):array
	{
		$lista = static :: getDirectories ($prioridad_menor_primero);
		return array_map($callback, $lista);
	}

	/**
	 * getDirectoriesLabels ()
	 * Función que retorna los directorios y sus nombres
	 *
	 * @param $prioridad_menor_primero Boolean Indica si se retornará la lista de manera invertida
	 * @return Array
	 */
	public static function getDirectoriesLabels ($prioridad_menor_primero = FALSE):array
	{
		$lista  = static :: $_directories_ordered_list;
		$labels = static :: $_directories_label;
		
		$prioridad_menor_primero and 
		$lista = array_reverse($lista);

		$lista = array_combine($lista, array_map(function($o) use ($labels) {
			return $labels[$o];
		}, $lista));

		return $lista;
	}

	public static function thereIsRecentsAddedDirectories (bool $clean = false):bool
	{
		$valor = count(static :: $_directories_recents_added);
		$clean and static :: $_directories_recents_added = [];
		return $valor;
	}
	
}