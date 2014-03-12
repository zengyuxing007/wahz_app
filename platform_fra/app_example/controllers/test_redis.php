<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Test_redis extends MG_Controller {

	public function __construct()
	{
		parent::__construct();
		#$this->redis = $this->cache->__get('redis');
		   $this->load->driver('cache',array('adapter' => 'redis','backup' => 'file'));
		   $this->redis = $this->cache->__get('redis');
	}


	public function test(){
		$this->redis->save('new_portal_test','hello world');
		echo $this->redis->get('new_portal_test');
	}
}


