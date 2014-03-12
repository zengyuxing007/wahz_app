<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Myencrypt extends MG_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('encrypt');
    }

    public function index()
    {
        echo "test encrypt lib\n";
        return;
    }

    public function encode($simple_msg)
    {
        $encode_msg = $this->encrypt->encode($simple_msg,"encrypt key");
        echo "$simple_msg after encode=> $encode_msg\n";

        echo "decode;;;;";

        $plain_msg = $this->encrypt->decode($encode_msg,"encrypt key");

        echo "after decode:$plain_msg\n";

    }

}
