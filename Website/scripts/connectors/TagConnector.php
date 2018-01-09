<?php
	class TagConnector {
		private $mysqli = NULL;
		
		public static $TABLE_NAME = "tags";
		public static $COLUMN_TAGID = "tagid";
		public static $COLUMN_STUDENTID = "studentid";
		
        // The prepare statements exist to prevent SQL injection
		private $createStatement = NULL;
		private $selectStatement = NULL;
		private $selectAllStatement = NULL;
		private $deleteStatement = NULL;
		
		function __construct($mysqli) {
            // This class requires utils/database.php
            // The input to the constructor is a handle to the sql session. Should be $conn
			if($mysqli->connect_errno > 0){
				die('Unable to connect to database [' . $mysqli->connect_error . ']');
			}
			
			$this->mysqli = $mysqli;
			
            // createStatement associates a NFC id to a student id
			$this->createStatement = $mysqli->prepare("INSERT INTO " . TagConnector::$TABLE_NAME . "(`" . TagConnector::$COLUMN_TAGID . "`, `" . TagConnector::$COLUMN_STUDENTID . "`) VALUES(?, ?)");
            
            // selectStatement searches for a particular NFC id
			$this->selectStatement = $mysqli->prepare("SELECT * FROM `" . TagConnector::$TABLE_NAME . "` WHERE `" . TagConnector::$COLUMN_TAGID . "` = ?");
			
            // selectAllStatement grabs all rows and sort them by student id
            $this->selectAllStatement = $mysqli->prepare("SELECT * FROM `" . TagConnector::$TABLE_NAME . "` ORDER BY `" . TagConnector::$COLUMN_STUDENTID . "`");
            
            // deleteStatement deletes an entry with a particular NFC id
			$this->deleteStatement = $mysqli->prepare("DELETE FROM " . TagConnector::$TABLE_NAME . " WHERE `" . TagConnector::$COLUMN_TAGID . "` = ?");
		}
		
		public function create($tagid, $studentid) {
            // Associates a NFC id with a student id
			$this->createStatement->bind_param("ss", $tagid, $studentid);
			return $this->createStatement->execute();
		}
		
		public function select($tagid) {
            // Searches for an entry based on NFC id
			$this->selectStatement->bind_param("s", $tagid);
			if(!$this->selectStatement->execute()) return false;

			$result = $this->selectStatement->get_result();
			if(!$result) return false;
			$tag = $result->fetch_assoc();
			
			$this->selectStatement->free_result();
			
			return $tag;
		}
		
		public function selectAll() {
            // Grabs all entries
			if(!$this->selectAllStatement->execute()) return false;
			
			$result = $this->selectAllStatement->get_result();
			if(!$result) return false;
			$tags = $result->fetch_all(MYSQLI_ASSOC);
			
			$this->selectAllStatement->free_result();
			
			return $tags;
		}
		
		public function delete($tagid) {
			// Deletes an entry with $tagid
			$this->deleteStatement->bind_param("s", $tagid);
			if(!$this->deleteStatement->execute()) return false;
			
			return true;
		}
	}
?>
