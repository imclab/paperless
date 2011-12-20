<?php
require_once("permissions.php");
require_once('models/User.php');

	
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
			$status = Model::getRoleForClass($sectionleader, $class); //sanity check: make sure they are visiting an sl for this class
	     	if($status < POSITION_SECTION_LEADER){
				Header("Location: " . ROOT_URL);
			}
			
			$user = new User(USERNAME);
			//print_r($user);
			
			$role = Permissions::requireRole(POSITION_SECTION_LEADER, $class);
			
			if($role == POSITION_SECTION_LEADER){
				$this->smarty->assign("sl_class", $class);
			}
			if($role > POSITION_SECTION_LEADER){
				$this->smarty->assign("admin_class", $class);
			}
					
			$slsdirname = SUBMISSIONS_PREFIX . "/" . $class . "/" . SUBMISSIONS_DIR;
			$sls = $this->sortAll($this->getDirEntries($slsdirname));
									
			$dirname = SUBMISSIONS_PREFIX . "/" . $class . "/" . SUBMISSIONS_DIR . "/" . $sectionleader . "/";
			
			$assns = $this->getDirEntries($dirname);
			if(empty($assns) || strlen($assns[0]) == 0){
				$this->smarty->assign("no_assns", 1);
			}
			
			$students = Model::getStudentsForSectionLeader($sectionleader, $class);
			//print_r($students);
			
			$students = $this->sortAll($students);
			
			$this->smarty->assign("students", $students);
			
			// assign template variables
			$this->smarty->assign("sls", $sls);
			$this->smarty->assign("assignments", $assns);
			$this->smarty->assign("class", htmlentities($class));
			$this->smarty->assign("sl", $sectionleader);
			
			// display the template
			$this->smarty->display('index.html');
		}
	}
?>