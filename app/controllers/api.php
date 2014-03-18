<?php

class Api extends MG_Controller
{
    public function __construct(){
        parent::__construct();
        $this->image_base_url = "http://app.setv.sh.cn/uploadimg/";
        $this->image_thumbnail_url= "http://app.setv.sh.cn/thumbnail/";
        $this->load->model('wahz_model');  
        $this->load->helper('global');  
		$this->salt = 'wahz_123#$%';
    }


    public function index(){
       echo '<h1>api:Don\'t be panic!!!</h1>';
    }

    public function getNewsList(){
       $request = $this->input->get_post(NULL,TRUE);
       $startTime = isset($request['startTime'])?$request['startTime']:0;
       $type = isset($request['type'])?$request['type']:3;
       $limit = isset($request['limit'])?$request['limit']:20;
	   
       //var_dump($request);
       //return;

	   $data = $this->wahz_model->get_news_simple($startTime,$type,$limit);

       foreach ($data as $key => $d){
           $d['image_url_s'] = $this->image_thumbnail_url.$d['image_url'];
           $d['image_url'] = $this->image_base_url.$d['image_url'];
           $data[$key] = $d;
       }
	   //var_dump($data);
       //$data = array('1' => '21122');

       $response = array(
			'result' => 0,
		    'number' => count($data),
            'data' => $data
       );
	   if(isset($request['debug'])){
			dump($response);
			return;
	   }
	   $this->output->set_output(json_encode($response));
    }

    public function getNewsInfo(){
		$request = $this->input->get_post(NULL,TRUE);
		$content = '';
		if(isset($request['id'])){
			$data = $this->wahz_model->get_news_content_by_id($request['id']);
//		var_dump($data); return;
			if(is_array($data) and isset($data[0]['content'])){
					$content=$data[0]['content'];
			}
		}
		$response = array(
			'content' => $content
		);

		if(isset($request['debug'])){
			dump($response);
			return;
		}
        $this->output->set_output(json_encode($response));
    }

	public function getShowList(){
		$request = $this->input->get_post(NULL,TRUE);
		$startTime = isset($request['startTime'])?$request['startTime']:1;
		$data = $this->wahz_model->get_show_list($startTime);
		$response = array(
			'number' => count($data),
			'data' => $data
		);
		if(isset($request['debug'])){
			dump($response);
			return;
		}
		$this->output->set_output(json_encode($response));
	}

	public function updatePlayerInfo(){
		$desc = array(
			'0' => 'success',
			'1' => 'unknow device type',
			'2' => 'verify code error',
            '3' => 'invalid request,lack some paramters',
            '500' => 'system error'
		);
		$request = $this->input->get_post(NULL,TRUE);
        $response = '';
		$result = 0;
		if(!isset($request['unique_id']) or !isset($request['device_type']) or
		   !isset($request['user_name']) or !isset($request['phone_no']) or
		   !isset($request['address']) or !isset($request['verify_code']) 
			){
			$result = 3;
		}else{
			$verify_code = md5($request['unique_id'].$request['device_type'].$this->salt);
		    if(strtoupper($verify_code) != $request['verify_code']){
				log_message('error',"verify code error:".$request['verify_code']."\tshould be $verify_code");
				$result = 2;
			}else{
				//update sql
				$data = array(
					'unique_id'   => $request['unique_id'],
					'device_type' => $request['device_type'],
					'user_name'   => $request['user_name'],
					'phone_no'    => $request['phone_no'],
					'address'     => $request['address'],
					'zcode'       => isset($request['zcode'])?$request['zcode']:'200000',
                    'update_time' => time()
				);
				if(!$this->wahz_model->update_player_info($data)){
					$result = 500;
				}
			}
		}
		$response = array('result' => $result, 'desc' => $desc[$result]);
		if(isset($request['debug'])){
			dump($response);
			return;
		}
		$this->output->set_output(json_encode($response));
    }

	/*
	   1。一共只有1种奖品
	   2。奖品数量只有100份
	   3。3.22 ,前5周每周。共15位中奖者 最后一周为25位
	 */
	public function lottery(){
		log_message('debug',"----lottery....");
		$request = $this->input->get_post(NULL,TRUE);
        $response = '';
		$result=0;
		$reward_code = '';
		if(!isset($request['unique_id']) or !isset($request['right_answers']) or !$request['unique_id'] or !$request['right_answers']){
			$result = 0;
		}
		//at first,check if unique_id exist;
		else if(!$this->wahz_model->check_user_exist($request['unique_id'])){
			log_message('error',"unique_id[".$request['unique_id']."] not exist!!!");
			$result = 0;
		}
		else{
			//lottery process
			//begin time : 3.22
			$begin_time = strtotime('2014-03-22 00:00:00');
			$internal_time = 7*24*3600; //one week
			$reward_num_array = array(15,15,15,15,15,25);
			if(isset($request['test'])){
			    $reward_num_array = array(15000,15000,15000,15000,15000,25000);
			}
			$now = time();
			if($now < $begin_time and !isset($request['debug'])){
				log_message('debug',"---time begin:$begin_time");
				$result = 0;
			}
            else
			{
			    $week_num = intval(($now - $begin_time) / $internal_time);
			    //check this week if full
				$total = 0;
				for($i=0;$i<=$week_num;++$i){
					$total += $reward_num_array[$i];
				}
				$week_end = $begin_time + $internal_time + $internal_time * $week_num;
				log_message('debug',"week[$week_num] --total[$total] ");
				if($this->wahz_model->get_reward_num($week_end) >= $total){
					log_message('debug',"week[$week_num] --total[$total] but have full... ");
				    $result = 0;	
				}else{
						//give a probability
						$prob = 15+intval($request['right_answers']);
						$user_reward_num = $this->wahz_model->get_user_reward_num($request['unique_id']);
						log_message('debug','user['.$request['unique_id']."] right_answer:".$request['right_answers']." and he have get reward[$user_reward_num]");
						$max = 1000+$user_reward_num*500;
						if(isset($request['debug']) or isset($request['test'])) $prob += $max;
						$random = rand(0,$max);
						log_message('debug',"prob[$prob],random:$random");
						if($random <= $prob){
								//congratuation!!!
								$reward = $this->wahz_model->active_a_reward($request['unique_id']);
								if($reward and isset($reward['code'])){
										$result = 1;
								}
						}
				}
			//end logic
			}
		}
		log_message('debug',"lottery result:$result");
		if($result){
			$response = array('result' => $result, 'code' => $reward['code'],'reward' => $reward['reward_type']);
		}else{
			$response = array('result' => $result);
		}
		if(isset($request['debug'])){
				dump($response);
				return;
		}
		$this->output->set_output(json_encode($response));
	}


	//advice
	public function advice(){
		$desc = array(0 => 'success',1 => 'lack some necessary paramters',2 => 'userid not exist',500 => 'system error');
		$request = $this->input->get_post(NULL,TRUE);
        $response = '';
        $result = 0;
		if(!isset($request['unique_id']) or !isset($request['advice']) or !$request['unique_id'] or !$request['advice'] ){
			$result = 1;
		}
		//at first,check if unique_id exist;
		else if(!$this->wahz_model->check_user_exist($request['unique_id'])){
			log_message('error',"advice---unique_id[".$request['unique_id']."] not exist!!!");
			$result = 2;
		} else{
		    if($this->wahz_model->up_advice($request['unique_id'],$request['advice'])){
				$result = 0;
			}else{
			    log_message('error','up_advice db error:'.$request['advice']."\t".$request['unique_id']);
				$result = 500;
			}
		}
		
		$response = array('result' => $result,'desc' => $desc[$result]);
		if(isset($request['debug'])){
				dump($response);
				return;
		}
		$this->output->set_output(json_encode($response));
	}

	//get reward info
	public function getRewardInfo(){
		$desc = array(0 => 'success',1 => 'lack some necessary paramters');
		$request = $this->input->get_post(NULL,TRUE);
        $data = null;
		if(!isset($request['code_list'])){
			$result =1;
		}
		else{
			$codeArray = explode(',',$request['code_list']);
			$list = implode("','",$codeArray); 
			$list = "'$list'";
			$result = 0;
			$data = $this->wahz_model->get_reward_info($list);
		}

		$response = array('result' => $result,'desc' => $desc[$result]);
		if(!$result and $data) $response['data'] = $data;
		if(isset($request['debug'])){
				dump($response);
				return;
		}
		$this->output->set_output(json_encode($response));
	}


    //get user all reward info
	public function getUserAllReward(){


		$desc = array(0 => 'success',1 => 'lack some necessary paramters');
		$request = $this->input->get_post(NULL,TRUE);
        $data = null;

		if(!isset($request['unique_id']) or !$request['unique_id']){
			$result = 1;
		}
		else{
			$codeArray = $this->wahz_model->get_user_all_record($request['unique_id']);
			$result = 0;
			if($codeArray and !empty($codeArray)){
					$list = implode("','",$codeArray); 
					$list = "'$list'";
					$data = $this->wahz_model->get_reward_info($list);
			}
		}

       	$response = array('result' => $result,'desc' => $desc[$result]);
		if(!$result and $data) $response['data'] = $data;
		if(isset($request['debug'])){
				dump($response);
				return;
		}
		$this->output->set_output(json_encode($response));

	}

	public function upDeviceInfo(){
		$desc = array(0 => 'success',1 => 'lack some necessary paramters',
					  2 => 'verify_code error',
						500=>'system error');

		$request = $this->input->get_post(NULL,TRUE);
		
		if(!isset($request['device_token']) or !isset($request['unique_id']) or !isset($request['verify_code'])){
			$result =1;
		}
		else if(md5($request['device_token'].$request['unique_id'].$this->salt) != strtolower($request['verify_code'])){
			$result = 2;
		}
		else{
			$device_token = str_replace(" ","",$request['device_token']);
			if($this->wahz_model->up_device_info($device_token,$request['unique_id'])){
				$result = 0;
			}else{
				$result = 500;
			}
		}
		$response = array('result' => $result,'desc' => $desc[$result]);

		if(isset($request['debug'])){
				dump($response);
				return;
		}
		$this->output->set_output(json_encode($response));
	}

}

?>
