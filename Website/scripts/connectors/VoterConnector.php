<?php
	class VoterConnector {
		private $mysqli = NULL;
		
		public static $TABLE_NAME = "voters";
		public static $COLUMN_ID = "id";
		public static $COLUMN_STUDENTID = "studentid";
		public static $COLUMN_EVENTID = "eventid";
		public static $COLUMN_GROUPID = "groupid";
		
        // The prepare statements exist to prevent SQL injection
		private $createStatement = NULL;
		private $selectNumberVotesStatement = NULL;
		private $deleteStatement = NULL;
		
		function __construct($mysqli) {
            // This class requires utils/database.php
            // The input to the constructor is a handle to the sql session. Should be $conn
			if($mysqli->connect_errno > 0){
				die('Unable to connect to database [' . $mysqli->connect_error . ']');
			}
			
			$this->mysqli = $mysqli;
			 
            // createStatement creates a record of which group a voter voted for at which event
			$this->createStatement = $mysqli->prepare("INSERT INTO " . VoterConnector::$TABLE_NAME . "(" . VoterConnector::$COLUMN_STUDENTID . ", " . VoterConnector::$COLUMN_EVENTID . ", " . VoterConnector::$COLUMN_GROUPID . ") VALUES (?, ?, ?)");
            
            // selectNumberVotesStatement counts number of votes a voter did for a particular event
			$this->selectNumberVotesStatement = $mysqli->prepare("SELECT COUNT(*) FROM " . VoterConnector::$TABLE_NAME . " WHERE " . VoterConnector::$COLUMN_STUDENTID . "=? AND " . VoterConnector::$COLUMN_EVENTID . "=?");
			
            // selectIfVotedStatement counts number of votes a voter gave for a particular group at a particular event
            // should be a 0 or 1. 1 means voted. 0 means yet to vote.
            $this->selectIfVotedStatement = $mysqli->prepare("SELECT COUNT(*) FROM " . VoterConnector::$TABLE_NAME . " WHERE " . VoterConnector::$COLUMN_STUDENTID . "=? AND " . VoterConnector::$COLUMN_EVENTID . "=? AND " . VoterConnector::$COLUMN_GROUPID . "=?");
			
            // selectUniqueVotersStatement was intended to search for unique voters at an event
            // but not it just grabs every entry including repeats
            $this->selectUniqueVotersStatement = $mysqli->prepare("SELECT `voters`.`studentid` FROM `voters` LEFT JOIN `tags` ON `tags`.`studentid`=`voters`.`studentid` WHERE `voters`.`eventid`=?");
			
            // deletes a particular entry based on entry id
            $this->deleteStatement = $mysqli->prepare("DELETE FROM " . VoterConnector::$TABLE_NAME . " WHERE " . VoterConnector::$COLUMN_ID . "=?");
			
            // deletes all entries with a given eventid
            $this->deleteEventStatement = $mysqli->prepare("DELETE FROM " . VoterConnector::$TABLE_NAME . " WHERE " . VoterConnector::$COLUMN_EVENTID . "=?");
		}
		
		public function createVote($studentid, $eventid, $groupid) {
            // creates a record of which group a voter voted for at a given event
			$this->createStatement->bind_param("sii", $studentid, $eventid, $groupid);
			
			if(!$this->createStatement->execute()){
				die('Query failed: ' . $this->createStatement->error);
			}			

			return $this->mysqli->insert_id;
		}
		
		public function selectNumberVotes($studentid, $eventid) {
            // counts number of votes a voter gave for a particular event
			$this->selectNumberVotesStatement->bind_param("si", $studentid, $eventid);
			if(!$this->selectNumberVotesStatement->execute()) return false;
			$this->selectNumberVotesStatement->store_result();
			$this->selectNumberVotesStatement->bind_result($votes);
			$this->selectNumberVotesStatement->fetch();
			
			$this->selectNumberVotesStatement->free_result();
			
			return $votes;
		}
		
		public function selectIfVoted($studentid, $eventid, $groupid) {
            // counts number of votes a voter gave for a particular group at a particular event
            // should be either 1 for voted and 0 for yet to vote
            // if more than 1, means the student voted for the same group multiple times
			$this->selectIfVotedStatement->bind_param("sii", $studentid, $eventid, $groupid);
			if(!$this->selectIfVotedStatement->execute()) return false;
			$this->selectIfVotedStatement->store_result();
			$this->selectIfVotedStatement->bind_result($votes);
			$this->selectIfVotedStatement->fetch();
			
			$this->selectIfVotedStatement->free_result();
			
			return $votes;
		}
		
		public function selectUniqueVoters($eventid) {
            // grabs every entry from database
			$this->selectUniqueVotersStatement->bind_param("i", $eventid);
			if(!$this->selectUniqueVotersStatement->execute()) return false;
			$result = $this->selectUniqueVotersStatement->get_result();
			$resultArray = $result->fetch_all(MYSQLI_ASSOC);
			return $resultArray;
		}
		
		public function deleteVote($voteid) {
            // deletes a particular vote entry
			$this->deleteStatement->bind_param("i", $voteid);
			if(!$this->deleteStatement->execute()) return false;
			
			return true;
		}
		
		public function deleteEvent($eventid) {
            // deletes all votes for a given event
			$this->deleteEventStatement->bind_param("i", $eventid);
			if(!$this->deleteEventStatement->execute()) return false;
			
			return true;
		}
	}
?>
