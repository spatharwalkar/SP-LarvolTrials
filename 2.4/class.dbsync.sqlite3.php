<?php
	/**
     * Class DBSync_sqlite3
     * Used by class DBSync to sync a SQLite3 database
     *
     * @author Diogo Resende <me@diogoresende.net>
     * @licence GPL
     **/
	class DBSync_sqlite3 {
    	var $dbp;
        var $database;
        var $host;
        var $user;
        var $pass;
        var $ok = false;

        /**
         * DBSync_sqlite3::DBSync_mysql()
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
    	function DBSync_sqlite3($host, $user, $pass, $database) {
        	$this->database = $database;
            $this->host = $host;
            $this->user = $user;
            $this->pass = $pass;
			$this->dbp = new SQLite3(':memory:');
			$setupscript = file_get_contents($database);
			$setupscript = preg_replace('/int\(..\) (unsigned |)NOT NULL AUTO_INCREMENT/', 'INTEGER PRIMARY KEY', $setupscript);
			$setupscript = str_replace('unsigned ', '', $setupscript);
			$setupscript = str_replace('AUTO_INCREMENT', '', $setupscript);
			$setupscript = preg_replace('/enum\(.*\)/', 'enum', $setupscript);
			$setupscript = preg_replace('/(CHARACTER SET|DEFAULT CHARSET).utf8/', '', $setupscript);
			$setupscript = preg_replace('/COLLATE.utf8_unicode_ci/', '', $setupscript);
			$setupscript = str_replace('ENGINE=InnoDB ', '', $setupscript);
			$setupscript = preg_replace('/,(\n|\r\n)  PRIMARY KEY \(`.*`\)/', '', $setupscript);
			$setupscript = preg_replace('/UNIQUE KEY `.*` \(/', 'UNIQUE (', $setupscript);
			$setupscript = preg_replace('/,(\n|\r\n)  KEY `.*` \(.*\)/', '', $setupscript);
			$setupscript = preg_replace('/COMMENT \'.*\'/', '', $setupscript);
			$setupscript = str_replace(' ON UPDATE CURRENT_TIMESTAMP', '', $setupscript);
			$setupscript = substr($setupscript,0, strpos($setupscript,'ALTER TABLE'));
			
			//echo($setupscript);
			$this->dbp->exec($setupscript);
			$this->ok = !(bool)($this->dbp->lastErrorCode());
        }

        /**
         * DBSync_sqlite3::ListTables()
		 * List tables on current database
         *
         * @access	public
         * @return 	array	Table list
         **/
        function ListTables()
		{
        	$tables = array();
        	$result = $this->dbp->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            while($row = $result->fetchArray(SQLITE3_ASSOC)) $tables[] = $row['name'];
            return $tables;
        }

        /**
         * DBSync_sqlite3::ListTableFields()
		 * List table fields from a table on current database
         *
         * @param	string	$table	Table Name
         *
         * @access	public
         * @return 	array	Field List
         **/
        function ListTableFields($table)
		{
        	$fields = array();
        	/*$result = $this->dbp->query('SELECT column_name,data_type,is_nullable,column_key,column_default,extra '
										. 'FROM information_schema.columns where table_name="' . $table . '"');*/
			$result = $this->dbp->query('PRAGMA table_info(' . $table . ')');
            while ($row = $result->fetchArray(SQLITE3_ASSOC))
			{
				/*$fields[] = array(
                	'name'	  => $row['column_name'],
                    'type'    => $row['data_type'],
                    'null'    => $row['is_nullable'],
                    'key'     => $row['column_key'],
                    'default' => $row['column_default'],
                    'extra'   => $row['extra']
                );*/
				$fields[] = array(
                	'name'	  => $row['name'],
                    'type'    => $row['type'],
                    'null'    => !((bool)$row['notnull']),
                    'key'     => $row['pk'] ? 'UNI' : '',
                    'default' => $row['dflt_value'],
                    'extra'   => ''
                );
            }
            return $fields;
        }

        /**
         * DBSync_sqlite3::CreateTable()
		 * Create a table on current database
         *
         * @param	string	$name		Table Name
         * @param	array	$fields		Field List
         *
         * @access	public
         * @return 	boolean success
         **/
        function CreateTable($name, $fields)
		{

        	$primary_keys = array();
            $sql_f = array();

            for ($i = 0; $i < count($fields); $i++) {
            	if ($fields[$i]['key'] == 'PRI') {
                	$primary_keys[] = $fields[$i]['name'];
                }
                $sql_f[] = "`{$fields[$i]['name']}` {$fields[$i]['type']} " . ($fields[$i]['null'] ? '' : 'NOT') . ' NULL' . (strlen($fields[$i]['default']) > 0 ? " default '{$fields[$i]['default']}'" : '') . ($fields[$i]['extra'] == 'auto_increment' ? ' auto_increment' : '');
            }

            $sql = "CREATE TABLE `{$name}` (" . implode(', ', $sql_f) . (count($primary_keys) > 0 ? ", PRIMARY KEY (`" . implode('`, `', $primary_keys) . "`)" : '') . ')';
            echo($sql.'<br />'); return true;
        }

        /**
         * DBSync_sqlite3::RemoveTable()
		 * Remove a table from current database
         *
         * @param	string	$name		Table Name
         *
         * @access	public
         * @return 	boolean success
         **/
        function RemoveTable($table)
		{
			$sql = "DROP TABLE `{$table}`";
            echo($sql.'<br />'); return true;
        }

        /**
         * DBSync_sqlite3::AddTableField()
		 * Add a field to a table on current database
         *
         * @param				string	$table			Table Name
         * @param				array	$field			Field Information
         * @param	optional	string	$field_before	Field before the field to be added
         *												(if $field_before = 0 this field will
         *												be added at the begining of the table)
         *
         * @access	public
         * @return 	boolean success
         **/
        function AddTableField($table, $field, $field_before = 0)
		{
			$sql = "ALTER TABLE `{$table}` ADD `{$field['name']}` {$field['type']} " . ($field['null'] ? '' : 'NOT') . ' NULL' . (strlen($field['default']) > 0 ? " default '{$field['default']}'" : '') . ($field['extra'] == 'auto_increment' ? ' auto_increment' : '') . (!is_string($field_before) ? ' FIRST' : " AFTER `{$field_before}`") . ($field['key'] == 'PRI' ? ", ADD PRIMARY KEY (`{$field['name']}`)" : '');
            echo($sql.'<br />'); return true;
        }

        /**
         * DBSync_sqlite3::ChangeTableField()
		 * Change a field on a table on current database
         *
         * @param	string	$table		Table Name
         * @param	string	$field		Field Name
         * @param	array	$new_field	New Field Information
         *
         * @access	public
         * @return 	boolean success
         **/
        function ChangeTableField($table, $field, $new_field)
		{
			$sql = "ALTER TABLE `{$table}` CHANGE `{$field}` `{$new_field['name']}` {$new_field['type']} " . ($new_field['null'] ? '' : 'NOT') . ' NULL' . (strlen($new_field['default']) > 0 ? " default '{$new_field['default']}'" : '') . ($field['extra'] == 'auto_increment' ? ' auto_increment' : '') . ($field['key'] == 'PRI' ? ", ADD PRIMARY KEY (`{$field['name']}`)" : '');
            echo($sql.'<br />'); return true;
        }

        /**
         * DBSync_sqlite3::RemoveTableField()
		 * Remove a field from a table on current database
         *
         * @param	string	$table		Table Name
         * @param	string	$field		Field Name
         *
         * @access	public
         * @return 	boolean success
         **/
        function RemoveTableField($table, $field)
		{
			$sql = "ALTER TABLE `{$table}` DROP `{$field}`";
            echo($sql.'<br />'); return true;
        }

        /**
         * DBSync_sqlite3::ClearTablePrimaryKeys()
		 * Clear primary keys on a table on current database
         *
         * @param	string	$table		Table Name
         *
         * @access	public
         * @return 	boolean success
         **/
        function ClearTablePrimaryKeys($table) {
        	$sql = "ALTER TABLE `{$table}` DROP PRIMARY KEY";
            echo($sql.'<br />'); return true;
        }

        /**
         * DBSync_sqlite3::SetTablePrimaryKeys()
		 * Clears primary keys and sets new ones on a table on current database
         *
         * @param	string	$table		Table Name
         * @param	array	$keys		Primary Keys List
         *
         * @access	public
         * @return 	boolean success
         **/
        function SetTablePrimaryKeys($table, $keys) {
        	$sql = "ALTER TABLE `{$table}` DROP PRIMARY KEY, ADD PRIMARY KEY (`" . implode('`, `', $keys) . "`)";
            echo($sql.'<br />'); return true;
        }

        /**
         * DBSync_sqlite3::LastError()
		 * Returns last error message from MySQL server
         *
         * @access	public
         * @return 	string	Error Message
         **/
        function LastError() {
        	return $this->dbp->lastErrorMsg();
        }
    }
?>