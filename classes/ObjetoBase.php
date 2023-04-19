<?php

use Helper\CasterVal;

abstract class ObjetoBase extends JArray
{
	//=====================================================================//
	//=== Variables de la configuración del objeto                      ===//
	//=====================================================================//

	/**
	 * $CON
	 * Indica la conección que se usará en este objeto
	 *
	 * Puede ser:
	 * 1.- Un string la cual indica la configuración que tomará del $config
	 * 2.- Un string la cual es la ruta de conección PDO
	 * 3.- Un objeto PDO
	 * 4.- Un objeto mysqli
	 */
	public static $CON = null;

	/**
	 * $TBLNAME
	 * Indica el nombre de la tabla
	 */
	public static $TBLNAME = null;

	/**
	 * $FIELDs
	 * Indica el listado de campos que contiene el objeto
	 *
	 * Atributos por cada campo
	 * - name     => {sting} 												— Requerido
	 * - type     => {string (int|number|float|char|text|binary|datetime)}	— Por defecto "text"
	 * - length   => {entero mayor a cero / string (long|medium)} 			— Requerido por algunos tipos
	 * - decimals => {entero mayor a cero} 									— Requerido por algunos tipos
	 * - notnull  => {boolean} 												— Por defecto "FALSE"
	 * - unsigned => {boolean}												— Por defecto "FALSE", solo usado en tipos numéricos
	 * - default  => {mixed}												— No Requerido
	 *
	 * Uso de tabla según XMLDB columns types de Moodle
	 * https://docs.moodle.org/dev/XMLDB_column_types
	 *
	 * | type     | len    | MySql      | PostgreSQL | Oracle   | MSSQL    |
	 * |----------|--------|------------|------------|----------|----------|
	 * | int      | > 9    | BIGINT     | BIGINT     | NUMBER   | BIGINT   |
	 * | int      | > 6    | INT        | INTEGER    | NUMBER   | INTEGER  |
	 * | int      | > 4    | MEDIUMINT  | INTEGER    | NUMBER   | INTEGER  |
	 * | int      | > 2    | SMALLINT   | SMALLINT   | NUMBER   | SMALLINT |
	 * | int      | > 0    | TINYINT    | SMALLINT   | NUMBER   | SMALLINT |
	 * | number   | NULL   | NUMERIC    | NUMERIC    | NUMBER   | DECIMAL  |
	 * | float    | > 6dec | DOUBLE     | DOUBLE ... | NUMBER   | FLOAT    |
	 * | float    | > 0dec | FLOAT      | REAL       | NUMBER   | REAL     |
	 * | char     | > 0    | VARCHAR    | VARCHAR    | VARCHAR2 | NVARCHAR |
	 * | text     | long   | LONGTEXT   | TEXT       | CLOB     | NTEXT    |
	 * | text     | medium | MEDIUMTEXT | TEXT       | CLOB     | NTEXT    |
	 * | text     | NULL   | TEXT       | TEXT       | CLOB     | NTEXT    |
	 * | binary   | long   | LONGBLOB   | BYTEA      | BLOB     | IMAGE    |
	 * | binary   | medium | MEDIUMBLOB | BYTEA      | BLOB     | IMAGE    |
	 * | binary   | NULL   | BLOB       | BYTEA      | BLOB     | IMAGE    |
	 * | datetime | NULL   | DATETIME   | TIMESTAMP  | DATE     | DATETIME |
	 *
	 * > Si el nombre del campo es equivalente a el de un FK
	 */
	public static $FIELDs = [];

	/**
	 * $PKs
	 * Indica el listado de campos que funcionan como PRIMARY KEY
	 *
	 * > Listado de strings
	 */
	public static $PKs = [];

	/**
	 * $PK_ID
	 * Indica el único campo que es PRIMARY KEY AUTO INCREMENT
	 *
	 * > En XMLDB se le llama "sequence" tipo boolean y se asigna como dato dentro del campo
	 *
	 * Si este dato no es NULO entonces $PKs no será consderado pero igual debe contener este campo
	 */
	public static $PK_id = null;

	/**
	 * $UKs
	 * Indica el listado de CONSTRAINTS que funcionan como UNIQUE KEY
	 *
	 * Atributos por cada campo
	 * - name   => {string}
	 * - fields => {array de string}
	 */
	public static $UKs = [];

	/**
	 * $FKs_childs
	 * Indica el listado de CONSTRAINTS que funcionan como FOREIGN KEY
	 *
	 * Atributos por cada campo
	 * - name       => {string}
	 * - field      => {string} — Nombre del campo que contendrá el listado de datos
	 * - ref_table  => {string}
	 * - ref_class  => {string}
	 * - ref_fields => {array[string] => string} — Key equivale al campo de la tabla referenciada / Val es el campo de este objeto
	 * - on_update  => {string} — Por defecto CASCADE
	 * - on_delete  => {string} — Por defecto CASCADE
	 *
	 * > Los objetos referenciados aquí dependen de este objeto en al menos un campo (ej. usuario_sesion tiene el campo usuario_id que depende del campo id de este objeto usuario)
	 */
	public static $FKs_childs = [];

	/**
	 * $FKs_parents
	 * Indica el listado de CONSTRAINTS que funcionan como FOREIGN KEY
	 *
	 * Atributos por cada campo
	 * - name       => {string}
	 * - field      => {string} — Nombre del campo que contendrá el objeto padre
	 * - ref_table  => {string}
	 * - ref_class  => {string}
	 * - ref_fields => {array[string] => string} — Key equivale al campo de la tabla referenciada / Val es el campo de este objeto
	 *
	 * > El objeto depende de los referenciados aquí
	 */
	public static $FKs_parents = [];

	/**
	 * $INDEXes
	 * Indica el listado de CONSTRAINTS que funcionan como INDEXes
	 */
	public static $INDEXes = [];

	/**
	 * $FIELD_string
	 * Indica el campo que será utilizado para convertir el objeto en texto
	 *
	 * > Si el valor es un callable entonces será ejecutado previo a la solicitud
	 */
	public static $FIELD_string = null;


	//=====================================================================//
	//=== Funciones generalizadas del objeto                            ===//
	//=====================================================================//

	public static function gcc ()
	{
		return get_called_class();
	}

	public static function fromArray (array $data):ObjetoBase
	{
		$instance = new static;

		foreach ($data as $k => $v)
		{
			$instance -> _data_original[$k] = $v;
			$instance -> _data_instance[$k] = $v;
		}

		$instance -> _found      = true;
		$instance -> _from_array = true;

		return $instance;
	}

	public static function getLista (array $filter = [], array $sortby = [], int $limit = 0, int $page = 0)
	{
		
	}

	public static function Lista (array $filter = [], array $sortby = [], int $limit = 0, int $page = 0)
	{
		return static :: getLista ($filter, $sortby, $limit, $page);
	}

	public static function getRecord (array $filter = [], array $sortby = [])
	{
		$lista = static :: getLista ($filter, $sortby, 1, 1);
		$first = array_shift($lista);
		is_null($first) and $first = new static;
		return $first;
	}

	public static function cachedFields ()
	{
		static $_fields;

		if ( ! isset($_fields))
		{
			$_fields = [];
			$FIELDs = static :: $FIELDs;
			foreach($FIELDs as $field)
			{
				if (is_string($field))
				{
					$field = [
						'name' => $field,
					];
				}

				if ( ! is_array($field))
					$field = (array) $field; ## Need be arrayable

				if ( ! isset($field['name']))
					continue;

				$field['name'] = (string) $field['name'];

				if (empty($field['name']))
					continue;

				$field['type'] = (string) (isset($field['type']) ? $field['type'] : 'text');

				$_fields[$field['name']] = $field;
			}
		}

		return $_fields;
	}

	public static function cachedKeys ()
	{
		static $_keys;

		if ( ! isset($_keys))
		{
			$PKs = static :: $PKs;
			foreach ($PKs as $key)
			{
				$key = (string) $key;

				if (is_empty($key))
					continue;

				$_keys[] = $key;
			}
		}

		return $_keys;
	}

	public static function generateDefaultData ()
	{
		static $cached_data;

		if ( ! isset($cached_data))
		{
			$cached_data = [];

			//=== Añadiendo los campos
			$fields = static :: cachedFields();
			foreach ($fields as $field)
			{
				$name    = $field['name'];
				$default = $field['default'];

				$cached_data[$name] = $default;
			}
		}

		return $cached_data;
	}


	//=====================================================================//
	//=== Constructor del objeto                                        ===//
	//=====================================================================//

	public function __construct (...$pks_data)
	{
		//=== Establecer conección de la base datos
		// $this -> _CON = use_con(static :: $CON);

		//=== Configurando el objeto base
		$this -> _data_original = new JArray (static :: generateDefaultData ());
		$this -> _data_instance = new JArray (static :: generateDefaultData ());

		parent :: __construct ($this -> _data_instance);
		$this -> execCallbacks ('construct');

		//=== Estableciendo los datos PKs
		$keys = static :: cachedKeys();
		while(count($pks_data) > 0 and count($keys) > 0)
		{
			$keyval = array_shift($pks_data);
			$key    = array_shift($keys);

			$this -> _data_original[$key] = $this -> _data_instance[$key] = $keyval;
		}

		//=== Realizando la busqueda
		$this -> select();
	}


	//=====================================================================//
	//=== Variables de la instancia del objeto                          ===//
	//=====================================================================//

	/**
	 * $_found
	 * El objeto ha sido encontrado en la base datos
	 */
	protected $_found = false;

	/**
	 * $_from_array
	 * El objeto proviene de un objeto array
	 */
	protected $_from_array = false;

	/**
	 * $_data_original
	 * La información original proveniente de la base datos
	 */
	protected $_data_original = [];

	/**
	 * $_data_instance
	 * La información establecida en la instancia
	 *
	 * > La información es probable que se actualice
	 */
	protected $_data_instance = [];

	/**
	 * $_manual_setted
	 * Listado de campos que el usuario cambio de manera manual
	 *
	 * - Después de cada SELECT se limpia la lista
	 *
	 * key & val son iguales
	 */
	protected $_manual_setted = [];

	/**
	 * $_errores
	 * Listado de errores producidos
	 */
	protected $_errores = [];


	//=====================================================================//
	//=== Funciones privadas de la instancia del objeto                 ===//
	//=====================================================================//

	protected function _addError (string $error):ObjetoBase
	{
		$this -> _errores[] = $error;
		return $this;
	}

	protected function _logger (string $error, int $level = 1, string $function = null, mixed $severity = E_USER_WARNING, string $fileline = __FILE__ . '#' . __LINE__):ObjetoBase
	{
		$gcc = static :: gcc();
		$level > 1 and $gcc .= '#' . $level;
		empty($function) or $gcc .= '*' . $function;

		$msg = $error . ' [' . $gcc . ']';
		(new MetaException\Objeto ($msg, $this))
			-> logger();

		return $this;
	}

	protected function _verify (string $accion_realizada, int $level = 1, bool $logger = false)
	{
		
	}

	protected function _auto_calculate ()
	{
		
	}

	protected function _castear_valor (mixed $valor, string $index)
	{
		
		return $valor;
	}


	//=====================================================================//
	//=== Funciones públicas de la instancia del objeto                 ===//
	//=====================================================================//

	public function OID ():string
	{
		$data = $this -> _data_original;

		//=== En caso el PRIMARY KEY AUTOINCREMENT existe
		$field = static :: $PK_id;

		if ( ! empty($field) and isset($data[$field]))
			return (string) $data[$field];

		//=== Obener el MD5
		$keys = static :: cachedKeys();

		$md5 = [ static :: gcc() ];

		foreach ($keys as $key)
			$md5[] = (isset($data[$key]) ? $data[$key] : null);

		$md5 = json_encode($md5);
		$md5 = md5($md5);

		return (string) $md5;
	}

	public function getErrores ():array
	{
		return $this -> _errores;
	}

	public function get_last_error ()
	{
		$errores = $this -> _errores;
		return array_pop($errores);
	}

	public function found ():bool
	{
		return $this -> _found;
	}

	public function setFound (bool $newval):ObjetoBase
	{
		$this -> _found = $newval;
		return $this;
	}

	public function isFromArray ():bool
	{
		return $this -> _from_array;
	}

	public function isValid ()
	{
		return $this -> _verify ('validar', 1, false);
	}

	public function reset ():ObjetoBase
	{
		$this -> _found  = false;
		$this -> select();
		return $this;
	}

	public function getData (array $fields = null, string $context = 'edit')
	{
		$return = [];
		$this -> defaultContext ($context);
		$fields = $this -> execCallbacks ('getData/fields', $fields, $context);

		is_empty($fields) and $fields = array_keys((array) $this -> _data_instance);
		$fields = (array) $fields;

		foreach ($fields as $field)
		{
			$func = [$this, 'get_' . $field];
			$return[$field] = is_callable($func) ? call_user_func($func) : null;
		}

		$return = $this -> execCallbacks ('getData', $return, $context);
		$return = $this -> execCallbacks ('getData/' . $context, $return);

		return $return;
	}


	//=====================================================================//
	//=== Funciones manipuladores de la base datos                      ===//
	//=====================================================================//

	public function insertForced ()
	{
		$this -> _found = false;

		//=== Quitando el KEY
		$manset = array_values($this -> _manual_setted);
		$field  = static :: $PK_id;

		if (in_array($field, $manset))
			unset($this -> _manual_setted[$field]);

		return $this -> insert ();
	}

	public function insert_update ()
	{
		if ($this -> _found)
			return $this -> update();
		return $this -> insert();
	}

	public function select ()
	{
		
	}

	public function insert ()
	{
		
	}

	public function update ()
	{
		
	}

	public function delete (array $omitir_validacion = [])
	{
		
	}


	//=====================================================================//
	//=== Callbackable                                                  ===//
	//=====================================================================//

	protected function _after_set ($newval, $index)
	{
		$this -> _auto_calculate();
	}

	protected function _before_set ($newval, $index)
	{
		$newval = $this -> _castear_valor ($newval, $index);
		return $newval;
	}
}