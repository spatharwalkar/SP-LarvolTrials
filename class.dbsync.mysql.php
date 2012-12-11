<?php
require_once('include.util.php');
	/**
     * Class DBSync_mysql
     * Used by class DBSync to sync a MySQL database
     *
     * @author Diogo Resende <me@diogoresende.net>
     * @licence GPL
     *
     * @method DBSync_mysql::ListTables()
     * @method DBSync_mysql::ListTableFields()
     * @method DBSync_mysql::CreateTable()
     * @method DBSync_mysql::RemoteTable()
     * @method DBSync_mysql::AddTableField()
     * @method DBSync_mysql::ChangeTableField()
     * @method DBSync_mysql::RemoveTableField()
     * @method DBSync_mysql::ClearTablePrimaryKeys()
     * @method DBSync_mysql::SetTablePrimaryKeys()
     * @method DBSync_mysql::LastError()
     **/
	class DBSync_mysql {
    	var $dbp;
        var $database;
        var $host;
        var $user;
        var $pass;
        var $ok = false;

        /**
         * DBSync_mysql::DBSync_mysql()
		 * Class constructor
         *
         * @param	string	$host		Host
         * @param	string	$user		Database Username
         * @param	string	$pass		Database Password
         * @param	string	$database	Database Name
         *
         * @access	public
         * @return 	void
         **/
    	function DBSync_mysql($host, $user, $pass, $database) {
        	$this->database = $database;
            $this->host = $host;
            $this->user = $user;
            $this->pass = $pass;
        	if (($this->dbp = @mysql_pconnect($host, $user, $pass)) !== false) {
            	$this->ok = @mysql_select_db($database, $this->dbp);
                return;
            }
			$this->ok = false;
        }

        /**
         * DBSync_mysql::ListTables()
		 * List tables on current database
         *
         * @access	public
         * @return 	array	Table list
         **/
        function ListTables() {
        	$tables = array();

        	$result = mysql_query("SHOW TABLES FROM {$this->database}", $this->dbp);
            while ($row = mysql_fetch_row($result)) {
				$tables[] = $row[0];
            }

            return $tables;
        }

        /**
         * DBSync_mysql::ListTableFields()
		 * List table fields from a table on current database
         *
         * @param	string	$table	Table Name
         *
         * @access	public
         * @return 	array	Field List
         **/
        function ListTableFields($table) {
            mysql_select_db($this->database, $this->dbp);

        	$fields = array();
        	$result_index = mysql_query("SHOW INDEX FROM {$table}",$this->dbp);
        	$indexArr = array();
        	while($row = mysql_fetch_assoc($result_index))
        	{
        		$indexArr[] = $row;
        	}
        	
        	//get row format
        	$queryRowFormat = 'SELECT row_format, engine FROM information_schema.tables WHERE table_schema="'.$this->database.'" AND table_name="'.$table.'" LIMIT 1';
        	$resultRowFormat = mysql_query($queryRowFormat,$this->dbp);
        	while($rw = mysql_fetch_assoc($resultRowFormat))
        	{
        		$rowFormat = $rw['row_format'];
        	}        	
        	
        	//get foreign key associations if present for all fields in the table
        	$foreignKeyAssoc = null;
        	$foreignKeyAssoc = $this->listForeignKeyAssociations($this->database,$table);
         	$result = mysql_query("SHOW COLUMNS FROM {$table}", $this->dbp);
         	$keyDuplicateCountMemory = array();
         	$keyDuplicateBefore = null;
            while ($row = mysql_fetch_row($result)) {
	            $KeyName = null;
	            $Non_unique = null;
	            $Seq_in_index = null;
	            $Cardinality = null;
	            $Sub_part = null;
	            $indexFlag = false;
	            $multiColumnKeyNameTmp = array();
	            $keyDuplicateCount = null;
				
				//Get collation type and characterset for each field as some time collation type of field is different from table
				$FieldDetails = mysql_query("SELECT `CHARACTER_SET_NAME`, `COLLATION_NAME` FROM information_schema.columns WHERE table_schema='".$this->database."' AND table_name='".$table."' AND `COLUMN_NAME`= '{$row[0]}' LIMIT 1", $this->dbp);
				
				if($FieldDetails)
				{
					while ($FieldDetailsRow = mysql_fetch_row($FieldDetails))
					{
						$characterSet_Field = $FieldDetailsRow[0];
						$collateName_Field = $FieldDetailsRow[1];
					}
				}
				else
				{
					mysql_error();
				}
				//End of retrieval
						
				foreach($indexArr as $index)
				{
					$multiColumnKeyNameTmp[] = $index['Key_name'];
					if($row[0]==$index['Column_name'])
					{
						
						$KeyName = $index['Key_name'];
						$Non_unique = $index['Non_unique'];
						$Seq_in_index = $index['Seq_in_index'];
						$Sub_part = $index['Sub_part'];
						$Cardinality = $index['Cardinality'];
						
						$indexFlag=true;
					}
				}
				if($KeyName !='' && $KeyName!='PRIMARY')
				{	
					$multiColumnKeyNameTmp = array_count_values($multiColumnKeyNameTmp);
					$keyDuplicateCount = $multiColumnKeyNameTmp[$KeyName];
					if(in_array($multiColumnKeyNameTmp[$KeyName], $keyDuplicateCountMemory))
					{
						$keyDuplicateBefore = 1;
					}					
					$keyDuplicateCountMemory[] = $multiColumnKeyNameTmp[$KeyName];
				}
				else
				{
					$keyDuplicateCount = null;
				} 
				//add foreign key data if present for that field
				foreach($foreignKeyAssoc as $foreignKey)
				{

					if($foreignKey['foreign_key']==$row[0])
					{
						
						$foreign_key = ($foreignKey['foreign_key']!='')?$foreignKey['foreign_key']:null;
						$referenced_table_name = ($foreignKey['referenced_table_name']!='')?$foreignKey['referenced_table_name']:null;
						$referenced_column_name = ($foreignKey['referenced_column_name']!='')?$foreignKey['referenced_column_name']:null;
						$update_rule = ($foreignKey['update_rule']!='')?$foreignKey['update_rule']:null;
						$delete_rule = ($foreignKey['delete_rule']!='')?$foreignKey['delete_rule']:null;
						$constraint_name = ($foreignKey['constraint_name']!='')?$foreignKey['constraint_name']:null;
						break;

					}
					else 
					{
						$foreign_key = $referenced_table_name = $referenced_column_name = $update_rule = $delete_rule = $constraint_name = null;
					}
				}
				
				//get table detail engine status like engine collation etc.
				$query_engine = "show table status where Name='$table'";
				$result_engine = mysql_query($query_engine, $this->dbp);
				while($rw = mysql_fetch_assoc($result_engine))
				{
					$engine = $rw['Engine'];
					$collate = $rw['Collation'];
				}
				
				
				$fields[] = array(
                	'name'	  => $row[0],
                    'type'    => $row[1],
                    'null'    => $row[2],
                    'key'     => $row[3],
                    'default' => $row[4],
                    'extra'   => $row[5],
					'Non_unique' => $Non_unique,
					'Seq_in_index' => $Seq_in_index,
					'Cardinality' => $Cardinality,
					'Sub_part' => $Sub_part,
					'indexFlag' => $indexFlag,
					'key_primary' => $KeyName,
					'foreign_key' => $foreign_key,
					'referenced_table_name' => $referenced_table_name,
					'referenced_column_name' => $referenced_column_name,
					'update_rule' => $update_rule,
					'delete_rule' => $delete_rule,
					'constraint_name' => $constraint_name,
					'Engine' => $engine,
					'Collation' => (($collateName_Field) ? $collateName_Field:$collate),	//if field collation is specified use it otherwise use table collation for that field
					'CharacterSet' => $characterSet_Field,	//if field collation set is specified use it
					'row_format' => $rowFormat,
					'key_duplicate_count' => $keyDuplicateCount	,
					'key_duplicate_before' => $keyDuplicateBefore	
                );
            }
            return $fields;
        }
        
        /**
         * DBSync_mysql::listForeignKeyAssociations()
		 * Lists all foreign keys associations
		 * in the current table/field
         * @param	string	$table	Table Name
         * @param string Field Name
         * @access	public
         * @return 	array	Foreign Key Associations
         * @author Jithu Thomas
         **/        
        function listForeignKeyAssociations($schema,$table,$field=null)
        {
        	$query = "SELECT distinct kcu.table_schema,kcu.table_name,kcu.column_name as 'foreign_key', kcu.referenced_table_name, kcu.referenced_column_name,rc.update_rule,rc.delete_rule,rc.constraint_name
						FROM information_schema.key_column_usage kcu, information_schema.referential_constraints rc
						WHERE
						kcu.table_schema = '$schema' AND 
						kcu.table_name = '$table' AND 
						kcu.referenced_table_name is not null AND
						kcu.referenced_table_name  = rc.referenced_table_name AND
						rc.table_name = '$table' AND
						rc.constraint_schema = '$schema';";
        /* 	if($schema=='clinicaltrials' && $table=='rpt_masterhm')
        	{
        		//debugecho $query;
        	}
 */        	$associations = array();
        	$res = mysql_query($query);
        	while($row = mysql_fetch_assoc($res))
        	{
        		$associations[] = $row;
        	}
        	return ($associations);die;
        }

        /**
         * DBSync_mysql::CreateTable()
		 * Create a table on current database
         *
         * @param	string	$name		Table Name
         * @param	array	$fields		Field List
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function CreateTable($name, $fields) {
            mysql_select_db($this->database, $this->dbp);
        	$primary_keys = array();
        	$index_keys = array();
        	$unique_keys = array();
        	$special_mul_keys = null;
            $sql_f = array();
            for ($i = 0; $i < count($fields); $i++) {
            	if ($fields[$i]['key_primary'] == 'PRIMARY') {
                	$primary_keys[] = $fields[$i]['name'];
                }
                if($fields[$i]['indexFlag']===true && $fields[$i]['key_primary'] != 'PRIMARY' )
                {
                	if($fields[$i]['Non_unique']==1 &&  $fields[$i]['key']!='MUL')
                	{
                		$index_keys[] = $fields[$i]['name'];
                	}
                    if($fields[$i]['Non_unique']==1 &&  $fields[$i]['key']=='MUL' && $fields[$i]['Sub_part']!='')
                	{
                		$special_mul_key = ', KEY `'.$fields[$i]['name'].'` (`'.$fields[$i]['name'].'`('.$fields[$i]['Sub_part'].'))';
                	}                	
                	if($fields[$i]['Non_unique']==0)
                	{
                		$unique_keys[] = $fields[$i]['name'];
                	}
                }
                $sql_f[] = "`{$fields[$i]['name']}` {$fields[$i]['type']} " . ($fields[$i]['CharacterSet']!='' ? " CHARACTER SET {$fields[$i]['CharacterSet']} " : '') . ($fields[$i]['Collation']!='' ? " COLLATE {$fields[$i]['Collation']} " : '') . ($fields[$i]['null'] =='YES'?'' : 'NOT') . ' NULL' . (strlen($fields[$i]['default']) > 0 ? " default '{$fields[$i]['default']}'" : '') . ($fields[$i]['extra'] == 'auto_increment' ? ' auto_increment' : '');
            }

            $sql = "CREATE TABLE `{$name}` (" . implode(', ', $sql_f) . (count($primary_keys) > 0 ? ", PRIMARY KEY (`" . implode('`, `', $primary_keys) . "`)" : '') . (count($index_keys) > 0 ? ", INDEX (`" . implode('`, `', $index_keys) . "`)" : '') . (count($unique_keys) > 0 ? ", UNIQUE (`" . implode('`, `', $unique_keys) . "`)" : '') .  ($special_mul_key?$special_mul_key:'') . ') ENGINE='.$fields[0]['Engine'].' COLLATE='.$fields[0]['Collation'].' ROW_FORMAT='.$fields[0]['row_format'];
			echo($sql.';<br />');
            return true;
        }

        /**
         * DBSync_mysql::RemoveTable()
		 * Remove a table from current database
         *
         * @param	string	$name		Table Name
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function RemoveTable($table) {
            mysql_select_db($this->database, $this->dbp);

			$sql = "DROP TABLE `{$table}`";
			echo($sql.';<br />');
            return true;
        }

        /**
         * DBSync_mysql::AddTableField()
		 * Add a field to a table on current database
         *
         * @param				string	$table			Table Name
         * @param				array	$field			Field Information
         * @param	optional	string	$field_before	Field before the field to be added
         *												(if $field_before = 0 this field will
         *												be added at the begining of the table)
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function AddTableField($table, $field, $field_before = 0) {
        	$sql1 = "ALTER TABLE `{$table}` DROP PRIMARY KEY";
			$sql = "ALTER TABLE `{$table}` ADD `{$field['name']}` {$field['type']} " . (trim($field['CharacterSet'])!='' ? " CHARACTER SET {$field['CharacterSet']} " : '') . (trim($field['Collation'])!='' ? " COLLATE {$field['Collation']} " : '') . ($field['null']=='YES' ? '' : 'NOT') . ' NULL' . (strlen($field['default']) > 0 ? " default '{$field['default']}'" : '') . ($field['extra'] == 'auto_increment' ? ' auto_increment' : '') . (!is_string($field_before) ? ' FIRST' : " AFTER `{$field_before}`") . ($field['key'] == 'PRIdisabled' ? ", ADD PRIMARY KEY (`{$field['name']}`)" : '');
			echo($sql.';<br />');
            return true;
        }

        /**
         * DBSync_mysql::ChangeTableField()
		 * Change a field on a table on current database
         *
         * @param	string	$table		Table Name
         * @param	string	$field		Field Name
         * @param	array	$new_field	New Field Information
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function ChangeTableField($table, $field, $new_field,$old_field=array(),$foreignKeyCheck=0,$keys_home=array(),$fieldNamesOrderHome=array(),$fieldNamesOrderSync=array(),$fields_home=array(),$fields_sync=array()) {
        	
        	switch($foreignKeyCheck)
        	{
	        	case 0:
	        		
				$skipModify = null;	        		
	        	//special case detected for mul keys
	        	$special_mul_key = null;
	        	$indexKey = null;
				$Collation = null;
				$AlreadyIndex = null;
	        	//pr($new_field);
	        	//pr($old_field);
	        	//die;
	        	if($new_field['key']=='MUL' && $old_field['key']=='' && $new_field['Sub_part']!=$old_field['Sub_part'] && !$new_field['key_duplicate_before'] && !$new_field['key_duplicate_count'])
	        	{
	        		$special_mul_key = ', ADD KEY `'.$field.'` (`'.$field.'`('.$new_field['Sub_part'].'))';
	        		$skipModify = 1;
					$AlreadyIndex = 1;
	        	}
	        	if($new_field['key']=='MUL' && $old_field['key']=='MUL' && $new_field['Sub_part']!=$old_field['Sub_part'] && !$new_field['key_duplicate_before'] && !$new_field['key_duplicate_count'])
	        	{
	        		$special_mul_key = ', DROP INDEX `'.$old_field['name'].'`, ADD KEY `'.$field.'` (`'.$field.'`('.$new_field['Sub_part'].'))';
	        		$skipModify = 1;
					$AlreadyIndex = 1;
	        	}	        	
	        	if($new_field['Non_unique']=='1' && $old_field['Non_unique']=='0' && $new_field['indexFlag']==1 && $old_field['indexFlag']==1)
	        	{
	        		$special_mul_key = ', DROP INDEX `'.$old_field['name'].'` , ADD INDEX `'.$old_field['name'].'` ( `'.$old_field['name'].'` )';
	        		$skipModify = 1;
					$AlreadyIndex = 1;
	        	}
	        	if($new_field['key']=='MUL' && $old_field['key']=='' && $new_field['indexFlag']==1 && $old_field['indexFlag']=='' && !$new_field['key_duplicate_before'] && !$new_field['key_duplicate_count'])
	        	{
	        		$indexKey = ', ADD INDEX (`'.$field.'`) ';
	        		$skipModify = 1;
					$AlreadyIndex = 1;
	        	}
				
				if(($new_field['key']=='MUL' && $old_field['key']=='' || $new_field['indexFlag']==1 && $old_field['indexFlag']=='') && !$AlreadyIndex && $new_field['key']!='PRI')
	        	{
	        		$indexKey = ', ADD INDEX (`'.$field.'`) ';	//Special case when key is not primary and we have not indexed it, but change is to make it index
	        		$skipModify = 1;
	        	}
				
				if($new_field['Collation'] != $old_field['Collation'] || $new_field['CharacterSet'] != $old_field['CharacterSet'])
	        	{
	        		$Collation = '';
					if(trim($new_field['CharacterSet']) != '')
					$Collation = ' CHARACTER SET ' . $new_field['CharacterSet'] . ' ';
					if(trim($new_field['Collation']) != '')
					$Collation = ' COLLATE ' . $new_field['Collation'] .' ';
					if(trim($Collation) != '')
	        		$skipModify = 1;
	        	}
				
	        	if(($new_field['key']=='UNI' || $new_field['Non_unique'] == 0)  && $new_field['type']=='blob')
	        	{
	        		//if its a multi column key and already processed no need to worry about it again.
	        		if(!$new_field['key_duplicate_before'])
	        		{
		        		//need to remove old index before adding blob and also suggest blob index if any present
		        		if($old_field['indexFlag']==1)
		        		{
			        		$sql =  "ALTER table `{$table}` DROP INDEX {$old_field['key_primary']}";
			        		echo $sql.';<br />';
		        		}
		        		if($new_field['key_duplicate_count'] > 1)
		        		{
		        			$keyPart = null;
		        			$keyPartArr = null;
		        			$new_fieldTmp = null;
		        			$keyPart = '`'.$new_field['key_primary'].'` (';
		        			foreach($fields_home as $new_fieldTmp)
		        			{
		        				if($new_fieldTmp['key_primary']==$new_field['key_primary'])
		        				$keyPartArr[] = '`'.$new_fieldTmp['name'].'`('.$new_fieldTmp['Sub_part'].')';
		        			}
		        			$keyPart .= implode(',',$keyPartArr);
		        			$keyPart .= ')';
		        		}
		        		else 
		        		{
		        			$keyPart = '`'.$field.'` (`'.$field.'`('.$new_field['Sub_part'].'))';
		        		}
		        		$special_uni_key = ', ADD UNIQUE '.$keyPart;
		        		$skipModify = 1;
	        		
	        		}
	        		else 
	        		{
	        			return true;
	        		}
    		
	        	}
	        	//check primary key defintion needed or not
	        	if($old_field['key']=='PRI' && $new_field['key']=='PRI' || count($keys_home)>1)
	        	$no_primary_def_needed = 1;
	        	

	        	//decide change/modify
	        	//TODO:remove all test comments
	        	/*
	        	 * Array Home $fieldNamesOrderHome
					(
					    [0] => id
					    [1] => name
					    [2] => type
					    [3] => level
					)
						
					Array Sync $fieldNamesOrderSync
					(
					    [0] => id
					    [1] => level
					    [2] => type
					    [3] => name
					)

	        	 * */
	        	//by default $change ='CHANGE'
	        	$change = 'CHANGE';
	        	$after = null;
	        	$newFieldHomeKey = array_search($new_field['name'],$fieldNamesOrderHome);
	        	$newFieldSyncKey = array_search($new_field['name'],$fieldNamesOrderSync);
	        	if($newFieldHomeKey != $newFieldSyncKey && $skipModify == null)
	        	{
	        		//required for reordering alter query
	        		$change = 'MODIFY';
	        		//$after is to be defined if $change ='MODIFIED' or else query totaly fails.
	        		$after = $fieldNamesOrderHome[$newFieldHomeKey-1];
	        	}
	        	
				$sql = $this->ChangeTableFieldQuery($table, $change, $field, $new_field, $no_primary_def_needed, $special_mul_key, $indexKey, $special_uni_key,$after,$Collation);
				echo($sql.';<br />');
/* 				if($table=='rpt_ott_upm' && ($new_field['name'] == 'intervention_name_negate' || $new_field['name'] == 'intervention_name'))
				{
					pr($new_field);pr($old_field);
				} */
/* 				// TODO:remove this later after user testing.
				// TODO:end */
				
				return true;
				break;	
				
	        	case 1:
				//check for foreign key alterations after the appropriate field changes for the field (if present) is defined.
				if($old_field['constraint_name']!='')
				{
					$sql = "ALTER TABLE `{$table}` DROP FOREIGN KEY `{$old_field['constraint_name']}` ;";
					echo $sql.'<br/>';
				}
				$foreignKeyAlteration = true;
				if($foreignKeyAlteration)
				{
					if($new_field['foreign_key']!='')
					{
						$constrainClause = 'ADD';
						$constrain = $new_field['foreign_key'];
						$constrainType = 'FOREIGN KEY';
						$referencedTableName = $new_field['referenced_table_name'];
						$referencedColumnName = $new_field['referenced_column_name'];
						$deleteRule = $new_field['delete_rule'];
						$updateRule = $new_field['update_rule'];
					}
					else 
					{
						return true;
					}
					
				}
				if($foreignKeyAlteration)
				$sql = "ALTER TABLE `$table` $constrainClause  $constrainType (`$constrain`) REFERENCES `$referencedTableName` (`$referencedColumnName`) ON DELETE $deleteRule ON UPDATE $updateRule;";
				echo $sql.'<br/>';
	            return true;
	            break;
        	}
        }

        /**
        * DBSync_mysql::ChangeTableFieldQuery()
        * @tutorial Returns the query for changeTableField function
        * @access	public
        * @return 	mysql result
        * @author Jithu Thomas
        **/        
        function ChangeTableFieldQuery($table,$changeOrModify,$field,$new_field,$no_primary_def_needed,$special_mul_key,$indexKey,$special_uni_key,$after=null,$Collation)
        {
            $sql = "ALTER TABLE `{$table}` ";
        	
        	$sql .= " $changeOrModify ";
        	
        	if($changeOrModify=='CHANGE')
        	{
        		$sql .= "`{$field}`";
        	}
        	else
        	{ 
        		$sql .= null;
        	}
        	$sql .= " `{$new_field['name']}` {$new_field['type']} ";
			
			if($Collation)
			{
				$sql .= $Collation;
			}
        	
        	if($new_field['null']=='YES')
        	{
        		$sql .= '';
        	}
        	else
        	{
        		$sql .= 'NOT';
        	}
        	
        	$sql .= ' NULL';
        	
        	if(strlen($new_field['default']) > 0 )
        	{
        		$sql .= " default '{$new_field['default']}'";
        	}

        	
        	if($new_field['extra'] == 'auto_increment')
        	{
        		$sql .=' auto_increment';
        	}

        	
        	if($new_field['key'] == 'PRI' && $no_primary_def_needed!=1  )
        	{
        		$sql .= ", ADD PRIMARY KEY (`{$new_field['name']}` ";
				if($new_field['type']=='text')	//For primary key of type text we reqire to specify size
				$sql .= " ({$new_field['Sub_part']}) ";
				$sql .= ") ";
        	}


       		if($special_mul_key)
       		{
       			$sql .=$special_mul_key;
       		}
       		
       		if($indexKey)
       		{
       			$sql .=$indexKey;
       		}
       		
       		if($special_uni_key)
       		{
       			$sql .=$special_uni_key;
       		}
       		
       		if($after)
       		{
       			$sql .=' AFTER `'.$after.'`';
       		}
       		
        	return $sql;
        }
        
        /**
        * DBSync_mysql::ChangeTableFieldQuery()
        * @tutorial Returns the query for changeTableField function
        * @access	public
        * @return 	mysql result
        * @author Jithu Thomas
        **/
        function ChangeTableRowFormatEngine($table,$new_field,$old_field)
        {
            //check row_format
	        if($old_field['row_format'] != $new_field['row_format'])
	        {
	        	$sql = "ALTER TABLE `$table`  ENGINE = {$new_field['Engine']} ROW_FORMAT={$new_field['row_format']}";
	        	echo $sql.';<br />';
	        }
        }        

        /**
         * DBSync_mysql::RemoveTableField()
		 * Remove a field from a table on current database
         *
         * @param	string	$table		Table Name
         * @param	string	$field		Field Name
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function RemoveTableField($table, $field) {
			$sql = "ALTER TABLE `{$table}` DROP `{$field}`";
			echo($sql.';<br />');
            return true;
        }

        /**
         * DBSync_mysql::ClearTablePrimaryKeys()
		 * Clear primary keys on a table on current database
         *
         * @param	string	$table		Table Name
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function ClearTablePrimaryKeys($table) {
        	$sql = "ALTER TABLE `{$table}` DROP PRIMARY KEY";
			echo($sql.';<br />');
            return true;
        }

        /**
         * DBSync_mysql::SetTablePrimaryKeys()
		 * Clears primary keys and sets new ones on a table on current database
         *
         * @param	string	$table		Table Name
         * @param	array	$keys		Primary Keys List
         *
         * @access	public
         * @return 	boolean	Success
         **/
        function SetTablePrimaryKeys($table, $keys) {
        	$sql = "ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD PRIMARY KEY (`" . implode('`, `', $keys) . "`)";
			echo($sql.';<br />');
            return true;
        }

        /**
         * DBSync_mysql::LastError()
		 * Returns last error message from MySQL server
         *
         * @access	public
         * @return 	string	Error Message
         **/
        function LastError() {
        	return mysql_error($this->dbp);
        }
       /**
         * DBSync_mysql::getData()
		 * @tutorial Returns all data for the table
         * @access	public
         * @return 	mysql result 
         * @author Jithu Thomas
         **/
        function getData($table)
        {
        	switch($table)
        	{
        		case 'data_fields':
        			$sql = "SELECT df . * , dc.name AS dcname FROM `$this->database`.data_fields df LEFT JOIN `$this->database`.data_categories dc ON df.category = dc.id";
        			break;
        		case 'data_enumvals':
        			$sql = "SELECT de . * , df.name AS dfname FROM `$this->database`.data_enumvals de LEFT JOIN `$this->database`.data_fields df ON df.id = de.field";
        			break;
        			
        		default:
        			$sql = 'select * from `'.$this->database.'`.'.$table.'`';
        			break;
        			
        	}
        	$result = mysql_query($sql,$this->dbp);
        	while($row = mysql_fetch_assoc($result))
        	{
        		$out[] = $row;
        	}
        	return $out;
        }  
        
       /**
         * DBSync_mysql::simpleInsert()
		 * @tutorial Returns insert query for data sync
         * @param string $table
         * @param array $columns
         * @param array $values
         * @access	public
         * @return 	string query
         * @author Jithu Thomas
         **/
        function simpleInsert($table,$columns,$values)
        {
        	$values = array_map(function($val){
        		if(!is_array($val))
        		$val = array($val);
        		if(is_array($val)&& count($val)>0)
        		{
        			$val = array_map(function($tmp){
        				return '\''.mysql_escape_string($tmp).'\'';
        			},$val);
        			$val = implode(',',$val);
        		}
        		return '('.$val.')';
        	},$values);
        	$sql = 'INSERT INTO `'.$table.'` ('.implode(',',$columns).') VALUES '.implode(',',$values).';<br>';
        	echo $sql;
        }

	     /**
         * DBSync_mysql::simpleDelete()
		 * @tutorial Returns delete query for data sync
         * @param string $table
         * @param array $columns
         * @param array $values
         * @access	public
         * @return 	string query
         * @author Jithu Thomas
         **/
        function simpleDelete($table,$values)
        {
        	$values = array_map(function($val){
        		foreach($val as $ky=>$value)
        		{
        			$tmp[] = "`$ky` = '".mysql_escape_string($value)."'";
        		}
        		return implode(' AND ',$tmp);
        	},$values);
        	if(count($values)>1)
        	$sql = 'DELETE FROM `'.$table.'` WHERE '.implode(' OR ',$values).' LIMIT '.count($values).';<br>';
        	else
        	$sql = 'DELETE FROM `'.$table.'` WHERE '.implode(' ',$values).' LIMIT 1;<br>';
        	echo $sql;
        }  

	     /**
         * DBSync_mysql::categoryIdMap()
		 * @tutorial provides a name/id for data_fields/data_categories as per input Arr
         * @param array $insertArr
         * @param string $home
         * @param string $sync
         * @param string $table
         * @access	public
         * @return 	array with mapped values
         * @author Jithu Thomas
         **/
        function categoryIdMap($insertArr,$home,$sync,$table)
        {
	        switch($table)
	        {
	        	case 'data_fields':
	        		$name = 'dcname';
	        		$newName = 'category';
	        		break;
	        	case 'data_enumvals':
	        		$name = 'dfname';
	        		$newName = 'field';
	        		break;	        			
	        		
	        }        	
		
        	$tmp = array();
        	$categoryIds = array();
        	if(count($insertArr)>0)
        	{
				$categoryIds = array_map(function($arr,$name){
					return $arr[$name];
				},$insertArr,array_fill(0,count($insertArr),$name));
				$categoryIds = array_unique($categoryIds);
				//without this the str_replace used below will not work properly in certain conditions. 
				usort($categoryIds,function ($a,$b){return strlen($b)-strlen($a);});
        	}
			foreach ($categoryIds as $oldCat)
			{
				switch($table)
	        	{
	        		case 'data_fields':
	        			$query = "SELECT id FROM $sync.data_categories WHERE name='$oldCat'";
	        			break;
	        		case 'data_enumvals':
	        			$query = "SELECT id FROM $sync.data_fields WHERE name='$oldCat'";
	        			break;	        			
	        			
	        	}				
				//get category names from the home db table
				//and get id details from the sync db for new category ids
				$result = mysql_query($query,$this->dbp);
				if(mysql_num_rows($result)>0)
				{
					$row = mysql_fetch_row($result);
					$map[] = $row[0];
				}
				else
				{
					$map[] = '';
				}
			}
			foreach($insertArr as $ky=>$arr)
			{
					$arr[$newName] = str_replace($categoryIds,$map,$arr[$name]);
					if($arr[$newName]=='')
					continue;
					unset($arr[$name]);
					$tmp[$ky] = $arr;
					
			}
			return $tmp;
        }
        
        /**
        * DBSync_mysql::getTriggerList()
        * @tutorial get triggers installed in database.
        * @access public
        * @return array trigger list
        * @author Jithu Thomas
        **/
        function getTriggerList()
        {
        	$query = "select * from information_schema.triggers where trigger_schema='".$this->database."' ";
        	$result = mysql_query($query,$this->dbp);
        	if(mysql_num_rows($result)>0)
        	{
        		while($row = mysql_fetch_assoc($result))
        		{
        			$triggers[0][] = $row['TRIGGER_NAME'];
        			$triggers[1][] = $row;
        		}
        		return $triggers;
        	}
        	else
        	{
        		return array(0=>array(),1=>array());
        	}
        }
        
        /**
         * DBSync_mysql::removeAllTriggers()
         * @tutorial generate sql to remove all triggers in a database.
         * @param array $triggers just trigger names' array
         * @access public
         * @return string
         * @author Jithu Thomas
         **/
        function removeAllTriggers($triggers)
        {
        	//$triggers = $this->getTriggerList();
        	foreach($triggers as $trigger)
        	{
        		$sql = "DROP TRIGGER `{$this->database}`.`{$trigger}`;";
        		echo $sql."<br/>";
        	}
        }      

        /**
        * DBSync_mysql::addAllTriggers()
        * @tutorial generate sql to add all triggers in a database.
        * @param array $homeDiffSync,$homeTriggerArr
        * @access public
        * @return string
        * @author Jithu Thomas
        **/
        function addAllTriggers($homeDiffSync,$homeTriggerArr)
        {
        	//$triggers = $this->getTriggerList();
        	foreach($homeDiffSync as $triggerToAdd)
        	{
        		foreach($homeTriggerArr[0] as $ky=>$triggerDef)
        		{
        			if($triggerToAdd==$homeTriggerArr[2][$ky])
        			{
        				$sql = str_replace("\n","<br/>",htmlspecialchars($triggerDef));
        				echo $sql."<br/>";
        			}
        		}
        	}
        }
        
        /**
        * DBSync_mysql::compareCommonTriggers()
        * @tutorial generate sql to compare & add/drop all triggers in a database.
        * @param array $homeInterSync,$homeTriggerArr
        * @access public
        * @return string
        * @author Jithu Thomas
        **/
        function compareCommonTriggers($homeInterSync,$homeTriggerArr,$syncTriggerArr)
        {
        	//pr($homeTriggerArr);pr($syncTriggerArr);die;
        	foreach($homeInterSync as $triggerToAdd)
        	{
        		foreach($homeTriggerArr[0] as $ky=>$triggerDef)
        		{
        			foreach($syncTriggerArr[1] as $syncKy=>$syncTriggerDef)
        			{
        				//pr($syncTriggerDef);
	        			if($triggerToAdd==$homeTriggerArr[2][$ky] && $triggerToAdd==$syncTriggerDef['TRIGGER_NAME'])
	        			{
	        				$syncTriggerDefStrip = 'DELIMITER$$CREATETRIGGER'.$syncTriggerDef['TRIGGER_NAME'].$syncTriggerDef['ACTION_TIMING'].$syncTriggerDef['EVENT_MANIPULATION'].'ON'.$syncTriggerDef['EVENT_OBJECT_TABLE'].'FOREACHROW'.$syncTriggerDef['ACTION_STATEMENT'].';$$DELIMITER;';
	        				$syncTriggerDefStrip = strtolower(preg_replace('!\s+!', '',trim($syncTriggerDefStrip)));
	        				$homeTriggerDefStrip = strtolower(preg_replace('!\s+!', '',trim($homeTriggerArr[0][$ky])));
	        				if($homeTriggerDefStrip != $syncTriggerDefStrip)
	        				{
	        					//add to array for remove sync def & add home def
	        					$triggersForUpdate[] = $triggerToAdd;
	        				}
	        				else
	        				{
	        					//match so we don't need further changes
	        					continue;
	        				}
	        			}
        			}
        		}
        		
        	}
			//decision ready now executing..
			if(count($triggersForUpdate)>0)
			{
				//remove all diff sync
				$this->removeAllTriggers($triggersForUpdate);
				//add all home diff sync
				$this->addAllTriggers($triggersForUpdate,$homeTriggerArr);
			}
        }
    //end class    
    }
?>