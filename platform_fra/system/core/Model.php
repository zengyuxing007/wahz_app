<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Mobage Model Class
 *
 * @package		Mobage
 * @subpackage	Libraries
 * @category	Libraries
 * @author		mobage platformDev Team
 * @link		http://mobagepf.com/user_guide/libraries/config.html
 */
class MG_Model {

	/**
	 * Constructor
	 *
	 * @access public
	 */
	function __construct()
	{
		log_message('debug', "Model Class Initialized");
	}

	/**
	 * __get
	 *
	 * Allows models to access MG's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @param	string
	 * @access private
	 */
	function __get($key)
	{
		$MG =& get_instance();
		return $MG->$key;
	}
}
// END Model Class

/* End of file Model.php */
/* Location: ./system/core/Model.php */
