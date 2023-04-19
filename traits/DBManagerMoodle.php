<?php

define('MUST_EXIST',      2);
define('IGNORE_MISSING',  4);
define('IGNORE_MULTIPLE', 8);

/**
 * DBManagerMoodle
 * https://docs.moodle.org/dev/Data_manipulation_API
 */
trait DBManagerMoodle
{
	//*************************************************************
	// Getting a single record
	//*************************************************************

	/**
	 * get_record
	 * Return a single database record as an object where all the given conditions are met.
	 */
	public function get_record ($table, array $conditions, $fields='*', $strictness=IGNORE_MISSING);

	/**
	 * get_record_select
	 * Return a single database record as an object where the given conditions are used in the WHERE clause.
	 */
	public function get_record_select ($table, $select, array $params=null, $fields='*', $strictness=IGNORE_MISSING);

	/**
	 * get_record_sql
	 * Return a single database record as an object using a custom SELECT query.
	 */
	public function get_record_sql ($sql, array $params=null, $strictness=IGNORE_MISSING);

	//*************************************************************
	// Getting a hashed array of records
	//*************************************************************

	/**
	 * get_records
	 * Return a list of records as an array of objects where all the given conditions are met.
	 */
	public function get_records ($table, array $conditions=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0);

	/**
	 * get_records_select
	 * Return a list of records as an array of objects where the given conditions are used in the WHERE clause.
	 */
	public function get_records_select ($table, $select, array $params=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0);

	/**
	 * get_records_sql
	 * Return a list of records as an array of objects using a custom SELECT query.
	 */
	public function get_records_sql ($sql, array $params=null, $limitfrom=0, $limitnum=0);

	/**
	 * get_records_list
	 * Return a list of records as an array of objects where the given field matches one of the possible values.
	 */
	public function get_records_list ($table, $field, array $values, $sort='', $fields='*', $limitfrom='', $limitnum='');

	//*************************************************************
	// Getting data as key/value pairs in an associative array
	//*************************************************************

	/**
	 * get_records_menu
	 * Return the first two columns from a list of records as an associative array where all the given conditions are met.
	 */
	public function get_records_menu ($table, array $conditions=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0);

	/**
	 * get_records_select_menu
	 * Return the first two columns from a list of records as an associative array where the given conditions are used in the WHERE clause.
	 */
	public function get_records_select_menu ($table, $select, array $params=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0);

	/**
	 * get_records_sql_menu
	 * Return the first two columns from a number of records as an associative array using a custom SELECT query.
	 */
	public function get_records_sql_menu ($sql, array $params=null, $limitfrom=0, $limitnum=0);

	//*************************************************************
	// Counting records that match the given criteria
	//*************************************************************

	/**
	 * count_records
	 * Count the records in a table where all the given conditions are met.
	 */
	public function count_records ($table, array $conditions=null);

	/**
	 * count_records_select
	 * Count the records in a table where the given conditions are used in the WHERE clause.
	 */
	public function count_records_select ($table, $select, array $params=null, $countitem="COUNT('x')");

	/**
	 * count_records_sql
	 * Counting the records using a custom SELECT COUNT(...) query.
	 */
	public function count_records_sql ($sql, array $params=null);

	//*************************************************************
	// Checking if a given record exists
	//*************************************************************

	/**
	 * record_exists
	 * Test whether a record exists in a table where all the given conditions are met.
	 */
	public function record_exists ($table, array $conditions=null);

	/**
	 * record_exists_select
	 * Test whether any records exists in a table where the given conditions are used in the WHERE clause.
	 */
	public function record_exists_select ($table, $select, array $params=null);

	/**
	 * record_exists_sql
	 * Test whether the given SELECT query would return any record.
	 */
	public function record_exists_sql ($sql, array $params=null);

	//*************************************************************
	// Getting a particular field value from one record
	//*************************************************************

	/**
	 * get_field
	 * 
	 */
	public function get_field ($table, $return, array $conditions, $strictness=IGNORE_MISSING);

	/**
	 * xxxxxxxxx
	 * Get a single field value from a table record where all the given conditions are met.
	 */
	public function xxxxxxxxx ();

	/**
	 * get_field_select
	 * Get a single field value from a table record where the given conditions are used in the WHERE clause.
	 */
	public function get_field_select ($table, $return, $select, array $params=null, $strictness=IGNORE_MISSING);

	/**
	 * get_field_sql
	 * Get a single field value (first field) using a custom SELECT query.
	 */
	public function get_field_sql ($sql, array $params=null, $strictness=IGNORE_MISSING);

	//*************************************************************
	// Getting field values from multiple records
	//*************************************************************

	/**
	 * get_fieldset_select
	 * Return values of the given field as an array where the given conditions are used in the WHERE clause.
	 */
	public function get_fieldset_select ($table, $return, $select, array $params=null);

	/**
	 * get_fieldset_sql
	 * Return values of the first column as an array using a custom SELECT field FROM ... query.
	 */
	public function get_fieldset_sql ($sql, array $params=null);

	//*************************************************************
	// Setting a field value
	//*************************************************************

	/**
	 * set_field
	 * Set a single field in every record where all the given conditions are met.
	 */
	public function set_field ($table, $newfield, $newvalue, array $conditions=null);

	/**
	 * set_field_select
	 * Set a single field in every table record where the given conditions are used in the WHERE clause.
	 */
	public function set_field_select ($table, $newfield, $newvalue, $select, array $params=null);

	//*************************************************************
	// Deleting records
	//*************************************************************

	/**
	 * delete_records
	 * Delete records from the table where all the given conditions are met.
	 */
	public function delete_records ($table, array $conditions=null);

	/**
	 * delete_records_select
	 * Delete records from the table where the given conditions are used in the WHERE clause.
	 */
	public function delete_records_select ($table, $select, array $params=null);

	//*************************************************************
	// Inserting records
	//*************************************************************

	/**
	 * insert_record
	 * Insert the given data object into the table and return the "id" of the newly created record.
	 */
	public function insert_record ($table, $dataobject, $returnid=true, $bulk=false);

	/**
	 * insert_records
	 * Insert multiple records into the table as fast as possible. Records are inserted in the given order, but the operation is not atomic. Use transactions if necessary.
	 */
	public function insert_records ($table, $dataobjects);

	/**
	 * insert_record_raw
	 * For rare cases when you also need to specify the ID of the record to be inserted.
	 */
	public function insert_record_raw ($table, $dataobjects);

	//*************************************************************
	// Updating records
	//*************************************************************

	/**
	 * update_record
	 * Update a record in the table. The data object must have the property "id" set.
	 */
	public function update_record ($table, $dataobject, $bulk=false);

	//*************************************************************
	// Executing a custom query
	//*************************************************************

	/**
	 * execute
	 * If you need to perform a complex update using arbitrary SQL, you can use the low level "execute" method. Only use this when no specialised method exists.
	 * Do NOT use this to make changes in database structure, use database_manager methods instead!
	 */
	public function execute ($sql, array $params=null);

	//*************************************************************
	// Using recordsets
	//*************************************************************

	/**
	 * get_recordset
	 * Return a list of records as a moodle_recordset where all the given conditions are met.
	 */
	public function get_recordset ($table, array $conditions=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0);

	/**
	 * get_recordset_select
	 * Return a list of records as a moodle_recordset where the given conditions are used in the WHERE clause.
	 */
	public function get_recordset_select ($table, $select, array $params=null, $sort='', $fields='*', $limitfrom=0, $limitnum=0);

	/**
	 * get_recordset_sql
	 * Return a list of records as a moodle_recordset using a custom SELECT query.
	 */
	public function get_recordset_sql ($sql, array $params=null, $limitfrom=0, $limitnum=0);

	/**
	 * get_recordset_list
	 * Return a list of records as a moodle_recordset where the given field matches one of the possible values.
	 */
	public function get_recordset_list ($table, $field, array $values, $sort='', $fields='*', $limitfrom='', $limitnum='');

	//*************************************************************
	// Delegated transactions
	//*************************************************************

	/**
	 * start_delegated_transaction
	 * @return TransactionClass { function allow_commit() function rollback ($ex) }
	 */
	public function start_delegated_transaction ();

	//*************************************************************
	// Cross-DB compatibility
	//*************************************************************

	/**
	 * sql_bitand
	 * Return the SQL text to be used in order to perform a bitwise AND operation between 2 integers.
	 */
	public function sql_bitand ($int1, $int2);

	/**
	 * sql_bitnot
	 * Return the SQL text to be used in order to perform a bitwise NOT operation on the given integer.
	 */
	public function sql_bitnot ($int1);

	/**
	 * sql_bitor
	 * Return the SQL text to be used in order to perform a bitwise OR operation between 2 integers.
	 */
	public function sql_bitor ($int1, $int2);

	/**
	 * sql_bitxor
	 * Return the SQL text to be used in order to perform a bitwise XOR operation between 2 integers.
	 */
	public function sql_bitxor ($int1, $int2);

	/**
	 * sql_null_from_clause
	 * Return an empty FROM clause required by some DBs in all SELECT statements.
	 */
	public function sql_null_from_clause ();

	/**
	 * sql_ceil
	 * Return the correct CEIL expression applied to the given fieldname.
	 */
	public function sql_ceil ($fieldname);

	/**
	 * sql_equal
	 * Return the query fragment to perform cross-db varchar comparisons when case-sensitiveness is important.
	 */
	public function sql_equal ($fieldname, $param, $casesensitive = true, $accentsensitive = true, $notequal = false);

	/**
	 * sql_like
	 * Return the query fragment to perform the LIKE comparison.
	 */
	public function sql_like ($fieldname, $param, $casesensitive = true, $accentsensitive = true, $notlike = false, $escapechar = ' \\ ');

	/**
	 * sql_like_escape
	 * Escape the value submitted by the user so that it can be used for partial comparison and the special characters like '_' or '%' behave as literal characters, not wildcards.
	 */
	public function sql_like_escape ($text, $escapechar = '\\');

	/**
	 * sql_length
	 * Return the query fragment to be used to calculate the length of the expression in characters.
	 */
	public function sql_length ($fieldname);

	/**
	 * sql_modulo
	 * Return the query fragment to be used to calculate the remainder after division.
	 */
	public function sql_modulo ($int1, $int2);

	/**
	 * sql_position
	 * Return the query fragment for searching a string for the location of a substring. If both needle and haystack use placeholders, you must use named placeholders.
	 */
	public function sql_position ($needle, $haystack);

	/**
	 * sql_substr
	 * Return the query fragment for extracting a substring from the given expression.
	 */
	public function sql_substr ($expr, $start, $length=false);

	/**
	 * sql_cast_char2int
	 * Return the query fragment to cast a CHAR column to INTEGER
	 */
	public function sql_cast_char2int ($fieldname, $text=false);

	/**
	 * sql_cast_char2real
	 * Return the query fragment to cast a CHAR column to REAL (float) number
	 */
	public function sql_cast_char2real ($fieldname, $text=false);

	/**
	 * sql_compare_text
	 * Return the query fragment to be used when comparing a TEXT (clob) column with a given string or a VARCHAR field (some RDBMs do not allow for direct comparison).
	 */
	public function sql_compare_text ($fieldname, $numchars=32);

	/**
	 * sql_order_by_text
	 * Return the query fragment to be used to get records ordered by a TEXT (clob) column. Note this affects the performance badly and should be avoided if possible.
	 */
	public function sql_order_by_text ($fieldname, $numchars=32);

	/**
	 * sql_concat
	 * Return the query fragment to concatenate all given paremeters into one string.
	 */
	public function sql_concat ();

	/**
	 * sql_group_concat
	 * Return SQL for performing group concatenation on given field/expression.
	 */
	public function sql_group_concat (string $field, string $separator = ', ', string $sort = '');

	/**
	 * sql_concat_join
	 * Return the query fragment to concatenate all given elements into one string using the given separator.
	 */
	public function sql_concat_join ($separator="' '", $elements=array());

	/**
	 * sql_fullname
	 * Return the query fragment to concatenate the given $firstname and $lastname
	 */
	public function sql_fullname ($first='firstname', $last='lastname');

	/**
	 * sql_isempty
	 * Return the query fragment to check if the field is empty
	 */
	public function sql_isempty ($tablename, $fieldname, $nullablefield, $textfield);

	/**
	 * sql_isnotempty
	 * Return the query fragment to check if the field is not empty
	 */
	public function sql_isnotempty ($tablename, $fieldname, $nullablefield, $textfield);

	/**
	 * get_in_or_equal
	 * Return the query fragment to check if a value is IN the given list of items (with a fallback to plain equal comparison if there is just one item)
	 */
	public function get_in_or_equal ($items, $type=SQL_PARAMS_QM, $prefix='param', $equal=true, $onemptyitems=false);

	/**
	 * sql_regex_supported
	 * Does the current database driver support regex syntax when searching?
	 */
	public function sql_regex_supported ();

	/**
	 * sql_regex
	 * Return the query fragment to perform a regex search.
	 */
	public function sql_regex ($positivematch = true, $casesensitive = false);

	/**
	 * sql_intersect
	 * Return the query fragment that allows to find intersection of two or more queries
	 */
	public function sql_intersect ($selects, $fields);

}