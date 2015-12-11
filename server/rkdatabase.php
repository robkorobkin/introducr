<?php

	Class RK_mysql {

		function __construct($config){
			extract($config);

			// Create connection
			$this -> conn = new mysqli($servername, $username, $password, $database);
			$this -> debugMode = false;
			
			if ($this -> conn->connect_error) {
				die("Connection failed: " . $this -> conn -> connect_error);
			}
		}
		
		function close(){
			$this -> conn -> close();
		}
		
		function run_query($sql){
			if ($this -> conn -> query($sql) !== TRUE) {
				echo "Error: " . $sql . "<br>" . $this -> conn->error;
			}
		}
	
		function get_var($sql){
			$result = $this -> conn -> query($sql);
			 $row = $result -> fetch_array();
			 return $row[0];
		}

		function get_row($sql){
			$result = $this -> conn -> query($sql);
			return $result -> fetch_assoc();
		}
	
		function get_results($sql){
			$result = $this -> conn -> query($sql);
			while($response[] = $result -> fetch_assoc());
			unset($response[count($response) -1]);
			return $response;
		}
	
		function update($obj, $table, $where){
			$input = (array) $obj;
		
			// generate sql
			$sql = 'UPDATE ' . $table;
			foreach($input as $k => $v){
				$params[] = $k . '="' . addSlashes($v) . '"';
			}
			$sql .= ' SET ' . implode(',', $params);
			
			
			foreach($where as $k => $v){
				$whereStrs[] = $k . '=' . '"' . addSlashes($v) . '"';
			}
			$sql .= ' WHERE ' . implode(' AND ', $whereStrs);
	
			// run query
			if($this -> debugMode) echo $sql;
			$this -> run_query($sql);
			
			// return updated object
			$sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $whereStrs);
			if($this -> debugMode) echo "\n\n\n $sql \n\n\n";
			return $this -> get_row($sql);
		}
	
		function insert($obj, $table){
			$input = (array) $obj;
			foreach($input as $k => $v){
				$kstrs[] = $k;
				$vstrs[] = '"' . addSlashes($v) . '"';
			}
			$sql = 	'INSERT INTO ' . $table . 
					' (' . implode(',', $kstrs) . ') VALUES (' . implode(',', $vstrs) . ')';
			
			// run query
			$this -> run_query($sql);		
			
			// return input id
			return mysqli_insert_id($this -> conn);

		}

	}