<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Localfile extends MG_Controller {


    function index($filename=0)
    {

        $this->output->cache(30);

        if($this->_do_some($filename))
        {
            //load file have check file if exsits ...
            $this->load->file("/tmp/$filename",false); 
        }
        else
        {
            echo "load file false\n";
        }

    }


    private function _do_some($filename)
    {
        if(file_exists($filename))
            return true;
        else
            return false;
    }



}
