<?php

	Class RK_mysql {

		function __construct($config){
			extract($config);
			
			// Create connection
			$this -> conn = new mysqli($servername, $username, $password, $database);
			$this -> debugMode = false;
			$this -> mode = "shell";
			
			
			if ($this -> conn->connect_error) {
				die("Connection failed: " . $this -> conn -> connect_error);
			}
		}

		function _linebreak(){
			return ($this -> mode == "shell") ? "\n\n" : "<br /><br />\n\n";
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
			 return $row ? $row[0] : false;
		}

		function get_row($sql){
			$result = $this -> conn -> query($sql);
			return ($result) ? $result -> fetch_assoc() : array();
		}
		
		function get_rowFromObj($where, $table){
			foreach($where as $k => $v) $whereStrs[] = $k . '=' . $v;
			$sql = 'select * from ' . $table . ' where ' . implode(' AND ', $whereStrs);
			if($this -> debugMode) echo $sql . $this -> _linebreak();
			return $this -> get_row($sql);
		}
	
		function get_results($sql){
			$result = $this -> conn -> query($sql);
			$response = array();
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
			if($this -> debugMode) echo $sql . $this -> _linebreak();
			$this -> run_query($sql);
			
			// return updated object
			$sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $whereStrs);
			if($this -> debugMode) echo $sql . $this -> _linebreak();
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
			
			if($this -> debugMode) echo $sql . $this -> _linebreak();


			// run query
			$this -> run_query($sql);		
			
			// return input id
			return mysqli_insert_id($this -> conn);

		}


		function updateOrCreate($update, $table, $where){
			
			// look for it?
			$row = $this -> get_rowFromObj($where, $table);
			
			// if it's not there, add it!
			if(count($row) == 0){
				$newObject = $update;
				foreach($where as $k => $v) $newObject[$k] = $v;
				$this -> insert($newObject, $table);
			}
		
			// otherwise, update it
			else {
				$this -> update($update, $table, $where);
			}
			
		}


		function  getOrCreate($obj, $table){
			
			// look for it?
			$row = $this -> get_rowFromObj($obj, $table);


			
			// if it's there, return it!
			if(count($row) != 0) return $row;
			
			// otherwise, create it!
			$this -> insert($obj, $table);
			return $obj;

		}
	}