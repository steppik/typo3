<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * PHP SQL engine
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  102: class t3lib_sqlengine extends t3lib_sqlparser
 *  124:     function init($config, &$pObj)
 *  132:     function resetStatusVars()
 *  145:     function processAccordingToConfig(&$value,$fInfo)
 *
 *              SECTION: SQL queries
 *  200:     function exec_INSERTquery($table,$fields_values)
 *  267:     function exec_UPDATEquery($table,$where,$fields_values)
 *  326:     function exec_DELETEquery($table,$where)
 *  377:     function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit)
 *  420:     function sql_query($query)
 *  431:     function sql_error()
 *  440:     function sql_insert_id()
 *  449:     function sql_affected_rows()
 *  459:     function quoteStr($str)
 *
 *              SECTION: SQL admin functions
 *  484:     function admin_get_tables()
 *  495:     function admin_get_fields($tableName)
 *  506:     function admin_get_keys($tableName)
 *  517:     function admin_query($query)
 *
 *              SECTION: Data Source I/O
 *  542:     function readDataSource($table)
 *  554:     function saveDataSource($table)
 *
 *              SECTION: SQL engine functions
 *  583:     function selectFromData($table,$where)
 *  619:     function select_evalSingle($table,$config,&$itemKeys)
 *  740:     function getResultSet($keys, $table, $fieldList)
 *
 *              SECTION: Debugging
 *  785:     function debug_printResultSet($array)
 *
 *
 *  824: class t3lib_sqlengine_resultobj
 *  838:     function sql_num_rows()
 *  847:     function sql_fetch_assoc()
 *  858:     function sql_fetch_row()
 *  876:     function sql_data_seek($pointer)
 *  889:     function sql_field_type()
 *
 * TOTAL FUNCTIONS: 27
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */






require_once(PATH_t3lib.'class.t3lib_sqlparser.php');


/**
 * PHP SQL engine / server
 * Some parts are experimental for now.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_sqlengine extends t3lib_sqlparser {

		// array with data records: [table name][num.index] = records
	var $data = array();						// Data source storage


		// Internal, SQL Status vars:
	var $errorStatus = '';						// Set with error message of last operation
	var $lastInsertedId = 0;					// Set with last inserted unique ID
	var $lastAffectedRows = 0;					// Set with last number of affected rows.





	/**
	 * Dummy function for initializing SQL handler. Create you own in derived classes.
	 *
	 * @param	array		Configuration array from handler
	 * @param	object		Parent object
	 * @return	void
	 */
	function init($config, &$pObj)	{
	}

	/**
	 * Reset SQL engine status variables (insert id, affected rows, error status)
	 *
	 * @return	void
	 */
	function resetStatusVars()	{
		$this->errorStatus = '';
		$this->lastInsertedId = 0;
		$this->lastAffectedRows = 0;
	}

	/**
	 * Processing of update/insert values based on field type.
	 *
	 * @param	[type]		$$value: ...
	 * @param	[type]		$fInfo: ...
	 * @return	[type]		...
	 */
	function processAccordingToConfig(&$value,$fInfo)	{
		$options = $this->parseFieldDef($fInfo['Type']);

		switch(strtolower($options['fieldType']))	{
			case 'int':
			case 'smallint':
			case 'tinyint':
			case 'mediumint':
				$value = intval($value);
				if ($options['featureIndex']['UNSIGNED'])	{
					$value = t3lib_div::intInRange($value,0);
				}
			break;
			case 'double':
				$value = (double)$value;
			break;
			case 'varchar':
			case 'char':
				$value = substr($value,0,trim($options['value']));
			break;
			case 'text':
			case 'blob':
				$value = substr($value,0,65536);
			break;
			case 'tinytext':
			case 'tinyblob':
				$value = substr($value,0,256);
			break;
			case 'mediumtext':
			case 'mediumblob':
				// ??
			break;
		}
	}







	/********************************
	 *
	 * SQL queries
	 * This is the SQL access functions used when this class is instantiated as a SQL handler with DBAL. Override these in derived classes.
	 *
	 ********************************/

	/**
	 * Execute an INSERT query
	 *
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs.
	 * @return	boolean		TRUE on success and FALSE on failure (error is set internally)
	 */
	function exec_INSERTquery($table,$fields_values)	{

			// Initialize
		$this->resetStatusVars();

			// Reading Data Source if not done already.
		$this->readDataSource($table);

			// If data source is set:
		if (is_array($this->data[$table]))	{

			$fieldInformation = $this->admin_get_fields($table);		// Should cache this...!

				// Looking for unique keys:
			$saveArray = array();
			foreach($fieldInformation as $fInfo)	{

					// Field name:
				$fN = $fInfo['Field'];

					// Set value:
				$saveArray[$fN] = isset($fields_values[$fN]) ? $fields_values[$fN] : $options['Default'];

					// Process value:
				$this->processAccordingToConfig($saveArray[$fN], $fInfo);

					// If an auto increment field is found, find the largest current uid:
				if ($fInfo['Extra'] == 'auto_increment')	{

						// Get all UIDs:
					$uidArray = array();
					foreach($this->data[$table] as $r)	{
						$uidArray[] = $r[$fN];
					}

						// If current value is blank or already in array, we create a new:
					if (!$saveArray[$fN] || in_array(intval($saveArray[$fN]), $uidArray))	{
						if (count($uidArray))	{
							$saveArray[$fN] = max($uidArray)+1;
						} else $saveArray[$fN] = 1;
					}

						// Update "last inserted id":
					$this->lastInsertedId = $saveArray[$fN];
				}
			}
#debug(array($fields_values,$saveArray));
				// Insert row in table:
			$this->data[$table][] = $saveArray;

				// Save data source
			$this->saveDataSource($table);

			return TRUE;
		} else $this->errorStatus = 'No data loaded.';

		return FALSE;
	}

	/**
	 * Execute UPDATE query on table
	 *
	 * @param	string		Table name
	 * @param	string		WHERE clause
	 * @param	array		Field values as key=>value pairs.
	 * @return	boolean		TRUE on success and FALSE on failure (error is set internally)
	 */
	function exec_UPDATEquery($table,$where,$fields_values)	{

			// Initialize:
		$this->resetStatusVars();

			// Reading Data Source if not done already.
		$this->readDataSource($table);

			// If anything is there:
		if (is_array($this->data[$table]))	{

				// Parse WHERE clause:
			$where = $this->parseWhereClause($where);

			if (is_array($where))	{

					// Field information
				$fieldInformation = $this->admin_get_fields($table);		// Should cache this...!

					// Traverse fields to update:
				foreach($fields_values as $fName => $fValue)	{
					$this->processAccordingToConfig($fields_values[$fName],$fieldInformation[$fName]);
				}
#debug($fields_values);
					// Do query, returns array with keys to the data array of the result:
				$itemKeys = $this->selectFromData($table,$where);

					// Set "last affected rows":
				$this->lastAffectedRows = count($itemKeys);

					// Update rows:
				if ($this->lastAffectedRows)	{
						// Traverse result set here:
					foreach($itemKeys as $dataArrayKey)	{

							// Traverse fields to update:
						foreach($fields_values as $fName => $fValue)	{
							$this->data[$table][$dataArrayKey][$fName] = $fValue;
						}
					}

					// Save data source
					$this->saveDataSource($table);
				}

				return TRUE;
			} else $this->errorStatus = 'WHERE clause contained errors: '.$where;
		} else $this->errorStatus = 'No data loaded.';

		return FALSE;
	}

	/**
	 * Execute DELETE query
	 *
	 * @param	string		Table to delete from
	 * @param	string		WHERE clause
	 * @return	boolean		TRUE on success and FALSE on failure (error is set internally)
	 */
	function exec_DELETEquery($table,$where)	{

			// Initialize:
		$this->resetStatusVars();

			// Reading Data Source if not done already.
		$this->readDataSource($table);

			// If anything is there:
		if (is_array($this->data[$table]))	{

				// Parse WHERE clause:
			$where = $this->parseWhereClause($where);

			if (is_array($where))	{

					// Do query, returns array with keys to the data array of the result:
				$itemKeys = $this->selectFromData($table,$where);

					// Set "last affected rows":
				$this->lastAffectedRows = count($itemKeys);

					// Remove rows:
				if ($this->lastAffectedRows)	{
						// Traverse result set:
					foreach($itemKeys as $dataArrayKey)	{
						unset($this->data[$table][$dataArrayKey]);
					}

						// Saving data source
					$this->saveDataSource($table);
				}

				return TRUE;
			} else $this->errorStatus = 'WHERE clause contained errors: '.$where;
		} else $this->errorStatus = 'No data loaded.';

		return FALSE;
	}

	/**
	 * Execute SELECT query
	 *
	 * @param	string		List of fields to select from the table. This is what comes right after "SELECT ...". Required value.
	 * @param	string		Table(s) from which to select. This is what comes right after "FROM ...". Required value.
	 * @param	string		Optional additional WHERE clauses put in the end of the query. NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself! DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @return	object		Returns result object, but if errors, returns false
	 */
	function exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit)	{

			// Initialize:
		$this->resetStatusVars();

			// Create result object
		$sqlObj = t3lib_div::makeInstance('t3lib_sqlengine_resultobj');
		$sqlObj->result = array();	// Empty result as a beginning

			// Get table list:
		$tableArray = $this->parseFromTables($from_table);
		$table = $tableArray[0]['table'];

			// Reading Data Source if not done already.
		$this->readDataSource($table);

			// If anything is there:
		if (is_array($this->data[$table]))	{

				// Parse WHERE clause:
			$where = $this->parseWhereClause($where_clause);
			if (is_array($where))	{

					// Do query, returns array with keys to the data array of the result:
				$itemKeys = $this->selectFromData($table,$where);

					// Finally, read the result rows into this variable:
				$sqlObj->result = $this->getResultSet($itemKeys,$table,'*');
					// Reset and return result:
				reset($sqlObj->result);
				return $sqlObj;
			} else $this->errorStatus = 'WHERE clause contained errors: '.$where;
		}  else $this->errorStatus = 'No data loaded: '.$this->errorStatus;

		return FALSE;
	}

	/**
	 * Performs an SQL query on the "database"
	 *
	 * @param	string		Query to execute
	 * @return	object		Result object or false if error
	 */
	function sql_query($query)	{
		$res = t3lib_div::makeInstance('t3lib_sqlengine_resultobj');
		$res->result = array();
		return $res;
	}

	/**
	 * Returns most recent error
	 *
	 * @return	string		Error message, if any
	 */
	function sql_error()	{
		return $this->errorStatus;
	}

	/**
	 * Returns most recently create unique ID (of INSERT queries)
	 *
	 * @return	integer		Last unique id created.
	 */
	function sql_insert_id() {
		return $this->lastInsertedId;
	}

	/**
	 * Returns affected rows (of UPDATE and DELETE queries)
	 *
	 * @return	integer		Last amount of affected rows.
	 */
	function sql_affected_rows() {
		return $this->lastAffectedRows;
	}

	/**
	 * Quoting strings for insertion in SQL queries
	 *
	 * @param	string		String
	 * @return	string		String, with quotes escaped
	 */
	function quoteStr($str)	{
		return addslashes($str);
	}










	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * (DUMMY) Returns the list of tables from the database
	 *
	 * @return	array		Tables in an array (tablename is in both key and value)
	 */
	function admin_get_tables()	{
		$whichTables = array();
		return $whichTables;
	}

	/**
	 * (DUMMY) Returns information about each field in the $table
	 *
	 * @param	string		Table name
	 * @return	array		Field information in an associative array with fieldname => field row
	 */
	function admin_get_fields($tableName)	{
		$output = array();
		return $output;
	}

	/**
	 * (DUMMY) Returns information about each index key in the $table
	 *
	 * @param	string		Table name
	 * @return	array		Key information in a numeric array
	 */
	function admin_get_keys($tableName)	{
		$output = array();
		return $output;
	}

	/**
	 * (DUMMY) mysql() wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 *
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer
	 */
	function admin_query($query)	{
		return $this->sql_query($query);
	}








	/********************************
	 *
	 * Data Source I/O
	 *
	 ********************************/

	/**
	 * Dummy function for setting table data. Create your own.
	 * NOTICE: Handler to "table-locking" needs to be made probably!
	 *
	 * @param	string		Table name
	 * @return	void
	 * @todo	Table locking tools?
	 */
	function readDataSource($table)	{
		$this->data[$table] = array();
	}

	/**
	 * Dummy function for setting table data. Create your own.
	 * NOTICE: Handler to "table-locking" needs to be made probably!
	 *
	 * @param	string		Table name
	 * @return	void
	 * @todo	Table locking tools?
	 */
	function saveDataSource($table)	{
		debug($this->data[$table]);
	}













	/********************************
	 *
	 * SQL engine functions
	 *
	 ********************************/

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$where: ...
	 * @return	[type]		...
	 */
	function selectFromData($table,$where)	{

		$output = array();
		if (is_array($this->data[$table]))	{

				// All keys:
			$OR_index = 0;

			foreach($where as $config)	{

				if (strtoupper($config['operator'])=='OR')	{
					$OR_index++;
				}

				if (!isset($itemKeys[$OR_index]))	$itemKeys[$OR_index] = array_keys($this->data[$table]);

				$this->select_evalSingle($table,$config,$itemKeys[$OR_index]);
			}

			foreach($itemKeys as $uidKeys)	{
				$output = array_merge($output, $uidKeys);
			}
			$output = array_unique($output);
		}

		return $output;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$config: ...
	 * @param	[type]		$itemKeys: ...
	 * @return	[type]		...
	 */
	function select_evalSingle($table,$config,&$itemKeys)	{
		$neg = preg_match('/^AND[[:space:]]+NOT$/',trim($config['operator']));

		if (is_array($config['sub']))	{
			$subSelKeys = $this->selectFromData($table,$config['sub']);
			if ($neg)	{
				foreach($itemKeys as $kk => $vv)	{
					if (in_array($vv,$subSelKeys))	{
						unset($itemKeys[$kk]);
					}
				}
			} else {
				$itemKeys = array_intersect($itemKeys, $subSelKeys);
			}
		} else {
			$comp = strtoupper(str_replace(array(' ',"\t","\r","\n"),'',$config['comparator']));
			$mod = strtoupper($config['modifier']);
			switch($comp)	{
				case 'NOTLIKE':
				case 'LIKE':
					$like_value = strtolower($config['value'][0]);
					if (substr($like_value,0,1)=='%')	{
						$wildCard_begin = TRUE;
						$like_value = substr($like_value,1);
					}
					if (substr($like_value,-1)=='%')	{
						$wildCard_end = TRUE;
						$like_value = substr($like_value,0,-1);
					}
				break;
				case 'NOTIN':
				case 'IN':
					$in_valueArray = array();
					foreach($config['value'] as $vParts)	{
						$in_valueArray[] = (string)$vParts[0];
					}
				break;
			}

			foreach($itemKeys as $kk => $v)	{
				$field_value = $this->data[$table][$v][$config['field']];

					// Calculate it:
				if ($config['calc']=='&')	{
					$field_value&=intval($config['calc_value']);
				}

					// Compare it:
				switch($comp)	{
					case '<=':
						$bool = $field_value <= $config['value'][0];
					break;
					case '>=':
						$bool = $field_value >= $config['value'][0];
					break;
					case '<':
						$bool = $field_value < $config['value'][0];
					break;
					case '>':
						$bool = $field_value > $config['value'][0];
					break;
					case '=':
						$bool = !strcmp($field_value,$config['value'][0]);
					break;
					case '!=':
						$bool = strcmp($field_value,$config['value'][0]);
					break;
					case 'NOTIN':
					case 'IN':
						$bool = in_array((string)$field_value, $in_valueArray);
						if ($comp=='NOTIN')	$bool = !$bool;
					break;
					case 'NOTLIKE':
					case 'LIKE':
						if (!strlen($like_value))	{
							$bool = TRUE;
						} elseif ($wildCard_begin && !$wildCard_end)	{
							$bool = !strcmp(substr(strtolower($field_value),-strlen($like_value)),$like_value);
						} elseif (!$wildCard_begin && $wildCard_end)	{
							$bool = !strcmp(substr(strtolower($field_value),0,strlen($like_value)),$like_value);
						} elseif ($wildCard_begin && $wildCard_end)	{
							$bool = strstr($field_value,$like_value);
						} else {
							$bool = !strcmp(strtolower($field_value),$like_value);
						}
						if ($comp=='NOTLIKE')	$bool = !$bool;
					break;
					default:
						$bool = $field_value ? TRUE : FALSE;
					break;
				}

					// General negation:
				if ($neg)	$bool = !$bool;

					// Modify?
				switch($mod)	{
					case 'NOT':
					case '!':
						$bool = !$bool;
					break;
				}

					// Action:
				if (!$bool)	{
					unset($itemKeys[$kk]);
				}
			}

#			echo $this->debug_printResultSet($this->getResultSet($itemKeys,$table,'uid,tstamp'));
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$keys: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$fields: ...
	 * @return	[type]		...
	 */
	function getResultSet($keys, $table, $fieldList)	{
		$fields = t3lib_div::trimExplode(',',$fieldList);

		$output = array();
		foreach($keys as $kValue)	{
			if ($fieldList=='*')	{
				$output[$kValue] = $this->data[$table][$kValue];
			} else {
				foreach($fields as $fieldName)	{
					$output[$kValue][$fieldName] = $this->data[$table][$kValue][$fieldName];
				}
			}
		}

		return $output;
	}





	/*************************
	 *
	 * Debugging
	 *
	 *************************/

	/**
	 * Returns the result set (in array) as HTML table. For debugging.
	 *
	 * @param	array		Result set array (array of rows)
	 * @return	string		HTML table
	 */
	function debug_printResultSet($array)	{

		if (count($array))	{
			$tRows=array();
			$fields = array_keys(current($array));
					$tCell[]='
							<td>IDX</td>';
				foreach($fields as $fieldName)	{
					$tCell[]='
							<td>'.htmlspecialchars($fieldName).'</td>';
				}
				$tRows[]='<tr>'.implode('',$tCell).'</tr>';


			foreach($array as $index => $rec)	{

				$tCell=array();
				$tCell[]='
						<td>'.htmlspecialchars($index).'</td>';
				foreach($fields as $fieldName)	{
					$tCell[]='
							<td>'.htmlspecialchars($rec[$fieldName]).'</td>';
				}
				$tRows[]='<tr>'.implode('',$tCell).'</tr>';
			}

			return '<table border="1">'.implode('',$tRows).'</table>';
		} else 'Empty resultset';
	}
}


/**
 * PHP SQL engine, result object
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_sqlengine_resultobj {

		// Result array, must contain the fields in the order they were selected in the SQL statement (for sql_fetch_row())
	var $result = array();

	var $TYPO3_DBAL_handlerType = '';
	var $TYPO3_DBAL_tableList = '';


	/**
	 * Counting number of rows
	 *
	 * @return	integer
	 */
	function sql_num_rows()	{
		return count($this->result);
	}

	/**
	 * Fetching next row in result array
	 *
	 * @return	array		Associative array
	 */
	function sql_fetch_assoc()	{
		$row = current($this->result);
		next($this->result);
		return $row;
	}

	/**
	 * Fetching next row, numerical indices
	 *
	 * @return	array		Numerical array
	 */
	function sql_fetch_row()	{
		$resultRow = $this->sql_fetch_assoc();

		if (is_array($resultRow))	{
			$numArray = array();
			foreach($resultRow as $value)	{
				$numArray[]=$value;
			}
			return $numArray;
		}
	}

	/**
	 * Seeking position in result
	 *
	 * @param	integer		Position pointer.
	 * @return	boolean		Returns true on success
	 */
	function sql_data_seek($pointer)	{
		reset($this->result);
		for ($a=0;$a<$pointer;$a++)	{
			next($this->result);
		}
		return TRUE;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function sql_field_type()	{
		return '';
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_sqlengine.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_sqlengine.php']);
}
?>
