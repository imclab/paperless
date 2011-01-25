<?php
	require_once("models/AssignmentFile.php");
	require_once("models/AssignmentComment.php");
	require_once("models/Model.php");
	require_once("permissions.php");
	require_once("utils.php");
	
	/*
	 * Controller that handles the syntax highlighted code view
	 * for a student, assignment pair and other ajax actions.
	 */
	class CodeHandler extends ToroHandler {
		
		/*
		 * Given a filename, tests if this is a good file type for the class
		 * i.e cs106a only gets java files and 106bx gets cpp/h
		 */
		private function isCodeFile($filename, $class){
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$ext = strtolower($ext);
			$filetypes = getFileTypesForClass($class);
			return in_array($ext, $filetypes);
		}
		
		/*
		 * Gets the file contents, file names, and appropriate AssignmentFile model objects
		 * corresponding to a given student, assignment pair.
		 * TODO: Don't use a hardcoded path / we need to allow multiple base search paths
		 * TODO: Handle error when a pathname is not found
		 */

		private function getAssignmentFiles($class, $student, $assignment, $sl) {
			
			$dirname = SUBMISSIONS_PREFIX . "/" . $class . "/" . SUBMISSIONS_DIR . "/" . $sl . "/". $assignment . "/" . $student . "/"; 
			echo $dirname;

			if(!is_dir($dirname)) return null; // TODO handle error
			
			$dir = opendir($dirname);
			$files = array();
			$file_contents = array();
			$assignment_files = array();
			
			$release = False;
			while($file = readdir($dir)) {
				if($file == "release"){
					$release = True;
				}else if($this->isCodeFile($file, $class)) {
					$assignmentFile = AssignmentFile::load(array("FilePath" => $dirname . $file));
					if(!$assignmentFile) {
					  $string = explode("_", $student); // if it was student_1 just take student
      			$student_suid = $string[0];
					  $gradedAssignID = Model::getGradedAssignID($class, $student_suid, $assignment);
					  /* if(!$gradedAssignID) {
					    echo "Couldn't find graded assignment $assignment for $student student in class $class!";
					  } */
						$assignmentFile = AssignmentFile::create($gradedAssignID, $dirname . $file);
						$assignmentFile->save();
					}
					
					$assignment_files[] = $assignmentFile;
					$files[] = $file;
					$file_contents[] = htmlentities(file_get_contents($dirname . $file));
				}
			}
			return array($files, $file_contents, $assignment_files, $release);
		}
		
		/*
		 * Displays the syntax highlighted code for a student, assignment pair
		 */
		public function get($class, $assignment, $student, $print=False) {
			//echo "student " . $student;
			if($print){
				$this->smarty->assign("print_view", 1);
			}
			
			$this->smarty->assign("code_file", $student);
			
			$suid = explode("_", $student); // if it was student_1 just take student
			$suid = $suid[0];
			
			// if the username is something other than the owner of these files, require
			// it to be a SL
			if($suid != USERNAME) {
				$role = Permissions::requireRole(POSITION_SECTION_LEADER, $class);
			}else{
				$role = Model::getRoleForClass(USERNAME, $class);
			}
			

			
		    $sl = Model::getSectionLeaderForStudent($suid);
			//echo $student . " ". $sl . "\n";
			
			list($files, $file_contents, $assignment_files, $release) = $this->getAssignmentFiles($class, $student, $assignment, $sl);
			
			if(count($files) == 0){
				  Header("Location: " . ROOT_URL . "error/for/you");
			}
			
			if($role == POSITION_SECTION_LEADER){
				$this->smarty->assign("sl_class", $class);
				$this->smarty->assign("interactive", 1);
				$showComments = True;
			}
			if($role == POSITION_STUDENT){
				$this->smarty->assign("student_class", $class);
				$showComments = $release;
			}
			if($role > POSITION_SECTION_LEADER){
				$this->smarty->assign("admin_class", $class);
				$showComments = True;
			}
			
			// assign template vars
			$this->smarty->assign("code", true);
			
			$string = explode("_", $student); // if it was student_1 just take student
			$student_suid = $string[0];
			
			$this->smarty->assign("class", htmlentities($class));
			$this->smarty->assign("student", htmlentities($student_suid));
			$this->smarty->assign("assignment", htmlentities($assignment));
			$this->smarty->assign("files", $files);
			$this->smarty->assign("file_contents", $file_contents);
			$this->smarty->assign("assignment_files", $assignment_files);
			$this->smarty->assign("sl", $sl);
			
			$this->smarty->assign("release", $release);
			$this->smarty->assign("showComments", $showComments);
			
			// display the template
			$this->smarty->display("code.html");
		}
		
		/*
		 * Handles adding and deleting comments.  Note: when a comment is edited it is
		 * first deleted and then re-added to the database.
		 * TODO: Log / handle an error when we don't have a valid path
		 * TODO: We probably need to filter user input somehow
		 * TODO: This should output (via echo or whatever) some valid JSON that is used
		 *       to confirm the request succeeded
		 */
		public function post_xhr($class, $assignment, $student) {

			// only section leaders should be able to add comments
			Permissions::requireRole(POSITION_SECTION_LEADER, $class);
			
			$suid = explode("_", $student); // if it was student_1 just take student
			$suid = $suid[0];
			$sl = Model::getSectionLeaderForStudent($suid);
			
			$dirname = SUBMISSIONS_PREFIX . "/" . $class . "/" . SUBMISSIONS_DIR . "/" . $sl . "/". $assignment . "/" . $student . "/"; 

			if(!isset($_POST['action'])) return;

			
			if($_POST['action'] == "release"){
				if($_POST['release'] == "create"){
					createRelease($dirname);
				}else{
					deleteRelease($dirname);
				}
				return;
			}
			
			$curFile = AssignmentFile::load(array("FilePath" => $dirname . $_POST['filename']));
			if(!$curFile) return; // TODO handle error
			
			if($_POST['action'] == "create") {
				$newComment = AssignmentComment::create($curFile->getID(), $_POST['rangeLower'], $_POST['rangeHigher'], $_POST['text']);
				$newComment->save();
			} else if($_POST['action'] == "delete") {
				// find the comment to delete
				foreach($curFile->getAssignmentComments() as $comment) {
					if($comment->getStartLine() == $_POST['rangeLower'] && $comment->getEndLine() == $_POST['rangeHigher']) {
						$comment->delete();
						break;
					}
				}
			} 
		}
	}
	?>