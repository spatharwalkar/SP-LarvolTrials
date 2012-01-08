<?php
	/**
     * Class DBSync
     * Sync 2 or more databases. For now it only supports structure wich
     * is the most essencial part.
     *
     * @author Diogo Resende <me@diogoresende.net>
     * @licence GPL
     *
     * @method DBSync::SetHomeDatabase()
     * @method DBSync::AddSyncDatabase()
     * @method DBSync::Sync()
     **/
	class DBSync {
    	var $home = array();
        var $sync = array();
        private $syncDataTables = array();

        /**
         * DBSync::DBSync()
		 * Class constructor
         *
         * @param	optional	string	$database	Home Database Name
         * @param	optional	string	$type		Home Database Type
         * @param	optional	string	$host		Home Host (can be diferent from localhost :D)
         * @param	optional	string	$user		Home Database Username
         * @param	optional	string	$pass		Home Database Password
         *
         * @access	public
         * @return 	void
         **/
    	function DBSync($database = '', $type = 'mysql', $host = 'localhost', $user = 'root', $pass = '') {
        	if (strlen($database) > 0) {
            	$this->SetHomeDatabase($database, $type, $host, $user, $pass);
            }
        }

        /**
         * DBSync::SetHomeDatabase()
         * Set definitions for home database. This is the database that should be
         * correct and all others will be synched with this one.
         *
         * @param				string	$database	Home Database Name
         * @param	optional	string	$type		Home Database Type
         * @param	optional	string	$host		Home Host (can be diferent from localhost :D)
         * @param	optional	string	$user		Home Database Username
         * @param	optional	string	$pass		Home Database Password
         *
         * @access	public
         * @return 	void
         **/
        function SetHomeDatabase($database, $type = 'mysql', $host = 'localhost', $user = 'root', $pass = '') {
        	if (!class_exists("DBSync_{$type}")) {
            	include dirname(__FILE__) . "/class.dbsync.{$type}.php";
            }

            $class = "DBSync_{$type}";

            $this->home = new $class($host, $user, $pass, $database);
            if (!$this->home->ok) {
            	$this->RaiseError('Home Database Error: ' . $this->home->LastError());
            }
        }

        /**
         * DBSync::AddSyncDatabase()
         * Add a database to sync with the home database. You can add as many as
         * you want.
         *
         * @param				string	$database	Database Name
         * @param	optional	string	$type		Database Type
         * @param	optional	string	$host		Host
         * @param	optional	string	$user		Database Username
         * @param	optional	string	$pass		Database Password
         *
         * @access	public
         * @return 	void
         **/
        function AddSyncDatabase($database, $type = 'mysql', $host = 'localhost', $user = 'root', $pass = '') {
        	if (!class_exists("DBSync_{$type}")) {
            	include dirname(__FILE__) . "/class.dbsync.{$type}.php";
            }

            $class = "DBSync_{$type}";

            $sync = new $class($host, $user, $pass, $database);
            if (!$sync->ok) {
            	$this->RaiseError('Sync Database Error: ' . $this->home->LastError());
            }
            $this->sync[] = $sync;
        }

        /**
         * DBSync::Sync()
         * Sync defined databases with home database
         *
         * @access	public
         * @return 	boolean		Success
         **/
        function Sync() {
        	if (count($this->sync) == 0) {
            	$this->RaiseError('No Sync Databases defined. Use AddSyncDatabase() to add Sync Databases.');
            }
            for ($i = 0; $i < count($this->sync); $i++) {
            	$this->SyncDatabases($this->home, $this->sync[$i]);
            }

            return true;
        }

        /**
         * DBSync::SyncDatabases()
         * Sync one database with home database
         *
         * @access	private
         * @return 	boolean		Success
         **/
        function SyncDatabases(&$db_home, &$db_sync) {
        	$tables_home = $db_home->ListTables();
            $tables_sync = $db_sync->ListTables();

            for ($i = 0; $i < count($tables_home); $i++) {
            	if (!in_array($tables_home[$i], $tables_sync)) {
                	$fields = $db_home->ListTableFields($tables_home[$i]);
					if (!$db_sync->CreateTable($tables_home[$i], $fields)) {
                    	$this->RaiseError("Could not create table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                    }
                } else {
					$fields_home = $db_home->ListTableFields($tables_home[$i]);
					$fields_home_tmp = $fields_home;
                    $fields_sync = $db_sync->ListTableFields($tables_home[$i]);
                    $fieldnames_sync = $this->GetFieldNames($fields_sync);

                    $diferent_fields = 0;

                    for ($j = 0; $j < count($fields_home); $j++)
                    {
                    	if (!in_array($fields_home[$j]['name'], $fieldnames_sync))
                    	{
                            if (!isset($fields_home[$j - 1])) {
                            	$success = $db_sync->AddTableField($tables_home[$i], $fields_home[$j], 0);
                            } else {
	                        	$success = $db_sync->AddTableField($tables_home[$i], $fields_home[$j], $fields_home[$j - 1]['name']);
                            }
                            if (!$success) {
								$this->RaiseError("Could not add field <strong>{$fields_home[$j]['name']}</strong> to table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                            }
                            $diferent_fields++;
                        }
                        else
                        {
                        		$keys_home = $this->GetPrimaryKeys($fields_home_tmp);
	                        	$k = $this->GetFieldIndex($fields_sync, $fields_home[$j]['name']);
	                            if (
		                            	$fields_sync[$k]['type'] != $fields_home[$j]['type'] ||
		                                $fields_sync[$k]['null'] != $fields_home[$j]['null'] ||
		                                ($fields_sync[$k]['key'] != $fields_home[$j]['key'] && ($fields_home[$j]['key']!='UNI'&&$fields_sync[$k]['key'] !='UNI')) ||
		                                $fields_sync[$k]['default'] != $fields_home[$j]['default'] ||
		                                $fields_sync[$k]['extra'] != $fields_home[$j]['extra'] 
	                                )
	                            {
		                            if (!$db_sync->ChangeTableField($tables_home[$i], $fields_home[$j]['name'], $fields_home[$j],$fields_sync[$k],0,$keys_home)) {
			                            $this->RaiseError("Could not change field <strong>{$fields_home[$j]['name']}</strong> on table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
		                        }
	                                $diferent_fields++;
	                            }
	                            if (
		                                $fields_sync[$k]['foreign_key'] != $fields_home[$j]['foreign_key']||
		                                $fields_sync[$k]['referenced_table_name'] != $fields_home[$j]['referenced_table_name']||
		                                $fields_sync[$k]['referenced_column_name'] != $fields_home[$j]['referenced_column_name']||
		                                $fields_sync[$k]['update_rule'] != $fields_home[$j]['update_rule']||
		                                $fields_sync[$k]['delete_rule'] != $fields_home[$j]['delete_rule']
	                            
	                            )
	                            {
		                            if (!$db_sync->ChangeTableField($tables_home[$i], $fields_home[$j]['name'], $fields_home[$j],$fields_sync[$k],1,$keys_home)) {
			                            $this->RaiseError("Could not change field <strong>{$fields_home[$j]['name']}</strong> on table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
		                            }
		                            $diferent_fields++;
	                            }
	                            
                        }

		                unset($fieldnames_sync[array_shift(array_keys($fieldnames_sync, $fields_home[$j]['name']))]);
                    }

                    if ($diferent_fields > 0) {
                    	/**
                         * Arrange Primary Keys
                         **/
                        $keys_home = $this->GetPrimaryKeys($fields_home);
                        $keys_sync = $this->GetPrimaryKeys($fields_sync);
                        if ($this->DiferentKeys($keys_home, $keys_sync)) {
	                        if (count($keys_home) > 0) {
    	                    	$db_sync->SetTablePrimaryKeys($tables_home[$i], $keys_home);
        	                } else {
            	            	$db_sync->ClearTablePrimaryKeys($tables_home[$i]);
                	        }
                        }
                    }

        		    foreach ($fieldnames_sync as $field) {
        		    	foreach($fields_sync as $fld)
        		    	{
        		    		if($fld['name']==$field && $fld['constraint_name']!='')
        		    		{
        		    			$sql = "ALTER TABLE `{$tables_home[$i]}` DROP FOREIGN KEY `{$fld['constraint_name']}` ;";
        		    			echo $sql.'<br/>';
        		    		}
        		    	}
                    	if (!$db_sync->RemoveTableField($tables_home[$i], $field)) {
	                        $this->RaiseError("Could not change field <strong>{$field}</strong> on table <strong>{$tables_home[$i]}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                        }
		            }
                }

                unset($tables_sync[array_shift(array_keys($tables_sync, $tables_home[$i]))]);
            }

            foreach ($tables_sync as $table) {
            	if (!$db_sync->RemoveTable($table)) {
	                $this->RaiseError("Could not remove table <strong>{$table}</strong> on database <strong>{$db_sync->database}</strong> at {$db_sync->user}@{$db_sync->host}: " . $db_sync->LastError());
                }
            }

            return true;
        }

        /**
         * DBSync::GetFieldNames()
         * Return the names of the fields on the field list array
         *
         * @param	array	$fields		Field List
         *
         * @access	private
         * @return 	array		Field Names
         **/
        function GetFieldNames($fields) {
        	$names = array();
            for ($i = 0; $i < count($fields); $i++) {
            	$names[] = $fields[$i]['name'];
            }

            return $names;
        }

        /**
         * DBSync::GetFieldIndex()
         * Return the index (array key) of the field with the given name
         *
         * @param	array	$fields		Field List
         * @param	string	$name		Field Name
         *
         * @access	private
         * @return 	array		Field Index
         **/
        function GetFieldIndex($fields, $name) {
            for ($i = 0; $i < count($fields); $i++) {
            	if ($fields[$i]['name'] == $name) {
                	return $i;
                }
            }
            return false;
        }

        /**
         * DBSync::GetPrimaryKeys()
         * Returns a list of field names wich are primary keys
         *
         * @param	array	$fields		Field List
         *
         * @access	private
         * @return 	array		Primary Keys Field List
         **/
        function GetPrimaryKeys($fields) {
        	$keys = array();
            for ($i = 0; $i < count($fields); $i++) {
            	if ($fields[$i]['key'] == 'PRI') {
	            	$keys[] = $fields[$i]['name'];
                }
            }

            return $keys;
        }

        /**
         * DBSync::DiferentKeys()
         * Compares two primary keys field lists and checks if
         * they are diferent from each other.
         *
         * @param	array	$fields_home	Primary Keys Field List 1
         * @param	array	$fields_sync	Primary Keys Field List 2
         *
         * @access	private
         * @return 	boolean		"Are diferent?"
         **/
        function DiferentKeys($keys_home, $keys_sync) {
        	if (count($keys_home) != count($keys_sync)) {
            	return true;
            }

            for ($i = 0; $i < count($keys_home); $i++) {
            	if ($keys_home[$i] != $keys_sync[$i]) {
                	return true;
                }
            }

            return false;
        }

        /**
         * DBSync::RaiseError()
         * Displays error message and aborts execution of the program
         *
         * @param	string	$description	Error description
         *
         * @access	private
         * @return 	void
         **/
        function RaiseError($description) {
        	echo "<h3>Error</h3><hr />\n" .
                 $description;
			exit(1);
        }
        
       	/**
         * DBSync::syncDataTables()
         * @tutorial Set or get sync tables for data sync
         * @access	private
         * @param String $method get or set
         * @param Array $tables used in $method='set'
         * @return 	array/boolean 
         * @author Jithu Thomas
         **/     
        public function syncDataTables($method='get',$tables=array())
        {     
        	if($method=='set')
        	{
        		if(count($tables)>0 && is_array($tables))
        		{
        			$this->syncDataTables = $tables;
        			return true;
        		}
        		else
        		{
        			$this->RaiseError('Sync data tables not set correctly.');
        		}	
        	}
        	if($method=='get')
        	{
        	    if(count($this->syncDataTables)>0 && is_array($this->syncDataTables))
        		{
        			return $this->syncDataTables;
        		}
        		else
        		{
        			$this->RaiseError('Sync data tables not set correctly.');
        		}        		
        	}
        }   
        
        /**
         * DBSync::syncData()
         * @tutorial Sync databases data with the home database
         * Data bases home and sync are already assigned before running
         * the function
         * @access	private
         * @return 	boolean		Success
         * @author Jithu Thomas
         **/     
        public function syncData()
        {
        	//echo '<pre>';
        	if(!$this->home->ok && count($this->sync) && !$this->sync[0]->ok)
        	{
        		$this->RaiseError('Home or Sync Database not set properly.');
        	}
            for ($i = 0; $i < count($this->sync); $i++)
            {
            	$this->syncDataIndividual($this->home, $this->sync[$i]);
            }        	
        	
        }
           
                /**
         * DBSync::syncDataIndividual()
         * @tutorial Sync one database data with the home database
         * @access	private
         * @return 	boolean		Success
         * @author Jithu Thomas
         **/     
        private function syncDataIndividual(&$dbHome,&$dbSync)
        {        
        	foreach($this->syncDataTables('get') as $table)
        	{
	        	$homeData = $dbHome->getData($table);
	        	$syncData = $dbSync->getData($table);
	        	$insertArr = array();
	        	$deleteArr = array();
	        	switch($table)
	        	{
	        		case 'data_categories':
	        			$fields = array('name');
	        			break;
	        		case 'data_enumvals' : 
	        			$fields = array('dfname','value');
	        			break;
	        		case 'data_fields' :
	        			$fields = array('name','type','dcname'); 

	        			break;
	        		case 'user_permissions' :
	        			$fields = array('name','type','level'); 
	        			break;	        			
	        	}
                $homeDataUniqueArr = $this->createUniqueValArray($homeData, $fields);
        		$syncDataUniqueArr = $this->createUniqueValArray($syncData, $fields);
        		$insertArr = array_diff($homeDataUniqueArr,$syncDataUniqueArr);
        		$deleteArr = array_diff($syncDataUniqueArr,$homeDataUniqueArr);     
        		$home = $dbHome->database;
        		$sync = $dbSync->database;
        	    if(is_array($insertArr) && count($insertArr)>0)
        		{
        			$insertArr = $this->createUniqueValArray($insertArr, $fields,'explode');
        		}
        		
        	    if(is_array($deleteArr) && count($deleteArr)>0)
        		{
        			$deleteArr = $this->createUniqueValArray($deleteArr, $fields,'explode');
        		}        		
        			
				if($table=='data_fields')
				{
					$insertArr = $dbHome->categoryIdMap($insertArr,$home,$sync,$table);
					$deleteArr = $dbHome->categoryIdMap($deleteArr,$home,$sync,$table);
        			$fields = array('name','type','category'); 						
				}
				if($table == 'data_enumvals')
				{
					$insertArr = $dbHome->categoryIdMap($insertArr,$home,$sync,$table);
					$deleteArr = $dbHome->categoryIdMap($deleteArr,$home,$sync,$table);
        			$fields = array('value','field'); 						
				}
				if(is_array($insertArr) && count($insertArr)>0)
        		{					     
        			$dbHome->simpleInsert($table,$fields,$insertArr);	
        		}
        	    if(is_array($deleteArr) && count($deleteArr)>0)
        		{
        			$dbHome->simpleDelete($table,$deleteArr);
        		}
        	}
        }
        
        public function createUniqueValArray($dataArr,$fields=array(),$action='implode',$glue='_::_')
        {
        	if(!isset($dataArr) || !is_array($dataArr) || !count($dataArr)>0)
        	return array();
        	$returnArr = array();
        	foreach($dataArr as $arr)
        	{
        		$unique = null;
        		$uniqueArr = array();
        		switch($action)
        		{
        			case 'implode':
		        		foreach($fields as $field)
		        		{
		        			$uniqueArr[] = $arr[$field];
		        		}
		        		$returnArr[] = implode($glue,$uniqueArr);
	        			break;
        			case 'explode':
        				$tmpArr = explode($glue,$arr);
        				foreach($fields as $ky=>$field)
		        		{
		        			$uniqueArr[$field] = $tmpArr[$ky];
		        		} 
		        		$returnArr[] =  $uniqueArr; 				
        				break;
        		}
        	}
        	return $returnArr;
        }

        /**
        * DBSync::syncTriggers()
        * @tutorial Sync databases triggers with the home database
        * Databases home and sync are already assigned before running
        * the function
        * @access	public
        * @return 	boolean		Success
        * @author Jithu Thomas
        **/
        public function syncTriggers($triggers)
        {
        	//echo '<pre>';
        	if(!$this->home->ok && count($this->sync) && !$this->sync[0]->ok)
        	{
        		$this->RaiseError('Home or Sync Database not set properly.');
        	}
        	
        	for ($i = 0; $i < count($this->sync); $i++)
        	{
        		$this->syncTriggersIndividual($this->home, $this->sync[$i],$triggers);
        	}        	
        }    

        /**
        * DBSync::syncTriggersIndividual()
        * @tutorial Sync individual sync databases triggers with the home database
        * Databases home and sync are already assigned before running
        * the function
        * @access	private
        * @return 	boolean		Success
        * @author Jithu Thomas
        **/
        private function syncTriggersIndividual(&$dbHome,&$dbSync,$homeTriggerArr)
        {
        	//echo '<pre>';
        	if(!$this->home->ok && count($this->sync) && !$this->sync[0]->ok)
        	{
        		$this->RaiseError('Home or Sync Database not set properly.');
        	}
        	
			$syncTriggerArr = $dbSync->getTriggerList();
			//pr($homeTriggerArr);die;
			//pr($syncTriggerArr);die;
			$homeDiffSync = array_diff($homeTriggerArr[2], $syncTriggerArr[0]);
			$syncDiffHome = array_diff($syncTriggerArr[0],$homeTriggerArr[2]);
			$homeInterSync = array_intersect($homeTriggerArr[2], $syncTriggerArr[0]);
			
			//remove all sync diff home
			if(count($syncDiffHome)>0)
			$dbSync->removeAllTriggers($syncDiffHome);
			//add all home diff sync
			if(count($homeDiffSync)>0)
			$dbSync->addAllTriggers($homeDiffSync,$homeTriggerArr);
			
			//compare all home intersection sync using stripped uncased trigger definition and decide
			$dbSync->compareCommonTriggers($homeInterSync,$homeTriggerArr,$syncTriggerArr);
        }        
    }
?>