<?php
/**
 * traits/Arrayable.php
 * @filesource
 */

defined('APPPATH') or exit(0); ## Acceso directo no autorizado

/**
 * Arrayable
 * La clase asociada puede simular ser una variable array y a la vez poder ejecutar funciones de clase
 */
trait Arrayable
{
	use Callbackable;

	/**
	 * first ()
	 * Permite retornar la primera variable encontrada dentor de los datos
	 *
	 * > Retorna NULL en caso de no encontrar algún elemento
	 */
	public function first (): mixed
	{
		if (isset ($this[0]))
			return $this[0];

		$return = NULL;
		foreach($this as $v)
		{
			$return = $v;
			break;
		}

		return $return;
	}

	/**
	 * clear ()
	 * Permite limpiar todos los elementos que contiene la clase
	 *
	 * @return Arrayable
	 */
	public function clear (): static
	{
		$keys = array_keys ((array) $this);

		while (count($keys) > 0)
		{
			$key = array_shift   ($keys);
			$this -> offsetUnset ($key );
		}

		return $this;
	}

	/**
	 * _detect_index ()
	 *
	 * Función que permite detectar un código 
	 */
	protected function _detect_index (string $index):string
	{
		if ($this -> __isset($index))
			return $index;

		$indices = [ $index ];
		$temp    = $index . 's'  and $indices[] = $temp;
		$temp    = $index . 'es' and $indices[] = $temp;
		$temp    = preg_replace('/s$/i',  '', $index) and $indices[] = $temp;
		$temp    = preg_replace('/es$/i', '', $index) and $indices[] = $temp;
		$indices = array_unique($indices);

		foreach ($indices as $indice)
			if ($this -> __isset($indice))
				return $index;

		return $index;
	}

	/**
	 * __call ()
	 */
	public function __call (string $name, array $args):mixed
	{
		if (preg_match('#^set_(.+)#', $name))
		{
			$index = preg_replace('#^set_#', '', $name);
			$index = $this -> _detect_index($index);
			$valor = array_shift ($args);
			return $this -> __set($index, $valor);
		}

		if (preg_match('#^get_(.+)#', $name))
		{
			$index = preg_replace('#^get_#', '', $name);
			$index = $this -> _detect_index($index);
			return $this -> __get($index);
		}

		if (preg_match('#^(add|agregar|push)_(.+)#', $name))
		{
			$index = preg_replace('#^(add|agregar|push)_#', '', $name);
			$index = $this -> _detect_index($index);

			$val = array_shift ($args);
			$key = array_shift ($args);

			$actual = $this -> __isset($index) ? $this -> __get($index) : [];
			$actual = (array)$actual;

			if (is_null($key))
				$actual[] = $val;
			else
				$actual[$key] = $val;

			return $this -> __set($index, $actual);
		}

		if (preg_match('#^(array_shift|shift)_(.+)#', $name))
		{
			$index = preg_replace('#^(array_shift|shift)_#', '', $name);
			$index = $this -> _detect_index($index);

			$actual = $this -> __isset($index) ? $this -> __get($index) : [];
			$actual = (array) $actual;
			$return = array_shift($actual);
			$this -> __set($index, $actual);

			return $return;
		}

		if (preg_match('#^(array_pop|pop)_(.+)#', $name))
		{
			$index = preg_replace('#^(array_pop|pop)_#', '', $name);
			$index = $this -> _detect_index($index);

			$actual = $this -> __isset($index) ? $this -> __get($index) : [];
			$actual = (array) $actual;
			$return = array_pop($actual);
			$this -> __set($index, $actual);

			return $return;
		}

		if (preg_match('#^(array_diff|diff)_(.+)#', $name))
		{
			$index = preg_replace('#^(array_diff|diff)_#', '', $name);
			$index = $this -> _detect_index($index);

			$actual = $this -> __isset($index) ? $this -> __get($index) : [];
			$actual = (array) $actual;
			$args   = (array) $args;
			array_unshift($args, $actual);

			return call_user_func_array('(array_diff|diff)', $args);
		}

		logger ('Función requerida no existe `' . get_called_class() . '::' . $name . '()`', E_USER_WARNING);
		return $this;
	}

	/**
	 * __invoke ()
	 */
	public function __invoke():mixed
	{
		$_args = func_get_args();
		$index = array_shift($_args);
		$val   = array_shift($_args);

			if (is_null($index)) return $this -> __toArray();
		elseif (is_null($val))   return $this -> __get($index);
		else                     return $this -> __set($index, $val);
	}

	/**
	 * __toString ()
	 */
	public function __toString():string
	{
		return json_encode($this);
	}

	/**
	 * __debugInfo()
	 */
	public function __debugInfo():array
	{
        return $this -> __toArray();
    }

	################################################
	## Magic Functions / Array Access             ##
	################################################

	/**
	 * offsetExists ()
	 */
	public function offsetExists (mixed $index):bool
	{
		$this -> execCallbacks('before_exists', $index);

		$return = parent :: offsetExists($index);

		$return = $this -> execCallbacks('exists', $return, $index);
		$return = $this -> execCallbacks(__FUNCTION__, $return, $index);

		return $return;
	}

	/**
	 * offsetExists ()
	 */
	public function offsetGet (mixed $index):mixed
	{
		if ($method = '_before_get_' . $index and method_exists($this, $method))
			$this -> $method ($index);

		if ($method = '_before_get' and method_exists($this, $method))
			$this -> $method ($index);

		$this -> execCallbacks('before_get', $index);
		$this -> execCallbacks('before_get_' . $index);

		$return = parent :: offsetGet($index);

		$return = $this -> execCallbacks('get', $return, $index);
		$return = $this -> execCallbacks('get_' . $index, $return);
		$return = $this -> execCallbacks(__FUNCTION__, $return, $index);

		if ($method = '_after_get_' . $index and method_exists($this, $method))
			$return = $this -> $method ($return, $index);

		if ($method = '_after_get' and method_exists($this, $method))
			$return = $this -> $method ($return, $index);

		return $return;
	}

	/**
	 * offsetSet ()
	 */
	public function offsetSet (mixed $index, mixed $newval):void
	{
		if ($method = '_before_set_' . $index and method_exists($this, $method))
			$newval = $this -> $method ($newval, $index);

		if ($method = '_before_set' and method_exists($this, $method))
			$newval = $this -> $method ($newval, $index);

		$newval = $this -> execCallbacks('before_set', $newval, $index);
		$newval = $this -> execCallbacks('before_set_' . $index, $newval);

		parent :: offsetSet($index, $newval);

		$this -> execCallbacks('set', $newval, $index);
		$this -> execCallbacks('set_' . $index, $newval);
		$this -> execCallbacks(__FUNCTION__, $newval, $index);

		if ($method = '_after_set_' . $index and method_exists($this, $method))
			$this -> $method ($newval, $index);

		if ($method = '_after_set' and method_exists($this, $method))
			$this -> $method ($newval, $index);

		return;
	}

	/**
	 * offsetUnset ()
	 */
	public function offsetUnset (mixed $index):void
	{
		$this -> execCallbacks('before_unset', $index);
		$this -> execCallbacks('before_unset_' . $index);

		parent :: offsetUnset($index);

		$this -> execCallbacks('unset', $index);
		$this -> execCallbacks('unset_' . $index);
		$this -> execCallbacks(__FUNCTION__, $index);

		return;
	}

	################################################
	## Magic Functions / Object Access            ##
	################################################

	/**
	 * __isset ()
	 */
	public function __isset (string $index):bool
	{
		$return = $this -> offsetExists($index);
		$this -> execCallbacks(__FUNCTION__, $return, $index);
		return $return;
	}

	/**
	 * __get ()
	 */
	public function __get (string $index):mixed
	{
		$return = $this -> offsetGet($index);
		$this -> execCallbacks(__FUNCTION__, $return, $index);
		return $return;
	}

	/**
	 * __set ()
	 */
	public function __set (string $index, mixed $newval):void
	{
		$this -> offsetSet($index, $newval);
		$this -> execCallbacks(__FUNCTION__, $newval, $index);
		return;
	}

	/**
	 * __unset ()
	 */
	public function __unset (string $index):void
	{
		$this -> offsetUnset($index);
		$this -> execCallbacks(__FUNCTION__, $index);
		return;
	}

	/**
	 * __toArray ()
	 */
	public function __toArray():array
	{
		$this -> execCallbacks('before_toarray');

		$return = (array) $this;

		$return = $this -> execCallbacks('toarray', $return);
		$return = $this -> execCallbacks(__FUNCTION__, $return);

		return $return;
	}
}
