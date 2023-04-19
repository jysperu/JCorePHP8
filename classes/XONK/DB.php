<?php
/**
 * APPPATH/classes/XONK/DB.php
 * @filesource
 */
namespace XONK;
defined('APPPATH') or exit(2); # Acceso directo no autorizado

/**
 * XONK\DB
 */
use SQLite3;
use Exception;

trait DB
{
	protected static $_con;

	public static function dbConnect ():bool
	{
		if (isset(static :: $_con))
			return true;

		if ( ! class_exists('SQLite3'))
			return false;

		try
		{
			$_dbfile = ROOTPATH . DS . '.xonk.v' . static :: db_version . '.db';
			$_dbfile_exists = file_exists($_dbfile);

			$_con = new SQLite3 ($_dbfile, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
			static :: $_con = $_con;

			if ( ! $_dbfile_exists)
			{
				$structure = static :: db_structure;
				foreach ($structure as $tblname => $fields)
				{
					$columns = [];
					foreach ($fields as $field)
					{
						$field = array_merge([
							'name'    => null,
							'type'    => null,
							'length'  => null,
							'notnull' => false,
							'pk'      => false,
						], $field);

						if (empty($field['name']))
							continue;

						if (empty($field['type']))
							continue;

						$columnname = '`' . $field['name'] . '`';
						$columntype = mb_strtoupper($field['type']);
						$columnlen  = empty($field['length']) ? '' : ('(' . $field['length'] . ')');
						$columnnull = $field['notnull'] ? 'NOT NULL' : 'NULL';
						$columnpk   = $field['pk'] ? 'PRIMARY KEY' : '';

						switch ($columntype)
						{
							case 'json':
								$columntype = 'BLOB';
								break;
							case 'char':
								$columntype = 'TEXT';
								break;
						}

						$columns[] = $columnname . ' ' . $columntype . ' ' . $columnlen . ' ' . $columnnull . ' ' . $columnpk;
					}

					if (count($columns) === 0)
						continue;

					$columns = implode(', ', $columns);
					$columns = '(' . $columns . ')';

					$query = 'CREATE TABLE `' . $tblname . '` ' . $columns;
					static :: $_con -> exec ($query);
				}
			}
		}
		catch(Exception $e)
		{
			return false;
		}

		return true;
	}

	public static function get (string $collection, array $filter)
	{
		
	}

	public static function set (string $collection, array $data)
	{
		
	}
}