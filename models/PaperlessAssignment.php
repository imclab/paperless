<?php
require_once(dirname(dirname(__FILE__)) . "/config.php");
require_once(dirname(dirname(__FILE__)) . "/models/Model.php");

class PaperlessAssignment extends Model {

	private $ID;
	private $Quarter;
	private $Class;
	private $DirectoryName;
	private $Name;
	private $DueDate;

	public function __construct() {
		parent::__construct();
	}

	/*
		* Saves the current assignment files's state to 
		* the database.
		*/
	public function save() {
		$query = "REPLACE INTO PaperlessAssignments ". 
			" VALUES(:ID, :Quarter, :Class, :DirectoryName, :Name, :DueDate);";

		try {
			$sth = $this->conn->prepare($query);
			$rows = $sth->execute(array(":ID" => $this->ID,
										":Quarter" => $this->Quarter,
										":Class" => $this->Class,
										":DirectoryName" => $this->DirectoryName,
										":Name" => $this->Name,
										":DueDate" => $this->DueDate
										));
			if(!$this->ID) {
				$this->ID = $this->conn->lastInsertId();
			}
		} catch(PDOException $e) {
			echo $e->getMessage(); // TODO log this error instead of echo
		}
	}

	public function deleteID($id) {
		echo "deleting...". $id;
		$instance = new self();
		$query = "DELETE FROM PaperlessAssignments " .
			" WHERE ID=:ID;";
		try {
			$sth = $instance->conn->prepare($query);
			$sth->execute(array(":ID" => $id));
		} catch(PDOException $e) {
			echo "fail..";
			echo $e->getMessage(); // TODO log this error instead of echo
		}
	}

	public static function create($class, $dir, $name, $due) {
		$instance = new self();
		$quarter_id = Model::getQuarterID();
		$class_id = Model::getClassID($class);
		$instance->fill(array(0, $quarter_id, $class_id, $dir, $name, $due));
		return $instance;
	}

	/*
		* Load from an id
		*/
	public static function loadForClass($class) {
		$query = "SELECT * FROM PaperlessAssignments WHERE Class=:Class";
		$instance = new self();
		
		try {
			$sth = $instance->conn->prepare($query);
			$class_id = Model::getClassID($class);
	      	$sth->setFetchMode(PDO::FETCH_ASSOC);
			$sth->execute(array(":Class" => $class_id));
			if($rows = $sth->fetchAll()) {
				//print_r($rows);
				return $rows;
			}
		} catch(PDOException $e) {
			echo $e->getMessage(); // TODO log this error instead of echo
		}
		return null;
	}

	/*
		* Getters and Setters
		*/

	public function fill(array $row) { 
		$this->ID = $row[0];
		$this->Quarter = $row[1];
		$this->Class = $row[2];
		$this->DirectoryName = $row[3];
		$this->Name = $row[4];
		$this->DueDate = $row[5];
	}

}
?>