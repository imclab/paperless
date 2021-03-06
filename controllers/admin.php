<?php

	/**
	 * This class is the handler for the Administrative view. The Admin view is seen
	 * by the head TA of a class as well as the Professor for the class. It is a basic
	 * view which includes a listing of all of the section leaders in the class, as 
	 * determined by the class submissions directory, since each SL has his own folder.
	 * We require the user to be at least a TA for the course to view this page.
	 *
	 * @author	Jeremy Keeshin	December 22, 2011
	 */
	class AdminHandler extends ToroHandler {
		
		public function get($qid, $class) {
			$this->basic_setup(func_get_args());
			Permissions::gate(POSITION_TEACHING_ASSISTANT, $this->role);			
			
			$sls = $this->course->get_sl_sunetids();
			if($sls){
				sort($sls);
			}
			
			// Handle a search
			$results = array();
			if(array_key_exists('q', $_GET)){
				$query = $_GET['q'];
				$results = User::search($query);
			}
			$this->smarty->assign("search", $results);
				
			// assign template variables
			$this->smarty->assign("sls", $sls);
			$this->smarty->display('admin.html');
		}
	}
	?>