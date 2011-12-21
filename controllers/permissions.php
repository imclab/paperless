<?php

class Permissions {

	public static function requireRole($role, $class) {
		$curRole = Model::getRoleForClass(USERNAME, $class);

		// Special case for cs198 coordinator access to cs106.
		if($class == "cs106a" || $class == "cs106b" || $class == "cs106x"){
			$cs198Role = Model::getRoleForClass(USERNAME, "cs198");
			// If they are the coordinator, give them general SL access to the entire class.
			if($cs198Role == POSITION_COORDINATOR && $role < POSITION_TEACHING_ASSISTANT){
				return POSITION_SECTION_LEADER;
			}
		}

		if($curRole < $role) {
			Header("Location: " . ROOT_URL);
		}
		return $curRole;
	}


	public static function require_role($role, $user, $course) {
		$cur_role = $user->get_role_for_course($course);
		if($cur_role < $role){
			Header("Location: " . ROOT_URL);	
		}
		return $cur_role;
		// 
		// // Special case for cs198 coordinator access to cs106.
		// if($class == "cs106a" || $class == "cs106b" || $class == "cs106x"){
		// 	$cs198Role = Model::getRoleForClass(USERNAME, "cs198");
		// 	// If they are the coordinator, give them general SL access to the entire class.
		// 	if($cs198Role == POSITION_COORDINATOR && $role < POSITION_TEACHING_ASSISTANT){
		// 		return POSITION_SECTION_LEADER;

	}

}



?>
