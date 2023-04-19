<?php

if ( ! function_exists('sql_esc'))
{
	/**
	 * sql_esc()
	 * Ejecuta la función `mysqli_real_escape_string`
	 *
	 * @param string
	 * @param mysqli
	 * @return string
	 */
	function sql_esc ($valor = '', mysqli $conection = NULL)
	{
		is_a($conection, 'mysqli') or $conection = use_CON($conection);
		return mysqli_real_escape_string($conection, $valor);
	}
}

if ( ! function_exists('sql_qpesc'))
{
	/**
	 * sql_qpesc()
	 * Retorna el parametro correcto para una consulta de base datos
	 *
	 * @param string
	 * @param bool
	 * @param mysqli
	 * @return string
	 */
	function sql_qpesc ($valor = '', $or_null = FALSE, mysqli $conection = NULL, $f_as_f = FALSE)
	{
		static $_functions_alws = [
			'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURTIME', 'LOCALTIME', 'LOCALTIMESTAMP', 'NOW', 'SYSDATE'
		];
		static $_functions = [
			'ASCII', 'CHAR_LENGTH', 'CHARACTER_LENGTH', 'CONCAT', 'CONCAT_WS', 'FIELD', 'FIND_IN_SET', 'FORMAT', 'INSERT', 'INSTR', 'LCASE', 'LEFT', 'LENGTH', 'LOCATE', 'LOWER', 'LPAD', 'LTRIM', 'MID', 'POSITION', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'RPAD', 'RTRIM', 'SPACE', 'STRCMP', 'SUBSTR', 'SUBSTRING', 'SUBSTRING_INDEX', 'TRIM', 'UCASE', 'UPPER', 'ABS', 'ACOS', 'ASIN', 'ATAN', 'ATAN2', 'AVG', 'CEIL', 'CEILING', 'COS', 'COT', 'COUNT', 'DEGREES', 'DIV', 'EXP', 'FLOOR', 'GREATEST', 'LEAST', 'LN', 'LOG', 'LOG10', 'LOG2', 'MAX', 'MIN', 'MOD', 'PI', 'POW', 'POWER', 'RADIANS', 'RAND', 'ROUND', 'SIGN', 'SIN', 'SQRT', 'SUM', 'TAN', 'TRUNCATE', 'ADDDATE', 'ADDTIME', 'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURTIME', 'DATE', 'DATEDIFF', 'DATE_ADD', 'DATE_FORMAT', 'DATE_SUB', 'DAY', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK', 'DAYOFYEAR', 'EXTRACT', 'FROM_DAYS', 'HOUR', 'LAST_DAY', 'LOCALTIME', 'LOCALTIMESTAMP', 'MAKEDATE', 'MAKETIME', 'MICROSECOND', 'MINUTE', 'MONTH', 'MONTHNAME', 'NOW', 'PERIOD_ADD', 'PERIOD_DIFF', 'QUARTER', 'SECOND', 'SEC_TO_TIME', 'STR_TO_DATE', 'SUBDATE', 'SUBTIME', 'SYSDATE', 'TIME', 'TIME_FORMAT', 'TIME_TO_SEC', 'TIMEDIFF', 'TIMESTAMP', 'TO_DAYS', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'YEAR', 'YEARWEEK', 'BIN', 'BINARY', 'CASE', 'CAST', 'COALESCE', 'CONNECTION_ID', 'CONV', 'CONVERT', 'CURRENT_USER', 'DATABASE', 'IF', 'IFNULL', 'ISNULL', 'LAST_INSERT_ID', 'NULLIF', 'SESSION_USER', 'SYSTEM_USER', 'USER', 'VERSION'
		];

		if ($or_null !== FALSE and is_empty($valor))
		{
			$or_null = ($or_null === TRUE ? 'NULL' : $or_null);
			return $or_null;
		}

		$_regex_funcs_alws = '/^(' . implode('|', $_functions_alws) . ')(\(\))?$/i';
		$_regex_funcs = '/\b('.implode('|', $_functions).')\b/i';

		if (is_string($valor) and preg_match($_regex_funcs_alws, $valor))  ## Palabras Reservadas No Peligrosas
		{
			return $valor;
		}
		elseif (is_string($valor) and preg_match($_regex_funcs, $valor) and $f_as_f)  ## Palabras Reservadas
		{
			if (is_string($valor) and preg_match('/^\[MF\]\:/i', $valor))
			{
				$valor = preg_replace('/^\[MF\]\:/i', '', $valor);
			}
			else
			{
				return $valor;
			}
		}
		else
		{
			if (is_string($valor) and preg_match('/^\[MF\]\:/i', $valor))
			{
				$valor = preg_replace('/^\[MF\]\:/i', '', $valor);
			}
		}

		if (is_bool($valor))
		{
			return $valor ? 'TRUE' : 'FALSE';
		}

		if (is_numeric($valor) and ! preg_match('/^0/i', (string)$valor))
		{
			return sql_esc($valor, $conection);
		}

		is_array($valor) and $valor = json_encode($valor);
		if (is_object($valor))
		{
			if (method_exists($valor, '__toArray'))
			{
				$valor = json_encode($valor -> __toArray());
			}
			elseif (method_exists($valor, 'ToArray'))
			{
				$valor = json_encode($valor -> ToArray());
			}
			elseif (is_a($valor, 'Serializable'))
			{
				$valor = serialize($valor);
			}
			else
			{
				$valor = serialize($valor); // umm
			}
		}

		return '"' . sql_esc($valor, $conection) . '"';
	}
}

if ( ! function_exists('qp_esc'))
{
	function qp_esc ($valor = '', $or_null = FALSE, mysqli $conection = NULL, $f_as_f = FALSE)
	{
		return sql_qpesc($valor, $or_null, $conection, $f_as_f);
	}
}

if ( ! function_exists('sql'))
{
	/**
	 * sql()
	 * Ejecuta una consulta a la Base Datos
	 *
	 * @param string
	 * @param bool
	 * @param mysqli
	 * @return mixed
	 */
	function sql(string $query, $is_insert = FALSE, mysqli $conection = NULL, $modulo = null)
	{
		global $_MYSQL_history, $_MYSQL_errno, $_MYSQL_afctrow;

		$trace = debug_backtrace(false);
		while(count($trace) > 0 and (
			( ! isset($trace[0]['file']))    or 
			(   isset($trace[0]['file'])     and str_replace(JAPIPATH, '', $trace[0]['file']) <> $trace[0]['file']) or 
			(   isset($trace[0]['function']) and preg_match ('/^sql/i', $trace[0]['function']))
		))
		{
			array_shift($trace);
		}

		$trace = array_shift($trace);
		is_null($trace) or $trace = $trace['file'] . '#' . $trace['line'];

		is_a($is_insert, 'mysqli') and
		$conection = $is_insert and
		$is_insert = false;

		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		$_consulta_inicio = microtime(true);
		$result =  mysqli_query($conection, $query);
		$_consulta_fin = microtime(true);

		if ( ! $result)
		{
			$_MYSQL_errno = $_ERRNO = mysqli_errno($conection);
			$_ERROR = mysqli_error($conection);

			$_stat = [
				'query' => $query,
				'suphp' => 'mysqli_query($conection, $query)',
				'error' => $_ERROR, 
				'errno' => $_ERRNO,
				'hstpr' => 'error',
				'start' => $_consulta_inicio,
				'endin' => $_consulta_fin,
				'conct' => $conection->identify,
				'funct' => 'sql',
				'filen' => $trace,
				'modul' => $modulo,
			];
			$_MYSQL_history[] = $_stat;
			APP() -> action_apply('SQL/Stat', $_stat, $conection);

			trigger_error('Error en el query: ' . PHP_EOL . $query . PHP_EOL . $_ERRNO . ': ' . $_ERROR, E_USER_WARNING);
			return FALSE;
		}

		$return = true;

		$is_insert and
		$return = mysqli_insert_id($conection);
		$_MYSQL_afctrow = mysqli_affected_rows($conection);

		$_stat = [
			'query' => $query,
			'suphp' => 'mysqli_query($conection, $query)',
			'error' => '', 
			'errno' => '',
			'hstpr' => 'success',
			'start' => $_consulta_inicio,
			'endin' => $_consulta_fin,
			'conct' => $conection->identify,
			'funct' => 'sql',
			'afrow' => $_MYSQL_afctrow,
			($is_insert ? 'insert_id' : 'return') => $return,
			'filen' => $trace,
			'modul' => $modulo,
		];
		$_MYSQL_history[] = $_stat;

		APP() -> action_apply('SQL/Stat', $_stat, $conection);
		return $return;
	}
}

if ( ! function_exists('sql_data'))
{
	/**
	 * sql_data()
	 * Ejecuta una consulta a la Base Datos
	 *
	 * @param string
	 * @param bool
	 * @param string|array|null
	 * @param mysqli
	 * @return mixed
	 */

	function sql_data(string $query, $return_first = FALSE, $fields = NULL, mysqli $conection = NULL, $modulo = null, $just_get_result = false)
	{
		global $_MYSQL_history, $_MYSQL_errno;
		static $_executeds = [];

		$trace = debug_backtrace(false);
		while(count($trace) > 0 and (
			( ! isset($trace[0]['file']))    or 
			(   isset($trace[0]['file'])     and str_replace(JAPIPATH, '', $trace[0]['file']) <> $trace[0]['file']) or 
			(   isset($trace[0]['function']) and preg_match ('/^sql/i', $trace[0]['function']))
		))
		{
			array_shift($trace);
		}

		$trace = array_shift($trace);
		is_null($trace) or $trace = $trace['file'] . '#' . $trace['line'];

		is_a($return_first, 'mysqli') and
		$conection = $return_first and
		$return_first = false;

		is_a($fields, 'mysqli') and
		$conection = $fields and
		$fields = null;

		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		isset($_executeds[$conection->identify]) or $_executeds[$conection->identify] = 0;
		$_executeds[$conection->identify]++;

		$_executeds[$conection->identify] > 1 and
		@mysqli_next_result($conection);

		$_consulta_inicio = microtime(true);
		$result =  mysqli_query($conection, $query);
		$_consulta_fin = microtime(true);

		if ( ! $result)
		{
			$_MYSQL_errno = $_ERRNO = mysqli_errno($conection);
			$_ERROR = mysqli_error($conection);

			$_stat = [
				'query' => $query,
				'suphp' => 'mysqli_query($conection, $query)',
				'error' => $_ERROR, 
				'errno' => $_ERRNO,
				'hstpr' => 'error',
				'start' => $_consulta_inicio,
				'endin' => $_consulta_fin,
				'conct' => $conection->identify,
				'funct' => 'sql_data',
				'filen' => $trace,
				'modul' => $modulo,
			];
			$_MYSQL_history[] = $_stat;
			APP() -> action_apply('SQL/Stat', $_stat, $conection);
			trigger_error('Error en el query: ' . PHP_EOL . $query . PHP_EOL . $_ERRNO . ': ' . $_ERROR, E_USER_WARNING);

			if ($just_get_result) return $result;

			$sql_data_result = MysqlResultData::fromArray([])
			-> quitar_fields('log');
		}
		else
		{
			$_stat = [
				'query' => $query,
				'suphp' => 'mysqli_query($conection, $query)',
				'error' => '', 
				'errno' => '',
				'hstpr' => 'success',
				'start' => $_consulta_inicio,
				'endin' => $_consulta_fin,
				'conct' => $conection->identify,
				'funct' => 'sql_data',
				'total' => $result->num_rows,
				'filen' => $trace,
				'modul' => $modulo,
			];
			$_MYSQL_history[] = $_stat;
			APP() -> action_apply('SQL/Stat', $_stat, $conection);

			if ($just_get_result) return $result;

			$sql_data_result = new MysqlResultData ($result);
		}

		if ( ! is_null($fields))
		{
			$sql_data_result
			-> filter_fields($fields);
		}

		if ($return_first)
		{
			return $sql_data_result
			-> first();
		}

		return $sql_data_result;
	}
}

if ( ! function_exists('sql_pswd'))
{
	/**
	 * sql_pswd()
	 * Obtiene el password de un texto
	 *
	 * @param string
	 * @param mysqli
	 * @return bool
	 */
	function sql_pswd ($valor, mysqli $conection = NULL)
	{
		if (function_exists('encrypt'))
		{
			return encrypt ($valor, sql_pswd_blowfish);
		}

		return sql_data('
		SELECT PASSWORD(' . sql_qpesc($valor, FALSE, $conection) . ') as `valor`;
		', TRUE, 'valor', $conection);
	}
}

if ( ! function_exists('sql_trans'))
{
	/**
	 * sql_trans()
	 * Procesa transacciones de Base Datos
	 * 
	 * WARNING: Si se abre pero no se cierra no se guarda pero igual incrementa AUTOINCREMENT
	 * WARNING: Se deben cerrar exitosamente la misma cantidad de los que se abren
	 * WARNING: El primero que cierra con error cierra todos los transactions activos 
	 *          (serìa innecesario cerrar exitosamente las demas)
	 *
	 * @param bool|null
	 * @param mysqli
	 * @return bool
	 */
	function sql_trans($do = NULL, mysqli $conection = NULL)
	{
		static $_trans = []; ## levels de transacciones abiertas
		static $_auto_commit_setted = [];

		is_a($do, 'mysqli') and
		$conection = $do and
		$do = null;

		is_a($conection, 'mysqli') or $conection = use_CON($conection);

		isset($_trans[$conection->identify]) or $_trans[$conection->identify] = 0;

		if ($do === 'NUMTRANS')
		{
			return $_trans[$conection->identify];
		}

		isset($_auto_commit_setted[$conection->identify]) or $_auto_commit_setted[$conection->identify] = FALSE;

		if (is_null($do))
		{
			## Se está iniciando una transacción

			## Solo si el level es 0 (aún no se ha abierto una transacción), se ejecuta el sql
			$_trans[$conection->identify] === 0 and mysqli_begin_transaction($conection);
			$_trans[$conection->identify]++; ## Incrmentar el level

			if ( ! $_auto_commit_setted[$conection->identify])
			{
				mysqli_autocommit($conection, false) AND $_auto_commit_setted[$conection->identify] = TRUE;
			}

			return TRUE;
		}

		if ($_trans[$conection->identify] === 0)
		{
			return FALSE; ## No se ha abierto una transacción
		}

		if ( ! is_bool($do))
		{
			trigger_error('Se está enviando un parametro ' . gettype($do) . ' en vez de un BOOLEAN', E_USER_WARNING);
			$do = (bool)$do;
		}

		if ($do)
		{
			$_trans[$conection->identify]--; ## Reducir el level

			## Solo si el level es 0 (ya se han cerrado todas las conecciones), se ejecuta el sql
			if ($_trans[$conection->identify] === 0)
			{
				mysqli_commit($conection);

				if ($_auto_commit_setted[$conection->identify])
				{
					mysqli_autocommit($conection, true) AND $_auto_commit_setted[$conection->identify] = FALSE;
				}
			}
		}
		else
		{
			$_trans[$conection->identify] = 0; ## Finalizar todas los levels abiertos

			mysqli_rollback($conection);

			if ($_auto_commit_setted[$conection->identify])
			{
				mysqli_autocommit($conection, true) AND $_auto_commit_setted[$conection->identify] = FALSE;
			}
		}

		return TRUE;
	}
}
