<?php
/**
 * APPPATH/classes/JArray.php
 * @filesource
 */

defined('APPPATH') or exit(0); // Acceso directo no autorizado

class JArray extends ArrayObject
{
	//////////////////////////////////////
	/// Constructor del objeto         ///
	//////////////////////////////////////
	public function __construct($data = [], $callbacks = [])
	{
		parent::__construct ($data);
		$this -> _callbacks = $callbacks;
	}
}