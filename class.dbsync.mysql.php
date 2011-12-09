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
        	
        	//get foreign key associations if present for all fields in the table
        	$foreignKeyAssoc = null;
        	$foreignKeyAssoc = $this->listForeignKeyAssociations($this->database,$table);
         	$result = mysql_query("SHOW COLUMNS FROM {$table}", $this->dbp);
            while ($row = mysql_fetch_row($result)) {
	            $KeyName = null;
	            $Non_unique = null;
	            $Seq_in_index = null;
	            $Cardinality = null;
	            $Sub_part = null;
	            $indexFlag = false;
				foreach($indexArr as $index)
				{
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
						$foreign_key = $referenced_table_name = $referenced_column_name = $update_rule = $delete_rule = null;
					}
				}
				
				//get table detail engine status like engine collation etc.
				$query = "show table status where Name='$table'";
				$result = mysql_query($query, $this->dbp);
				while($rw = mysql_fetch_assoc($result))
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
					'Collation' => $collate
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
                $sql_f[] = "`{$fields[$i]['name']}` {$fields[$i]['type']} " . ($fields[$i]['null'] =='YES'?'' : 'NOT') . ' NULL' . (strlen($fields[$i]['default']) > 0 ? " default '{$fields[$i]['default']}'" : '') . ($fields[$i]['extra'] == 'auto_increment' ? ' auto_increment' : '');
            }

            $sql = "CREATE TABLE `{$name}` (" . implode(', ', $sql_f) . (count($primary_keys) > 0 ? ", PRIMARY KEY (`" . implode('`, `', $primary_keys) . "`)" : '') . (count($index_keys) > 0 ? ", INDEX (`" . implode('`, `', $index_keys) . "`)" : '') . (count($unique_keys) > 0 ? ", UNIQUE (`" . implode('`, `', $unique_keys) . "`)" : '') .  ($special_mul_key?$special_mul_key:'') . ') ENGINE='.$fields[0]['Engine'].' COLLATE='.$fields[0]['Collation'];
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
			$sql = "ALTER TABLE `{$table}` ADD `{$field['name']}` {$field['type']} " . ($field['null']=='YES' ? '' : 'NOT') . ' NULL' . (strlen($field['default']) > 0 ? " default '{$field['default']}'" : '') . ($field['extra'] == 'auto_increment' ? ' auto_increment' : '') . (!is_string($field_before) ? ' FIRST' : " AFTER `{$field_before}`") . ($field['key'] == 'PRI' ? ", ADD PRIMARY KEY (`{$field['name']}`)" : '');
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
        function ChangeTableField($table, $field, $new_field,$old_field=array(),$foreignKeyCheck=0) {
        	
        	switch($foreignKeyCheck)
        	{
	        	case 0:
	        		
	        	//special case detected for mul keys
	        	$special_mul_key = null;
	        	$indexKey = null;
	        	//pr($new_field);
	        	//pr($old_field);
	        	if($new_field['key']=='MUL' && $old_field['key']=='' && $new_field['Sub_part']!=$old_field['Sub_part'])
	        	{
	        		$special_mul_key = ', ADD KEY `'.$field.'` (`'.$field.'`('.$new_field['Sub_part'].'))';
	        	}
	        	if($new_field['key']=='MUL' && $old_field['key']=='' && $new_field['indexFlag']==1 && $old_field['indexFlag']=='')
	        	{
	        		$indexKey = ', ADD INDEX (`'.$field.'`) ';
	        	}
	        	if($new_field['key']=='UNI' && $new_field['type']=='blob' && $old_field['type']!='blob')
	        	{
	        		//need to remove old index before adding blob and also suggest blob index if any present
	        		if($old_field['indexFlag']==1)
	        		$sql =  "ALTER table `{$table}` DROP INDEX {$old_field['key_primary']}";
	        		echo $sql.';<br />';
	        		$special_uni_key = ', ADD UNIQUE `'.$field.'` (`'.$field.'`('.$new_field['Sub_part'].'))';
	        	}
	        	if($old_field['key']=='PRI' && $new_field['key']=='PRI')
	        	$no_primary_def_needed = 1;
				$sql = "ALTER TABLE `{$table}` CHANGE `{$field}` `{$new_field['name']}` {$new_field['type']} " . ($new_field['null']=='YES' ? '' : 'NOT') . ' NULL' . (strlen($new_field['default']) > 0 ? " default '{$new_field['default']}'" : '') . ($new_field['extra'] == 'auto_increment' ? ' auto_increment' : '') . ($new_field['key'] == 'PRI' && $no_primary_def_needed!=1  ? ", ADD PRIMARY KEY (`{$new_field['name']}`)" : '') . ($special_mul_key?$special_mul_key:''). ($indexKey?$indexKey:''). ($special_uni_key?$special_uni_key:'');
				echo($sql.';<br />');
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
						//if($constrain=='' || $referencedTableName=='' || $referencedColumnName=='' || $deleteRule=='' || $updateRule!='')return true;
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
					unset($arr[$name]);
					if($arr[$newName]=='')
					continue;
					$tmp[$ky] = $arr;
					
			}
			return $tmp;
        }         
    }
?>