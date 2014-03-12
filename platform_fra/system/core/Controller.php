<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Mobage Application Controller Class
 *
 * This class object is the super class that every library in
 * Mobage will be assigned to.
 *
 * @package		Mobage
 * @subpackage	Libraries
 * @category	Libraries
 * @author		mobage platformDev Team
 * @link		http://mobagepf.com/user_guide/general/controllers.html
 */
class MG_Controller {

	private static $instance;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		self::$instance =& $this;
		
		// Assign all the class objects that were instantiated by the
		// bootstrap file (Mobage.php) to local class variables
		// so that MG can run as one big super object.
		foreach (is_loaded() as $var => $class)
		{
			$this->$var =& load_class($class);
		}

		$this->load =& load_class('Loader', 'core');

		$this->load->initialize();
		
		log_message('debug', "Controller Class Initialized");
	}

	public static function &get_instance()
	{
		return self::$instance;
	}
}
// END Controller class

/* End of file Controller.php */
/* Location: ./system/core/Controller.php */
