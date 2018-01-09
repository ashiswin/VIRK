<?php
	class GroupConnector {
		private $mysqli = NULL;
		
		public static $TABLE_NAME = "groups";
		public static $COLUMN_NAME = "name";
		public static $COLUMN_MEMBERS = "members";
		public static $COLUMN_ID = "groupid";
		public static $COLUMN_EVENTID = "eventid";
		public static $COLUMN_GROUP_PW = "password";
		public static $COLUMN_VOTES = "votes";
		
        // The prepare statements exist to prevent SQL injection
		private $createStatement = NULL;
		private $selectGroupStatement = NULL;
        private $selectEventStatement = NULL;
		private $selectAllStatement = NULL;
        private $checkUniquePWStatement = NULL;
		private $deleteGroupStatement = NULL;
       	private $deleteEventStatement = NULL;
		
		function __construct($mysqli) {
            // This class requires utils/database.php
            // The input to the constructor is a handle to the sql session. Should be $conn
			if($mysqli->connect_errno > 0){
				die('Unable to connect to database [' . $mysqli->connect_error . ']');
			}
			
			$this->mysqli = $mysqli;
            
            // createStatement creates a new group
			$this->createStatement = $mysqli->prepare("INSERT INTO " . GroupConnector::$TABLE_NAME . "(" . GroupConnector::$COLUMN_NAME . ", " . GroupConnector::$COLUMN_MEMBERS . ", " . GroupConnector::$COLUMN_EVENTID . ", " . GroupConnector::$COLUMN_GROUP_PW . ") VALUES (?, ?, ?, ?)");
            
            // selectGroupStatement selects a group based on event id and group id
			$this->selectGroupStatement = $mysqli->prepare("SELECT * FROM " . GroupConnector::$TABLE_NAME . " WHERE " . GroupConnector::$COLUMN_EVENTID . "=? AND " . GroupConnector::$COLUMN_ID . "=?");
			
            // selectGroupsByEventStatement selects all the groups from the same event id
            $this->selectGroupsByEventStatement = $mysqli->prepare("SELECT * FROM " . GroupConnector::$TABLE_NAME . " WHERE " . GroupConnector::$COLUMN_EVENTID . "=?");
	
            // selectAllStatement grabs all the groups in database
			$this->selectAllStatement = $mysqli->prepare("SELECT * FROM " . GroupConnector::$TABLE_NAME);

			// checkUniquePWStatement searches the database for another group with the same password
            $this->checkUniquePWStatement = $mysqli->prepare("SELECT 1 FROM " . GroupConnector::$TABLE_NAME . " WHERE " . GroupConnector::$COLUMN_GROUP_PW . "=? LIMIT 1");
	
            // deleteGroupStatement deletes a group based on eventid and groupid
			$this->deleteGroupStatement = $mysqli->prepare("DELETE FROM " . GroupConnector::$TABLE_NAME . " WHERE " . GroupConnector::$COLUMN_EVENTID . "=? AND " . GroupConnector::$COLUMN_ID . "=?");

			// deleteEventStatement deletes all the groups with a particular event id
            $this->deleteEventStatement = $mysqli->prepare("DELETE FROM " . GroupConnector::$TABLE_NAME . " WHERE " . GroupConnector::$COLUMN_EVENTID . "=?");
		}
		
		public function createGroup($name, $members, $password, $eventid) {
            // Creates a new group
            // $members are in the format Student1 id | Student1 name | Student2 id | Student2 name, etc
			$this->createStatement->bind_param("ssis", $name, $members, $eventid, $password);
			
			if(!$this->createStatement->execute()){
				die('Query failed: ' . $this->createStatement->error);
			}			

			return $this->mysqli->insert_id;
		}
		
		public function selectGroup($eventid, $groupid) {
            // Select group with $eventid and $groupid
			if($groupid == NULL) return false;
			
			$this->selectGroupStatement->bind_param("ii", $eventid, $groupid);
			if(!$this->selectGroupStatement->execute()) return false;
			$result = $this->selectGroupStatement->get_result();
			
			$group = $result->fetch_assoc();
			
			$this->selectGroupStatement->free_result();
			
			return $group;
		}
		
		public function selectGroupsByEvent($eventid) {
            // Select all the groups with $eventid
			if($eventid == NULL) return false;
			
			$this->selectGroupsByEventStatement->bind_param("i", $eventid);
			if(!$this->selectGroupsByEventStatement->execute()) return false;
			$result = $this->selectGroupsByEventStatement->get_result();
			$resultArray = $result->fetch_all(MYSQLI_ASSOC);
			return $resultArray;
		}
		
		public function selectAll() {
            // Select every single group in database
			if(!$this->selectAllStatement->execute()) return false;
			$result = $this->selectAllStatement->get_result();
			$resultArray = $result->fetch_all(MYSQLI_ASSOC);
			return $resultArray;
		}
		
		public function update($name, $members, $event_name, $eventid, $groupid) {
            // Updates whatever for a group
			$updateQuery = "UPDATE " . GroupConnector::$TABLE_NAME . " SET ";
			$first = false;
			
            if($name != NULL) {
				if($first) $updateQuery .= "AND ";
				$updateQuery .= "`" . GroupConnector::$COLUMN_NAME . "` = \"" . $name . "\" ";
				$first = true;
			}
			if($members != NULL) {
				if($first) $updateQuery .= "AND ";
				$updateQuery .= "`" . GroupConnector::$COLUMN_MEMBERS . "` = \"" . $members . "\" ";
				$first = true;
			}
			if($event_name != NULL) {
				if($first) $updateQuery .= "AND ";
				$updateQuery .= "`" . GroupConnector::$COLUMN_EVENT_NAME . "` = \"" . $event_name . "\" ";
				$first = true;
			}
			
			$updateQuery .= " WHERE " . GroupConnector::$COLUMN_EVENTID . " = " . $eventid . " AND " . GroupConnector::$COLUMN_GROUPID . " = " . $groupid;
			
			if(!$this->mysqli->query($updateQuery)) return false;
			return true;
		}
		
		public function updateVotes($voteString, $eventid, $groupid) {
            // Updates the votes/ratings a group with $groupid at event $eventid has received
            // $voteString is in the format studentid|votenumber|studentid|votenumber|studentid|votenumber ....
            // i.e. when you parse $voteString, you can get both the total number of votes and who voted
			$updateQuery = "UPDATE " . GroupConnector::$TABLE_NAME . " SET `" . GroupConnector::$COLUMN_VOTES . "` = \"" . $voteString . "\" ";
            $updateQuery .= " WHERE " . GroupConnector::$COLUMN_EVENTID . " = " . $eventid . " AND " . GroupConnector::$COLUMN_ID . " = " . $groupid;
			
			if(!$this->mysqli->query($updateQuery)) return false;
			return true;
		}
		
		public function checkUniquePW($password){
            // Checks if $password is unique
			if($password == NULL) return false;

			$this->checkUniquePWStatement->bind_param("s", $password);
			$this->checkUniquePWStatement->execute();
			$result = $this->checkUniquePWStatement->get_result();

			if(!isset($result)){
				die('Query failed ' . mysqli_error($this->mysqli));
			}
			else if(mysqli_num_rows($result) == 0) { // if no other group has this password
				return true;
			}
			else {
				return false;
			}
		}
		
		public function deleteGroup($eventid, $groupid) {
            // Deletes group with $eventid and $groupid
			if($groupid == NULL || $eventid == NULL) return false;
			
			$this->deleteGroupStatement->bind_param("ii", $eventid, $groupid);
			if(!$this->deleteGroupStatement->execute()) return false;
			
			return true;
		}
		
		public function deleteEvent($eventid) {
            // Deletes all the groups with $eventid
			if($eventid == NULL) return false;
			
			$this->deleteEventStatement->bind_param("i", $eventid);
			if(!$this->deleteEventStatement->execute()) return false;
			
			return true;
		}
	}
?>
