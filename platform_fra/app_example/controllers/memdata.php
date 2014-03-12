<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Memdata extends MG_Controller {

    public $mem = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->driver('cache',array('adapter' => 'memcached','backup' => 'file'));
        $this->mem = $this->cache->__get('memcached');
    }

    public function index()
    {
        echo "memcache test\n";
    }


    public function save($key,$value)
    {
        if($this->mem->save($key, $value, 10))
        {
            echo "save ok!\n";
        }
        else
        {
            echo "save failed\n";
        }
    }

    public function get($key)
    {
        if($data = $this->mem->get($key))
            echo $data;
        else
            echo "not found:$key\n";
    }

}
