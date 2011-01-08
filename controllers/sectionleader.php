<?php
require_once("permissions.php");
	
	function studentSort($a, $b){
		return $a['DisplayName'] > $b['DisplayName'];
	}
	
	class SectionLeaderHandler extends ToroHandler {
		
		function sortAll($students){
			if($students)
				uasort($students, 'studentSort');
			return $students;
		}
		
		public function get($class, $sectionleader) {
			$role = Permissions::requireRole(POSITION_SECTION_LEADER, $class);
			
			if($role == POSITION_SECTION_LEADER){
				$this->smarty->assign("sl_class", $class);
			}
			if($role > POSITION_SECTION_LEADER){
				$this->smarty->assign("admin_class", $class);
			}
									
			$dirname = SUBMISSIONS_PREFIX . "/" . $class . "/" . SUBMISSIONS_DIR . "/" . $sectionleader . "/";
			
			$assns = $this->getDirEntries($dirname);
			
			$students = Model::getStudentsForSectionLeader($sectionleader, $class);
			//print_r($students);
			
			$students = $this->sortAll($students);
			
			$this->smarty->assign("students", $students);
			
			// assign template variables
			$this->smarty->assign("assignments", $assns);
			$this->smarty->assign("class", strtoupper(htmlentities($class)));
			$this->smarty->assign("sl", $sectionleader);
			
			// display the template
			$this->smarty->display('index.html');
		}
	}
?>