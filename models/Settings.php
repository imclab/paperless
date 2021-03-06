<?php

class Settings{

	/*
	 * All arguments should be specified as strings
	 *
	 * Default settings format:
	 * 
	 * <setting_name> => array(
	 *			'default' => <default_value>,
	 *			'options' => array(<option1>, <option2>, ... <optionN>)	
	 *	)
	 */
	public static $options = array(	'theme' => 
										array(	'default' => 'default', 
												'options' => 
													array('default', 'ultra', 'midnight', 'django',
															'dark', 'eclipse', 'emacs', 'gray')
											),
								 	'show_sl_hint' =>
										array(	'default' => 'true',
												'options' =>
													array('true', 'false')
											)
								 );
	
	public $user;
	private $config; //array
	private $id;
	
	/*
	 * Return an array containing the default settings for a user.
	 */
	private static function default_settings(){
		$defaults = array();
		foreach(Settings::$options as $setting => $config){
			$defaults[$setting] = $config['default'];
		}
		return $defaults;
	}
	
	/*
	 * Return all the valid options for a key.
	 */
	public static function get_options($key){
		return Settings::$options[$key]['options'];
	}
	
	
	/*
	 * Return the settings value for a user for this key.
	 */
	public function get_value($key){
		return $this->config[$key];
	}
	
	/*
	 * Set the value for the user for this key.
	 */
	public function set_value($key, $value){
		$this->config[$key] = $value;
	}
	
	
	/*
	 * If the configuration array had extra key value pairs, remove them. This can 
	 * happen if we decide to change the settings array in the future.
	 */
	private function remove_extraneous_keys(){
		$valid_keys = array_keys(Settings::$options);
		$this->config = array_intersect_key($this->config, array_flip($valid_keys));
	}
	
	/*
	 * If the user is missing any settings, we populate it from the defaults. Also, if 
	 * one of the settings values was invalid, we restore it to default.
	 */
	private function populate_missing_from_defaults(){
		foreach(Settings::$options as $setting => $configuration){
			$default_val = $configuration['default'];
			$valid_values = $configuration['options'];
			
			// Set the default value if it is not set.
			if(!array_key_exists($setting, $this->config)){
				$this->config[$setting] = $default_val;
			}else{
				// If it was set, but was invalid, reset it as well.
				$cur_val = $this->config[$setting];
				if(!in_array($cur_val, $valid_values)){
					$this->config[$setting] = $default_val;
				}
			}
		}
	}
	
	// Save the configuration for a user.
	public function save(){
		$encoded = json_encode($this->config);
		// $query = "REPLACE INTO PaperlessProfile VALUES(:User, :Config);";
		$query = "UPDATE PaperlessProfile SET Config = :Config WHERE User = :User";
		$db = Database::getConnection();
		
		try {
			$sth = $db->prepare($query);
			$rows = $sth->execute(array(':Config' => $encoded, ":User" => $this->user->id));
		} catch(PDOException $e) {

		}	
	}
	
	/*
	 * Create a new entry in the db for this user.
	 */
	private static function create($user){
		$db = Database::getConnection();		
		$query = "INSERT INTO PaperlessProfile (User) Values (:uid)";
		try {
			$sth = $db->prepare($query);
			$sth->execute(array(":uid" => $user->id));
		} catch(PDOException $e) {

		}
				
	}
	
	public static function get_for_user($user){
		$instance = new self();
		$instance->user = $user;
		$db = Database::getConnection();		
		$query = "SELECT ID, Config FROM PaperlessProfile WHERE User = :uid;";
		try {
			$sth = $db->prepare($query);
			$sth->execute(array(":uid" => $user->id));
			$sth->setFetchMode(PDO::FETCH_ASSOC);
			if($row = $sth->fetch()) {
				$instance->id = $row['ID'];
				$instance->config = json_decode($row['Config'], true); // turn into assoc. array.	
			}else{
				$instance->config = Settings::default_settings(); // empty for now
				Settings::create($user);
			}
		} catch(PDOException $e) {

		}
		
		$instance->remove_extraneous_keys();
		$instance->populate_missing_from_defaults();
		$instance->save();		
		return $instance;
	}
}

?>