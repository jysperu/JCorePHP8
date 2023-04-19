<?php
/*!
 * Point.php
 * @filesource
 */
namespace BenchMark;
defined('APPPATH') or exit(0); // Acceso directo no autorizado

/**
 * Point
 */
use BenchMark as Base;

class Point
{
	protected $_key;
	protected $_punto_anterior;
	protected $_punto_siguiente;

	protected $_start_time;
	protected $_start_memory;

	protected $_is_ended = false;
	protected $_end_time;
	protected $_end_memory;

	public function __construct (string $key, Point $punto_anterior = null)
	{
		$this -> _key = $key;
		$this -> _punto_anterior = $punto_anterior;
	}

	public function start ()
	{
		$this -> _start_time   = microtime(true);
		$this -> _start_memory = memory_get_usage();
	}

	public function stop ()
	{
		$this -> _is_ended = true;

		if (isset($this -> _punto_siguiente) and ! $this -> _punto_siguiente -> isEnded ())
			$this -> _punto_siguiente -> end();

		$this -> _end_time   = microtime(true);
		$this -> _end_memory = memory_get_usage();

		Base :: _setPuntoAnterior ($this);
	}

	public function end ()
	{
		return $this -> stop();
	}

	public function getStartTime ()
	{
		return $this -> _start_time;
	}

	public function getStartMemory ()
	{
		return $this -> _start_memory;
	}

	public function getEndTime ()
	{
		return $this -> _end_time;
	}

	public function getEndMemory ()
	{
		return $this -> _end_memory;
	}

	public function isEnded ()
	{
		return $this -> _is_ended;
	}

	public function getTotalTime ():float
	{
		$this -> _is_ended or 
		$this -> stop();

		$_total_time = $this -> _end_time;
		$_total_time-= $this -> _start_time;

		return $_total_time;
	}

	public function getTotalMemory ():float
	{
		$this -> _is_ended or 
		$this -> stop();

		$_total_memory = $this -> _end_memory;
		$_total_memory-= $this -> _start_memory;

		return $_total_memory / (1024 ^ 2); ## in Mb, si es negativo entonces hay memoria a favor
	}

	public function setPuntoAnterior (Point $punto_anterior):Point
	{
		$this -> _punto_anterior = $punto_anterior;
		return $this;
	}

	public function getPuntoAnterior ():?Point
	{
		return $this -> _punto_anterior;
	}

	public function setPuntoSiguiente (Point $punto_siguiente):Point
	{
		$this -> _punto_siguiente = $punto_siguiente;
		return $this;
	}

	public function getPuntoSiguiente ():?Point
	{
		return $this -> _punto_siguiente;
	}

	public function setKey (string $key):Point
	{
		$this -> _key = $key;
		return $this;
	}

	public function getKey ():string
	{
		return $this -> _key;
	}

	public function indicateStartParams ($time, $memory):void
	{
		$this -> _start_time   = $time;
		$this -> _start_memory = $memory;
	}
}