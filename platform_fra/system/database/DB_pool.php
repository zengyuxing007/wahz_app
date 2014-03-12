
<?php  #if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MG_DB_pool {

	private static $ini_dbs;
	private static $tans_db;
	private static $instance;

	private function __construct(){
		$this->ini_dbs = array();
		$this->trans_db	= array();
	}

	public static function get_instance(){
		if(!isset(self::$instance)){
			#log_message("debug", " chenxuan here is init db ");
			self::$instance = new MG_DB_pool();	
		}

		return self::$instance;
	}

	public function put_in_pool($db) {
		array_push($this->ini_dbs, $db);
	}

	public function put_in_trans($db) {
		// When transactions are nested we only begin/commit/rollback the outermost ones
		$db->inc_trans_depth();
		if ($db->get_trans_depth() > 1)
		{
			return;
		}
		$db->trans_begin();

		#log_message("debug", "chenxuan put db : $db->database into trans array");
		array_push($this->trans_db, $db);
	}

	public function get_db_pool(){
		return $this->ini_dbs;
	}

	public function close_all(){
		foreach ($this->ini_dbs as $db){
			if (isset ($db)){
				#log_message("debug", " chenxuan closed db : $db->database");
				$db->close();
			}   
		}   
	}

	private function trans_allcommit(){
		foreach( $this->trans_db as $trans_db){
			// When transactions are nested we only begin/commit/rollback the outermost ones
			$trans_db->dec_trans_depth();
			if ($trans_db->get_trans_depth() > 0)
			{
				continue;
			}
			#log_message("debug", " chenxuan commit db : $trans_db->database");
			$trans_db->trans_commit();
		}
	}

	private function trans_allrollback(){
		foreach( $this->trans_db as $trans_db){
			// When transactions are nested we only begin/commit/rollback the outermost ones
			$trans_db->dec_trans_depth();
			if ($trans_db->get_trans_depth() > 0)
			{
				continue;
			}
			#log_message("debug", " chenxuan rollback db : $trans_db->database");
			$trans_db->trans_rollback();
		}
	}

	public function trans_complete(){
		$flg = TRUE;
		foreach( $this->trans_db as $trans_db){
			$flg &= $trans_db->trans_status();
		}

		if($flg ){
			$this->trans_allcommit();
		}else{
			$this->trans_allrollback();
		}
	
	}

}

