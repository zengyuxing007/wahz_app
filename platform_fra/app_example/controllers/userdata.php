<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Userdata extends MG_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    public function index($uid=0)
    {
        if($uid==0)
        {
            echo "what do you want to do?\n";
            return;
        }
        //json data
        $dbh = $this->load->database('default',TRUE);
        if(!$dbh)
        {
            echo "load database error\n";
            return;
        }
        echo "query user[$uid] data\n";

        $q = $dbh->query("select * from user where uid=$uid");

        $exsist = false;

        foreach ($q->result() as $row)
        {
            echo "data:\t".json_encode($row)."\n";
            $exsist = true;
        }
        if(!$exsist)
        {
            echo "not exsist this user:$uid\n";
        }
        else
        {
        }
        return;
    }


    public function add($uid=0,$nickname='null')
    {
        echo "try to add user\tuid:$uid,nickname:$nickname\n";
    }

    public function get($uid=0)
    {
        echo "try to get uid:$uid\n";
    }

    public function update($uid=0)
    {
        echo "try to update uid:$uid\n";
    }

    public function del($uid=0)
    {
        echo "try to del uid:$uid\n";
    }


}
