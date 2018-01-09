<?php
	class RedemptionConnector {
		private $mysqli = NULL;
		
		public static $TABLE_NAME = "redemptions";
		public static $COLUMN_ID = "id";
		public static $COLUMN_STUDENTID = "studentid";
		public static $COLUMN_EVENTID = "eventid";
		
        // The prepare statements exist to prevent SQL injection
		private $createStatement = NULL;
		private $selectStatement = NULL;
		private $selectTotalRedemptionsStatement = NULL;
		private $deleteStatement = NULL;
		
		function __construct($mysqli) {
            // This class requires utils/database.php
            // The input to the constructor is a handle to the sql session. Should be $conn
			if($mysqli->connect_errno > 0){
				die('Unable to connect to database [' . $mysqli->connect_error . ']');
			}
			
			$this->mysqli = $mysqli;
			
            // createStatement records students who have redeemed their reward for particular event
			$this->createStatement = $mysqli->prepare("INSERT INTO " . RedemptionConnector::$TABLE_NAME . "(`" . RedemptionConnector::$COLUMN_STUDENTID . "`, `" . RedemptionConnector::$COLUMN_EVENTID . "`) VALUES(?, ?)");
			
            // selectStatement searches for a particular student id for a particular eventid
            $this->selectStatement = $mysqli->prepare("SELECT * FROM `" . RedemptionConnector::$TABLE_NAME . "` WHERE `" . RedemptionConnector::$COLUMN_STUDENTID . "` = ? AND `" . RedemptionConnector::$COLUMN_EVENTID . "` = ?");
			
            // selectTotalRedemptionsStatement counts how many students have redeemed for an event
            $this->selectTotalRedemptionsStatement = $mysqli->prepare("SELECT COUNT(*) FROM `" . RedemptionConnector::$TABLE_NAME . "` WHERE `" . RedemptionConnector::$COLUMN_EVENTID . "` = ?");
			
            // deleteStatement removes one particular redemption record
            $this->deleteStatement = $mysqli->prepare("DELETE FROM " . RedemptionConnector::$TABLE_NAME . " WHERE `" . RedemptionConnector::$COLUMN_ID . "` = ?");
			
            // deleteEventStatement removes all the redemption records for a particular event
            $this->deleteEventStatement = $mysqli->prepare("DELETE FROM " . RedemptionConnector::$TABLE_NAME . " WHERE `" . RedemptionConnector::$COLUMN_EVENTID . "` = ?");
		}
		
		public function create($studentid, $eventid) {
            // creates a redemption record using student id and event id
			$this->createStatement->bind_param("si", $studentid, $eventid);
			return $this->createStatement->execute();
		}
		
		public function select($studentid, $eventid) {
            // searches for a particular student record for an event
			$this->selectStatement->bind_param("si", $studentid, $eventid);
			if(!$this->selectStatement->execute()) return false;

			$result = $this->selectStatement->get_result();
			if(!$result) return false;
			$redemption = $result->fetch_assoc();
			
			$this->selectStatement->free_result();
			
			return $redemption;
		}
		public function selectTotalRedemptions($eventid) {
            // returns the total number of redemptions for an event
			$this->selectTotalRedemptionsStatement->bind_param("i", $eventid);
			if(!$this->selectTotalRedemptionsStatement->execute()) return false;
			$this->selectTotalRedemptionsStatement->store_result(); // Sets the behavior to download all rows when fetch() is called and save them in cache
			$this->selectTotalRedemptionsStatement->bind_result($redemptions); // Set result to be saved in $redemptions
			$this->selectTotalRedemptionsStatement->fetch(); // Download rows
			
			$this->selectTotalRedemptionsStatement->free_result();
			
			return $redemptions;
		}

		public function delete($id) {
            // deletes a particular redemption record
			if($id == NULL) return false;
			
			$this->deleteStatement->bind_param("i", $id);
			if(!$this->deleteStatement->execute()) return false;
			
			return true;
		}
		
		public function deleteEvent($eventid) {
            // deletes all the redemption records for an event
			if($eventid == NULL) return false;
			
			$this->deleteEventStatement->bind_param("i", $eventid);
			if(!$this->deleteEventStatement->execute()) return false;
			
			return true;
		}
	}
?>
