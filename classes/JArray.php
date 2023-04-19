<?php
/**
 * APPPATH/classes/JArray.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Acceso directo no autorizado

class JArray extends ArrayObject
{
	use Arrayable;
	use Callbackable;

	//////////////////////////////////////
	/// Constructor del objeto         ///
	//////////////////////////////////////
	public function __construct($data = [], $callbacks = [])
	{
		parent::__construct ($data);
		$this -> _callbacks = $callbacks;
	}

	/**
	 * defaultContext
	 */
	protected $_default_context = 'edit';
	public function defaultContext (? string $context = null)
	{
		if ( ! is_null($context))
		{
			$this -> _default_context = $context;
			return $this;
		}

		return $this -> _default_context;
	}

	/**
	 * offsetExists ()
	 */
	public function offsetGet (mixed $index):mixed
	{
		$context = $this -> defaultContext();

		if ($method = '_before_get_' . $index and method_exists($this, $method))
			$this -> $method ($index, $context);

		$this -> execCallbacks('before_get', $index, $context);
		$this -> execCallbacks('before_get_' . $index, $context);
		$this -> execCallbacks('before_get_' . $index . '_' . $context);

		$return = parent::offsetGet($index);

		$return = $this -> execCallbacks('get', $return, $index, $context);
		$return = $this -> execCallbacks('get_' . $index, $return, $context);
		$return = $this -> execCallbacks('get_' . $index . '_' . $context, $return);
		$return = $this -> execCallbacks(__FUNCTION__, $return, $index, $context);

		if ($method = '_after_get_' . $index and method_exists($this, $method))
			$return = $this -> $method ($return, $index, $context);

		return $return;
	}
}