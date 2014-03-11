<?php

class Wahz_model extends MG_Model 
{
    public $mydb=null;

    public function __construct()
    {   
        $this->mydb = $this->load->database('wahz',TRUE);
    }   


    public function get_news_simple($startTime=1,$type=3,$limit=20){
		$type_cond = '';
		if($type == 3){
			$type_cond = '(`type` = 1 or `type` = 2)';
		}
		else{
			$type_cond = "`type` = $type";
		}

        $sql = "select `id`,`title`,`image_url`,`order`,`create_time` from news_simple where create_time >= $startTime and $type_cond limit $limit";
        $query = $this->mydb->query($sql);
        return $query->result_array(); 
    }


	public function get_news_content_by_id($id=0){
		if(!$id) {return null;}
		log_message('debug',"id=$id");
		$sql = "select `content` from news_simple where `id` = $id";
		$query = $this->mydb->query($sql);
		return $query->result_array(); 
	}

	public function get_show_list($startTime=1){
		$sql = "select `show_time`,`desc` from show_list where show_time >= $startTime";
        $query = $this->mydb->query($sql);
        return $query->result_array(); 
		
	}

	public function update_player_info($data=null){
		if(!is_array($data)) { return false;}
		unset($data['verify_code']);
		return $this->mydb->replace('user_data',$data);
    }

	public function check_user_exist($unique_id=null){
		if(!$unique_id) return false;
		$sql = "select 1 from user_data where unique_id = '$unique_id'";
		$query = $this->mydb->query($sql);
	    if($row = $query->row_array()){
			return true;
		}
		return false;
	}
	
	public function get_reward_num($end_time){
	    $sql = "select count(1) as number from wahz_card where is_use!=0 and activeTime <=$end_time";
		$query = $this->mydb->query($sql);
		if($row = $query->row_array()){
			return $row['number'];
		}
		return 0;
	}

	public function get_user_reward_num($unique_id){
		$sql = "select count(1) as number from wahz_card where uid='$unique_id'";
		$query = $this->mydb->query($sql);
		if($row = $query->row_array()){
			return $row['number'];
		}
		return 0;
	
	}

	public function  get_reward_info($reward_list){
		if(!$reward_list) return null;
		$sql = 'select wahz_card.`code`,wahz_card.`activeTime` as get_time,wahz_card.`is_use`,wahz_card.`reward_type`,config_reward.`reward_img_url`,config_reward.`name` FROM';
		$sql .= "  wahz_card,config_reward WHERE wahz_card.reward_type=config_reward.id AND wahz_card.`code` IN($reward_list)";

        $query = $this->mydb->query($sql);
        return $query->result_array(); 
	}

	public function  get_user_all_record($unique_id){
			if(!$unique_id) return null;
			$sql = "select wahz_card.`code` FROM wahz_card WHERE wahz_card.`uid` = '$unique_id'";

			$query = $this->mydb->query($sql);
			$code = array();
			if($data = $query->result_array()){
				foreach($data as $key => $d){
					$code[] = $d['code'];
				}
			}
			return $code;
	}

	
	public function active_a_reward($unique_id){
		if(!$unique_id) return null;
		$reward = array();
		$sql = "select `id`,`reward_type`,`code` from wahz_card where is_use=0 for update";
		$this->mydb->trans_start();
		$query = $this->mydb->query($sql);
		if($row = $query->row_array()){
		    $now = time();
			$result = $this->mydb->query("update wahz_card set is_use=1,activeTime=$now,uid='$unique_id' where id=".$row['id']);
			if($result){
				$reward['code'] = $row['code'];
				$reward['reward_type'] = $row['reward_type'];
			}
		}
		$this->mydb->trans_complete();
		return $reward;
	} 


	public function up_advice($unique_id,$advice){
		if(!$unique_id or !$advice){
			return false;
		}
		$now = time();
		$sql = "insert into advice(unique_id,info,create_time) values('$unique_id','$advice',$now)";
        return $this->mydb->query($sql);
	}

	public function up_device_info($device_token,$unique_id){
		if(!$device_token or !$unique_id){
			return false;
		}
		$now = time();
		$sql = "replace into device_info(`unique_id`,`device_token`,create_time)  values('$unique_id','$device_token',$now)";
        return $this->mydb->query($sql);
	}
}
