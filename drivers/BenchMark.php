<?php
/*!
 * drivers/BenchMark.php
 * @filesource
 */
namespace Driver
{
	defined('APPPATH') or exit(0); ## Acceso directo no autorizado

	use Driver\BenchMark\Point;
	use Driver\BenchMark\Result;

	/**
	 * BenchMark
	 * @package Driver
	 */
	class BenchMark
	{
		/**
		 * $points
		 * Listado de puntos registrados
		 *
		 * {
		 *     string => Driver\BenchMark\Point
		 * }
		 */
		protected static $points = [];

		/**
		 * $points_keys
		 * Listado de puntos registrados
		 * string[]
		 */
		protected static $points_keys = [];

		/**
		 * $points_hooks
		 * Listado de funciones que se encuentran a la espera de ejecutarse
		 * cuando se marke un punto específico
		 *
		 * {
		 *     string => callable[]
		 * }
		 */
		protected static $points_hooks = [];

		/**
		 * mark()
		 * Registra un punto de control
		 *
		 * @params string|Driver\BenchMark\Point $key
		 * @params float $time Optional
		 * @params int $memory null
		 * @return Driver\BenchMark\Point
		 *
		 * @toDo Registrar el archivo y la línea donde se registro el punto
		 */
		public static function mark (string|Point $key, ? float $time = null, ? int $memory = null): Point
		{
			$Point = is_string($key) ? new Point ($key, $time, $memory) : $key;
			$key = (string) $Point;
			static :: $points [$key] = $Point;
			static :: $points_keys[] = $key;

			/** @toDo Ejecutar todos los hooks del punto */

			return $Point;
		}

		/**
		 * markFirstPoint()
		 * Registra un punto de control inicial
		 */
		public static function markFirstPoint (): void
		{
			static $key = 'init';

			if (isset(static :: $points [$key]))
				return; ## ya se encuentra el punto `init`

			/**
			 * exec_start_time
			 * @internal
			 * Valor utilizado para testear tiempos de procesos
			 */
			defined('exec_start_time')   or define('exec_start_time',   microtime(true));

			/**
			 * exec_start_memory
			 * @internal
			 * Valor utilizado para testear memoria utilizada
			 */
			defined('exec_start_memory') or define('exec_start_memory', memory_get_usage());

			static :: $points [$key] = new Point ($key);
			static :: $points_keys[] = $key;

			/** @toDo Ejecutar todos los hooks del punto `init` */
		}

		/**
		 * onMarked()
		 * Función que registra un $callback a ejecutar al momento que se marque un punto específico
		 *
		 * > Parámetro enviado al $callback es el Punto registrado (Driver\BenchMark\Point)
		 * @toDo
		 */
		public static function onMarked (string|Point $key, callable $callback): void
		{
			// comprobar si el punto ya se registro entonces ejecutar el hook inmediatamente
		}

		/**
		 * since()
		 * Función que compara el tiempo y la memoria entre 02 puntos
		 *
		 * > Si no se envían parametros se realizará una comparación desde la información en ese momento hasta el punto `init`
		 * > Si se envía un solo parámetro se realiza una comparación desde la información en ese momento hasta ese punto
		 * > Si se envía ambos parámetros entonces se realiza la comparación entre ambos puntos
		 *
		 * @params string|Driver\BenchMark\Point $key1	Optional
		 * @params string|Driver\BenchMark\Point $key2	Optional
		 * @return Driver\BenchMark\Result
		 *
		 * @toDo
		 */
		public static function since (string|Point $key1 = null, string|Point $key2 = null): Result
		{
			
		}
	}
}

namespace Driver\BenchMark
{
	use Driver\BenchMark as BenchMarkInstance;

	/**
	 * Point
	 * @package Driver\BenchMark
	 */
	class Point
	{
		/**
		 * $_key
		 * Valor del código del punto
		 */
		protected $_key;

		/**
		 * $_time
		 * Valor del momento en el que se registró el punto
		 */
		protected $_time;

		/**
		 * $_memory
		 * Valor de la memoria utiliza
		 */
		protected $_memory;

		/**
		 * __construct()
		 * Retorna el código del punto
		 *
		 * @params string $key
		 * @params float $time Optional
		 * @params int $memory Optional
		 */
		public function __construct (string $key, ? float $time = null, ? int $memory = null)
		{
			is_null($time)   and $time   = microtime(true);
			is_null($memory) and $memory = memory_get_usage();

			$this -> _key    = $key;
			$this -> _time   = $time;
			$this -> _memory = $memory;
		}

		/**
		 * get_key()
		 * Retorna el código del punto
		 *
		 * @return string
		 */
		public function get_key (): string
		{
			return $this -> _key;
		}

		/**
		 * __toString()
		 * Retorna el código del punto
		 *
		 * @return string
		 */
		public function __toString (): string
		{
			return $this -> get_key();
		}

		/**
		 * get_time()
		 * Retorna el valor del tiempo
		 *
		 * @return float
		 */
		public function get_time (): float
		{
			return $this -> _time;
		}

		/**
		 * set_time()
		 * Actualiza el valor del tiempo
		 *
		 * @params float $time
		 * @return Driver\BenchMark\Point
		 */
		public function set_time (float $time): Point
		{
			$this -> _time = $time;
			return $this;
		}

		/**
		 * get_memory()
		 * Retorna el valor de la memoria
		 *
		 * @return int
		 */
		public function get_memory (): int
		{
			return $this -> _memory;
		}

		/**
		 * set_memory()
		 * Actualiza el valor de la memoria
		 *
		 * @params int $memory
		 * @return Driver\BenchMark\Point
		 */
		public function set_memory (int $memory): Point
		{
			$this -> _memory = $memory;
			return $this;
		}

		/**
		 * since()
		 * Función que compara el tiempo y la memoria entre 02 puntos
		 *
		 * @params string|Driver\BenchMark\Point $key	Optional, Si el parámetro no se envía entonces se compara con el punto `init` (Primer punto);
		 *													caso contrario, se compara entre el punto enviado y este punto
		 * @return Driver\BenchMark\Result
		 */
		public function since (string|Point $key = null): Result
		{
			return BenchMarkInstance :: since ($this -> _key, $key ?? 'init');
		}
	}

	/**
	 * Result
	 * @package Driver\BenchMark
	 *
	 * @toDo Clase que contendrá la información de 02 puntos comparados
	 *		 y debe contener funciones para obtener la diferencia de tiempos y
	 *		 la memoria utilizada entre ambos puntos
	 */
	class Result
	{
		
	}
}
