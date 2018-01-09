<?php
	class EventConnector {
		private $mysqli = NULL;
		
		public static $TABLE_NAME = "events";
		public static $COLUMN_ID = "id";
		public static $COLUMN_NAME = "name";
		public static $COLUMN_DATE = "date";
		public static $COLUMN_START = "start";
		public static $COLUMN_END = "end";
		public static $COLUMN_MIN = "min";
		public static $COLUMN_MAX = "max";
		public static $COLUMN_REWARD = "reward";
		public static $COLUMN_LOGOUTPASS = "logoutpass";
		public static $COLUMN_ADMINID = "adminid";
		
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
            
            // createStatement creates a new event
			$this->createStatement = $mysqli->prepare("INSERT INTO " . EventConnector::$TABLE_NAME . "(" . EventConnector::$COLUMN_NAME . ", " . EventConnector::$COLUMN_DATE . ", " . EventConnector::$COLUMN_START . ", " . EventConnector::$COLUMN_END . ", " . EventConnector::$COLUMN_MIN . ", " . EventConnector::$COLUMN_MAX . ", " . EventConnector::$COLUMN_REWARD . ", " . EventConnector::$COLUMN_ADMINID . ") VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            // selectStatement searches a particular event via event ID
			$this->selectStatement = $mysqli->prepare("SELECT * FROM " . EventConnector::$TABLE_NAME . " WHERE " . EventConnector::$COLUMN_ID . " = ?");
			
            // selectAllStatement grabs every single event in database
            $this->selectAllStatement = $mysqli->prepare("SELECT * FROM " . EventConnector::$TABLE_NAME);
			
            // deleteStatement deletes one event via event ID
            $this->deleteStatement = $mysqli->prepare("DELETE FROM " . EventConnector::$TABLE_NAME . " WHERE " . EventConnector::$COLUMN_ID . " = ?");
		}
		
		public function create($name, $date, $start, $end, $min, $max, $reward, $adminid) {
			// Inputs are Event Name, Event Date, Start Time, End Time, Minimum amount of votes required for reward,
            // Maximum amount of votes per student, Reward and Admin id of the person creating the event
            if($name == NULL || $date == NULL || $start == NULL || $end == NULL || $adminid == NULL){
				return false;
			}

			$this->createStatement->bind_param("ssssiisi", $name, $date, $start, $end, $min, $max, $reward, $adminid);
			$result = $this->createStatement->execute();
			$this->createStatement->close();

			if(!$result){
				die('Query failed ' . mysqli_error($this->mysqli));
			}			
			return $this->mysqli->insert_id;
		}
		
		public function select($id) {
            // Input is an int representing the event id
			if($id == NULL) return false;
			
			$this->selectStatement->bind_param("i", $id);
			if(!$this->selectStatement->execute()) return false;
			$result = $this->selectStatement->get_result();
			
			$event = $result->fetch_assoc();
			
			$this->selectStatement->free_result();
			
			return $event;
		}
		
		public function selectAll() {
            // Selects all the events in database
			if(!$this->selectAllStatement->execute()) return false;
			$result = $this->selectAllStatement->get_result();
			$resultArray = $result->fetch_all(MYSQLI_ASSOC);
			return $resultArray;
		}
		
		public function selectAllCustom(...$fields) {
            // Selects all events but only those $fields or columns that you want
			$query = "SELECT ";
			for($i = 0; $i < count($fields); $i++) {
				$query .= $fields[$i];
				if($i != count($fields) - 1) {
					$query .= ", ";
				}
			}
			
			$query .= " FROM " . EventConnector::$TABLE_NAME;
			
			$result = $this->mysqli->query($query);
			if(!$result) return false;
			
			return $result->fetch_all(MYSQLI_ASSOC);
		}
		
		public function update($date, $start, $end, $min, $max, $reward, $eventid) {
            // Updates an event based on the inputs
			$updateQuery = "UPDATE " . EventConnector::$TABLE_NAME . " SET ";
			$updateQuery .= "`" . EventConnector::$COLUMN_DATE . "` = \"" . $date . "\"";
			$updateQuery .= ", `" . EventConnector::$COLUMN_START . "` = \"" . $start . "\"";
			$updateQuery .= ", `" . EventConnector::$COLUMN_END . "` = \"" . $end . "\"";
			$updateQuery .= ", `" . EventConnector::$COLUMN_MIN . "` = \"" . $min . "\"";
			$updateQuery .= ", `" . EventConnector::$COLUMN_MAX . "` = \"" . $max . "\"";
			$updateQuery .= ", `" . EventConnector::$COLUMN_REWARD . "` = \"" . $reward . "\"";
			$updateQuery .= " WHERE `" . EventConnector::$COLUMN_ID . "` = \"" . $eventid . "\"";
			
			if(!$this->mysqli->query($updateQuery)) return false;
			return true;
		}
		
		public function updateLogoutPass($logoutpass, $eventid) {
            // Updates the logout password for the event
            // Inputs are the new logoutpass and event id
			$updateQuery = "UPDATE " . EventConnector::$TABLE_NAME . " SET `" . EventConnector::$COLUMN_LOGOUTPASS . "` = \"" . $logoutpass . "\"";
			$updateQuery .= " WHERE " . EventConnector::$COLUMN_ID . " = " . $eventid;
			
			if(!$this->mysqli->query($updateQuery)) return false;
			return true;
		}
		
		public function delete($eventid) {
            // Delete an event based on event id
			if($eventid == NULL) return false;
			
			$this->deleteStatement->bind_param("i", $eventid);
			if(!$this->deleteStatement->execute()) return false;
			
			return true;
		}
	}
?>
