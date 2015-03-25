<?php

// Only edit this if you know what your doing!



class Model {

	public function __construct() {
		//$db = new SQLite30('database.db');
		
		$dsn = 'mysql:host=localhost;port=3306;dbname=mysql';
		$username = 'jakemor';
		$password = 'loplop34';
		
		$options = array(
		    1002 => 'SET NAMES utf8',
		); 

		$db = new PDO($dsn, $username, $password, $options);

		$table_name = get_class($this);
		$cols_array = array_keys(get_class_vars(get_class($this)));
		$cols = implode(",", $cols_array);
		$cols = "id INTEGER PRIMARY KEY, created_at, last_updated, date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP," . $cols; 
		$db->exec(
			'CREATE TABLE IF NOT EXISTS ' . $table_name . '(' . $cols . ');'
		);
	}

	public function connectToDB() {
		//$db = new SQLite3('database.db');

		$dsn = 'mysql:host=localhost;port=3306;dbname=mysql';
		$username = 'jakemor';
		$password = 'loplop34';
		
		$options = array(
		    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
		    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		); 

		$db = new PDO($dsn, $username, $password, $options);

		return $db; 
	}

	// create()
	public function save() {
		$db = $this->connectToDB();

		$table_name = get_class($this);
		
		if (!property_exists($this, "id")) {
			$cols_array = array(); 
			$vals_array = array(); 
			
			foreach ($this as $key => $value) {
				array_push($cols_array, $key); 
				array_push($vals_array, $value); 
			}

			array_push($cols_array, "last_updated");
			array_push($vals_array, time()); 

			array_push($cols_array, "created_at");
			array_push($vals_array, time()); 

			$cols = 'id INTEGER PRIMARY KEY AUTO_INCREMENT, ' . implode(' VARCHAR(100) NOT NULL , ', $cols_array) . ' VARCHAR(100) NOT NULL';
			$vals = "'" . implode("', '", $vals_array) . "'";

			$db->exec(
				'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (' . $cols . ');'
			);

			$cols = implode(' , ', $cols_array);

			$db->exec(
				"INSERT INTO " . $table_name . " (" . $cols . ") VALUES (" . $vals . ")"
	  		);
 

		} else {
			// to do
			$rows = 0; 
			$updated = 0; 
			foreach ($this as $key => $value) {
				if ($db->exec(
					'UPDATE `' . $table_name . '` SET `' . $key . '` = "' . $value . '" WHERE `id`=' . $this->id . ';'
				)) {
					$updated++; 
				}
				$rows++; 
			}

			$key = "last_updated"; 
			$value = time(); 

			$db->exec(
					'UPDATE `' . $table_name . '` SET `' . $key . '` = "' . $value . '" WHERE `id`=' . $this->id . ';'
				);

			return $rows==$updated; 
		}
	}

	// get(key, value)
	public function get($key, $value) { ///
		$db = $this->connectToDB();
		$table_name = get_class($this);

		$query = "SELECT * FROM `" . $table_name . "` WHERE `" . $key . "` = '" . $value . "'";

		$result = $db->query($query);

		$found = FALSE; 
		
		while ($row = $result->fetchAll(PDO::FETCH_ASSOC)) {

			$found = TRUE; 
		    foreach ($row[0] as $key => $value) {
		    	$this->$key = $value; 
		    }
		}

		return $found; 
	}

	// returns an array
	public function search($key, $value) {
		$db = $this->connectToDB();
		$table_name = get_class($this);

		$query = "SELECT * FROM \"" . $table_name . "\" WHERE \"" . $key . "\" = '" . $value . "'";

		$result = $db->query($query);
		$return = array();

		while ($row = $result->fetchArray(PDO::FETCH_ASSOC)) {
		    $db_row = array(); 
		    foreach ($row as $key => $value) {
		    	$db_row[$key] = $value; 
		    }
		    array_push($return, $db_row); 
		}

		return $return; 
	}

	// returns an array
	public function getAll() {
		$db = $this->connectToDB();
		$table_name = get_class($this);

		$query = "SELECT * FROM \"" . $table_name . "\"";

		$result = $db->query($query);
		$return = array();

		while ($row = $result->fetchArray(PDO::FETCH_ASSOC)) {
		    $db_row = array(); 
		    foreach ($row as $key => $value) {
		    	$db_row[$key] = $value; 
		    }
		    array_push($return, $db_row); 
		}

		return $return; 
	}

	// returns an array
	public function match($array) {
		$db = $this->connectToDB();
		$table_name = get_class($this);

		$query_array = array();  

		foreach ($array as $key => $value) {
			array_push($query_array,  "\"" . $key . "\" = '" . $value . "'"); 
		}

		$query = "SELECT * FROM \"" . $table_name . "\" WHERE " . implode(" AND ", $query_array);

		$result = $db->query($query);
		$return = array();

		while ($row = $result->fetchArray(PDO::FETCH_ASSOC)) {
		    $db_row = array(); 
		    foreach ($row as $key => $value) {
		    	$db_row[$key] = $value; 
		    }
		    array_push($return, $db_row); 
		}

		return $return; 
	}


	// returns an array
	public function getMultiple($key, $values) {
		$db = $this->connectToDB();
		$table_name = get_class($this);

		$query_array = array();  

		foreach ($values as $value) {
			array_push($query_array,  "\"" . $key . "\" = '" . $value . "'"); 
		}

		$query = "SELECT * FROM \"" . $table_name . "\" WHERE " . implode(" OR ", $query_array);

		$result = $db->query($query);
		$return = array();

		while ($row = $result->fetchArray(PDO::FETCH_ASSOC)) {
		    $db_row = array(); 
		    foreach ($row as $key => $value) {
		    	$db_row[$key] = $value; 
		    }
		    array_push($return, $db_row); 
		}

		return $return; 
	}

	// delete()
	public function delete() {
		if (property_exists($this, "id")) {
			$db = $this->connectToDB();
			$table_name = get_class($this);
			$query = 'DELETE FROM "' . $table_name . '" WHERE ("id" = ' . $this->id . ');'; 
			return $db->exec($query); 
		} else {
			return FALSE; 
		}
	}
}

?>