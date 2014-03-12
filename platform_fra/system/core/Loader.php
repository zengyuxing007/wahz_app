<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Loader Class
 *
 * Loads views and files
 *
 * @package		Mobage
 * @subpackage	Libraries
 * @author		mobage platformDev Team
 * @category	Loader
 * @link		http://mobagepf.com/user_guide/libraries/loader.html
 */
class MG_Loader {

	// All these are set automatically. Don't mess with them.
	/**
	 * Nesting level of the output buffering mechanism
	 *
	 * @var int
	 * @access protected
	 */
	protected $_mg_ob_level;
	/**
	 * List of paths to load views from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_view_paths		= array();
	/**
	 * List of paths to load libraries from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_library_paths	= array();
	/**
	 * List of paths to load models from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_model_paths		= array();
	/**
	 * List of paths to load helpers from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_helper_paths		= array();
	/**
	 * List of loaded base classes
	 * Set by the controller class
	 *
	 * @var array
	 * @access protected
	 */
	protected $_base_classes		= array(); // Set by the controller class
	/**
	 * List of cached variables
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_cached_vars		= array();
	/**
	 * List of loaded classes
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_classes			= array();
	/**
	 * List of loaded files
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_loaded_files		= array();
	/**
	 * List of loaded models
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_models			= array();
	/**
	 * List of loaded helpers
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_helpers			= array();
	/**
	 * List of class name mappings
	 *
	 * @var array
	 * @access protected
	 */
	protected $_mg_varmap			= array('unit_test' => 'unit',
											'user_agent' => 'agent');

	/**
	 * Constructor
	 *
	 * Sets the path to the view files and gets the initial output buffering level
	 */
	public function __construct()
	{
		$this->_mg_ob_level  = ob_get_level();
		$this->_mg_library_paths = array(APPPATH, BASEPATH);
		$this->_mg_helper_paths = array(APPPATH, BASEPATH);
		$this->_mg_model_paths = array(APPPATH);
		$this->_mg_view_paths = array(APPPATH.'views/'	=> TRUE);
		if(LANGUAGE == 'tw'){
                        $this->_mg_view_paths = array(APPPATH.'views_tw/'       => TRUE);
                }else{
                        $this->_mg_view_paths = array(APPPATH.'views/'       => TRUE);
                }
		log_message('debug', "Loader Class Initialized");
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize the Loader
	 *
	 * This method is called once in MG_Controller.
	 *
	 * @param 	array
	 * @return 	object
	 */
	public function initialize()
	{
		$this->_mg_classes = array();
		$this->_mg_loaded_files = array();
		$this->_mg_models = array();
		$this->_base_classes =& is_loaded();

		$this->_mg_autoloader();

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Is Loaded
	 *
	 * A utility function to test if a class is in the self::$_mg_classes array.
	 * This function returns the object name if the class tested for is loaded,
	 * and returns FALSE if it isn't.
	 *
	 * It is mainly used in the form_helper -> _get_validation_object()
	 *
	 * @param 	string	class being checked for
	 * @return 	mixed	class object name on the MG SuperObject or FALSE
	 */
	public function is_loaded($class)
	{
		if (isset($this->_mg_classes[$class]))
		{
			return $this->_mg_classes[$class];
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Class Loader
	 *
	 * This function lets users load and instantiate classes.
	 * It is designed to be called from a user's app controllers.
	 *
	 * @param	string	the name of the class
	 * @param	mixed	the optional parameters
	 * @param	string	an optional object name
	 * @return	void
	 */
	public function library($library = '', $params = NULL, $object_name = NULL)
	{
		if (is_array($library))
		{
			foreach ($library as $class)
			{
				$this->library($class, $params);
			}

			return;
		}

		if ($library == '' OR isset($this->_base_classes[$library]))
		{
			return FALSE;
		}

		if ( ! is_null($params) && ! is_array($params))
		{
			$params = NULL;
		}

		$this->_mg_load_class($library, $params, $object_name);
	}

	// --------------------------------------------------------------------

	/**
	 * Model Loader
	 *
	 * This function lets users load and instantiate models.
	 *
	 * @param	string	the name of the class
	 * @param	string	name for the model
	 * @param	bool	database connection
	 * @return	void
	 */
	public function model($model, $name = '', $db_conn = FALSE)
	{
		if (is_array($model))
		{
			foreach ($model as $babe)
			{
				$this->model($babe);
			}
			return;
		}

		if ($model == '')
		{
			return;
		}

		$path = '';

		// Is the model in a sub-folder? If so, parse out the filename and path.
		if (($last_slash = strrpos($model, '/')) !== FALSE)
		{
			// The path is in front of the last slash
			$path = substr($model, 0, $last_slash + 1);

			// And the model name behind it
			$model = substr($model, $last_slash + 1);
		}

		if ($name == '')
		{
			$name = $model;
		}

		if (in_array($name, $this->_mg_models, TRUE))
		{
			return;
		}

		$MG =& get_instance();
		if (isset($MG->$name))
		{
			show_error('The model name you are loading is the name of a resource that is already being used: '.$name);
		}

		$model = strtolower($model);

		foreach ($this->_mg_model_paths as $mod_path)
		{
			if ( ! file_exists($mod_path.'models/'.$path.$model.'.php'))
			{
				continue;
			}

			if ($db_conn !== FALSE AND ! class_exists('MG_DB'))
			{
				if ($db_conn === TRUE)
				{
					$db_conn = '';
				}

				$MG->load->database($db_conn, FALSE, TRUE);
                log_message('debug',"model function load databse[$db_conn]....");
			}

			if ( ! class_exists('MG_Model'))
			{
				load_class('Model', 'core');
			}

			require_once($mod_path.'models/'.$path.$model.'.php');

			$model = ucfirst($model);

			$MG->$name = new $model();

			$this->_mg_models[] = $name;
			return;
		}

		// couldn't find the model
		show_error('Unable to locate the model you have specified: '.$model);
	}

	// --------------------------------------------------------------------

	/**
	 * Database Loader
	 *
	 * @param	string	the DB credentials
	 * @param	bool	whether to return the DB object
	 * @param	bool	whether to enable active record (this allows us to override the config setting)
	 * @return	object
	 */
	public function database($params = '', $return = FALSE, $active_record = NULL)
	{
		// Grab the super object
		$MG =& get_instance();

		//the db pool
		require_once(BASEPATH.'database/DB_pool.php');
		$MG->db_pool = MG_DB_pool::get_instance();

		// Do we even need to load the database class?
		if (class_exists('MG_DB') AND $return == FALSE AND $active_record == NULL AND isset($MG->db) AND is_object($MG->db))
		{
			return FALSE;
		}

		require_once(BASEPATH.'database/DB.php');

		if ($return === TRUE)
		{
            log_message('debug','database return true-------no set this->db');;
	
			$db = & DB($params, $active_record);
			$MG->db_pool->put_in_pool($db);
			return $db;
		}

		// Initialize the db variable.  Needed to prevent
		// reference errors with some configurations
		$MG->db = '';

		// Load the DB class
		$MG->db =& DB($params, $active_record);
		$MG->db_pool->put_in_pool($MG->db);

	}

	// --------------------------------------------------------------------

	/**
	 * Load the Utilities Class
	 *
	 * @return	string
	 */
	public function dbutil()
	{
		if ( ! class_exists('MG_DB'))
		{
			$this->database();
		}

		$MG =& get_instance();

		// for backwards compatibility, load dbforge so we can extend dbutils off it
		// this use is deprecated and strongly discouraged
		$MG->load->dbforge();

		require_once(BASEPATH.'database/DB_utility.php');
		require_once(BASEPATH.'database/drivers/'.$MG->db->dbdriver.'/'.$MG->db->dbdriver.'_utility.php');
		$class = 'MG_DB_'.$MG->db->dbdriver.'_utility';

		$MG->dbutil = new $class();
	}

	// --------------------------------------------------------------------

	/**
	 * Load the Database Forge Class
	 *
	 * @return	string
	 */
	public function dbforge()
	{
		if ( ! class_exists('MG_DB'))
		{
			$this->database();
		}

		$MG =& get_instance();

		require_once(BASEPATH.'database/DB_forge.php');
		require_once(BASEPATH.'database/drivers/'.$MG->db->dbdriver.'/'.$MG->db->dbdriver.'_forge.php');
		$class = 'MG_DB_'.$MG->db->dbdriver.'_forge';

		$MG->dbforge = new $class();
	}

	// --------------------------------------------------------------------

	/**
	 * Load View
	 *
	 * This function is used to load a "view" file.  It has three parameters:
	 *
	 * 1. The name of the "view" file to be included.
	 * 2. An associative array of data to be extracted for use in the view.
	 * 3. TRUE/FALSE - whether to return the data or load it.  In
	 * some cases it's advantageous to be able to return data so that
	 * a developer can process it in some way.
	 *
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	void
	 */
	public function view($view, $vars = array(), $return = FALSE)
	{
		return $this->_mg_load(array('_mg_view' => $view, '_mg_vars' => $this->_mg_object_to_array($vars), '_mg_return' => $return));
	}

	// --------------------------------------------------------------------

	/**
	 * Load File
	 *
	 * This is a generic file loader
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	public function file($path, $return = FALSE)
	{
		return $this->_mg_load(array('_mg_path' => $path, '_mg_return' => $return));
	}

	// --------------------------------------------------------------------

	/**
	 * Set Variables
	 *
	 * Once variables are set they become available within
	 * the controller class and its "view" files.
	 *
	 * @param	array
	 * @param 	string
	 * @return	void
	 */
	public function vars($vars = array(), $val = '')
	{
		if ($val != '' AND is_string($vars))
		{
			$vars = array($vars => $val);
		}

		$vars = $this->_mg_object_to_array($vars);

		if (is_array($vars) AND count($vars) > 0)
		{
			foreach ($vars as $key => $val)
			{
				$this->_mg_cached_vars[$key] = $val;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Variable
	 *
	 * Check if a variable is set and retrieve it.
	 *
	 * @param	array
	 * @return	void
	 */
	public function get_var($key)
	{
		return isset($this->_mg_cached_vars[$key]) ? $this->_mg_cached_vars[$key] : NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Load Helper
	 *
	 * This function loads the specified helper file.
	 *
	 * @param	mixed
	 * @return	void
	 */
	public function helper($helpers = array())
	{
		foreach ($this->_mg_prep_filename($helpers, '_helper') as $helper)
		{
			if (isset($this->_mg_helpers[$helper]))
			{
				continue;
			}

			$ext_helper = APPPATH.'helpers/'.config_item('subclass_prefix').$helper.'.php';

			// Is this a helper extension request?
			if (file_exists($ext_helper))
			{
				$base_helper = BASEPATH.'helpers/'.$helper.'.php';

				if ( ! file_exists($base_helper))
				{
					show_error('Unable to load the requested file: helpers/'.$helper.'.php');
				}

				include_once($ext_helper);
				include_once($base_helper);

				$this->_mg_helpers[$helper] = TRUE;
				log_message('debug', 'Helper loaded: '.$helper);
				continue;
			}

			// Try to load the helper
			foreach ($this->_mg_helper_paths as $path)
			{
				if (file_exists($path.'helpers/'.$helper.'.php'))
				{
					include_once($path.'helpers/'.$helper.'.php');

					$this->_mg_helpers[$helper] = TRUE;
					log_message('debug', 'Helper loaded: '.$helper);
					break;
				}
			}

			// unable to load the helper
			if ( ! isset($this->_mg_helpers[$helper]))
			{
				show_error('Unable to load the requested file: helpers/'.$helper.'.php');
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Load Helpers
	 *
	 * This is simply an alias to the above function in case the
	 * user has written the plural form of this function.
	 *
	 * @param	array
	 * @return	void
	 */
	public function helpers($helpers = array())
	{
		$this->helper($helpers);
	}

	// --------------------------------------------------------------------

	/**
	 * Loads a language file
	 *
	 * @param	array
	 * @param	string
	 * @return	void
	 */
	public function language($file = array(), $lang = '')
	{
		$MG =& get_instance();

		if ( ! is_array($file))
		{
			$file = array($file);
		}

		foreach ($file as $langfile)
		{
			$MG->lang->load($langfile, $lang);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Loads a config file
	 *
	 * @param	string
	 * @param	bool
	 * @param 	bool
	 * @return	void
	 */
	public function config($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
	{
		$MG =& get_instance();
		$MG->config->load($file, $use_sections, $fail_gracefully);
	}

	// --------------------------------------------------------------------

	/**
	 * Driver
	 *
	 * Loads a driver library
	 *
	 * @param	string	the name of the class
	 * @param	mixed	the optional parameters
	 * @param	string	an optional object name
	 * @return	void
	 */
	public function driver($library = '', $params = NULL, $object_name = NULL)
	{
		if ( ! class_exists('MG_Driver_Library'))
		{
			// we aren't instantiating an object here, that'll be done by the Library itself
			require BASEPATH.'libraries/Driver.php';
		}

		if ($library == '')
		{
			return FALSE;
		}

		// We can save the loader some time since Drivers will *always* be in a subfolder,
		// and typically identically named to the library
		if ( ! strpos($library, '/'))
		{
			$library = ucfirst($library).'/'.$library;
		}

		return $this->library($library, $params, $object_name);
	}

	// --------------------------------------------------------------------

	/**
	 * Add Package Path
	 *
	 * Prepends a parent path to the library, model, helper, and config path arrays
	 *
	 * @param	string
	 * @param 	boolean
	 * @return	void
	 */
	public function add_package_path($path, $view_cascade=TRUE)
	{
		$path = rtrim($path, '/').'/';

		array_unshift($this->_mg_library_paths, $path);
		array_unshift($this->_mg_model_paths, $path);
		array_unshift($this->_mg_helper_paths, $path);

		$this->_mg_view_paths = array($path.'views/' => $view_cascade) + $this->_mg_view_paths;

		// Add config file path
		$config =& $this->_mg_get_component('config');
		array_unshift($config->_config_paths, $path);
	}

	// --------------------------------------------------------------------

	/**
	 * Get Package Paths
	 *
	 * Return a list of all package paths, by default it will ignore BASEPATH.
	 *
	 * @param	string
	 * @return	void
	 */
	public function get_package_paths($include_base = FALSE)
	{
		return $include_base === TRUE ? $this->_mg_library_paths : $this->_mg_model_paths;
	}

	// --------------------------------------------------------------------

	/**
	 * Remove Package Path
	 *
	 * Remove a path from the library, model, and helper path arrays if it exists
	 * If no path is provided, the most recently added path is removed.
	 *
	 * @param	type
	 * @param 	bool
	 * @return	type
	 */
	public function remove_package_path($path = '', $remove_config_path = TRUE)
	{
		$config =& $this->_mg_get_component('config');

		if ($path == '')
		{
			$void = array_shift($this->_mg_library_paths);
			$void = array_shift($this->_mg_model_paths);
			$void = array_shift($this->_mg_helper_paths);
			$void = array_shift($this->_mg_view_paths);
			$void = array_shift($config->_config_paths);
		}
		else
		{
			$path = rtrim($path, '/').'/';
			foreach (array('_mg_library_paths', '_mg_model_paths', '_mg_helper_paths') as $var)
			{
				if (($key = array_search($path, $this->{$var})) !== FALSE)
				{
					unset($this->{$var}[$key]);
				}
			}

			if (isset($this->_mg_view_paths[$path.'views/']))
			{
				unset($this->_mg_view_paths[$path.'views/']);
			}

			if (($key = array_search($path, $config->_config_paths)) !== FALSE)
			{
				unset($config->_config_paths[$key]);
			}
		}

		// make sure the application default paths are still in the array
		$this->_mg_library_paths = array_unique(array_merge($this->_mg_library_paths, array(APPPATH, BASEPATH)));
		$this->_mg_helper_paths = array_unique(array_merge($this->_mg_helper_paths, array(APPPATH, BASEPATH)));
		$this->_mg_model_paths = array_unique(array_merge($this->_mg_model_paths, array(APPPATH)));
		$this->_mg_view_paths = array_merge($this->_mg_view_paths, array(APPPATH.'views/' => TRUE));
		$config->_config_paths = array_unique(array_merge($config->_config_paths, array(APPPATH)));
	}

	// --------------------------------------------------------------------

	/**
	 * Loader
	 *
	 * This function is used to load views and files.
	 * Variables are prefixed with _mg_ to avoid symbol collision with
	 * variables made available to view files
	 *
	 * @param	array
	 * @return	void
	 */
	protected function _mg_load($_mg_data)
	{
		// Set the default data variables
		foreach (array('_mg_view', '_mg_vars', '_mg_path', '_mg_return') as $_mg_val)
		{
			$$_mg_val = ( ! isset($_mg_data[$_mg_val])) ? FALSE : $_mg_data[$_mg_val];
		}

		$file_exists = FALSE;

		// Set the path to the requested file
		if ($_mg_path != '')
		{
			$_mg_x = explode('/', $_mg_path);
			$_mg_file = end($_mg_x);
		}
		else
		{
			$_mg_ext = pathinfo($_mg_view, PATHINFO_EXTENSION);
			$_mg_file = ($_mg_ext == '') ? $_mg_view.'.php' : $_mg_view;

			foreach ($this->_mg_view_paths as $view_file => $cascade)
			{
				if (file_exists($view_file.$_mg_file))
				{
					$_mg_path = $view_file.$_mg_file;
					$file_exists = TRUE;
					break;
				}

				if ( ! $cascade)
				{
					break;
				}
			}
		}

		if ( ! $file_exists && ! file_exists($_mg_path))
		{
			show_error('Unable to load the requested file: '.$_mg_file);
		}

		// This allows anything loaded using $this->load (views, files, etc.)
		// to become accessible from within the Controller and Model functions.

		$_mg_MG =& get_instance();
		foreach (get_object_vars($_mg_MG) as $_mg_key => $_mg_var)
		{
			if ( ! isset($this->$_mg_key))
			{
				$this->$_mg_key =& $_mg_MG->$_mg_key;
			}
		}

		/*
		 * Extract and cache variables
		 *
		 * You can either set variables using the dedicated $this->load_vars()
		 * function or via the second parameter of this function. We'll merge
		 * the two types and cache them so that views that are embedded within
		 * other views can have access to these variables.
		 */
		if (is_array($_mg_vars))
		{
			$this->_mg_cached_vars = array_merge($this->_mg_cached_vars, $_mg_vars);
		}
		extract($this->_mg_cached_vars);

		/*
		 * Buffer the output
		 *
		 * We buffer the output for two reasons:
		 * 1. Speed. You get a significant speed boost.
		 * 2. So that the final rendered template can be
		 * post-processed by the output class.  Why do we
		 * need post processing?  For one thing, in order to
		 * show the elapsed page load time.  Unless we
		 * can intercept the content right before it's sent to
		 * the browser and then stop the timer it won't be accurate.
		 */
		ob_start();

		// If the PHP installation does not support short tags we'll
		// do a little string replacement, changing the short tags
		// to standard PHP echo statements.

		if ((bool) @ini_get('short_open_tag') === FALSE AND config_item('rewrite_short_tags') == TRUE)
		{
			echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($_mg_path))));
		}
		else
		{
			include($_mg_path); // include() vs include_once() allows for multiple views with the same name
		}

		log_message('debug', 'File loaded: '.$_mg_path);

		// Return the file data if requested
		if ($_mg_return === TRUE)
		{
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}

		/*
		 * Flush the buffer... or buff the flusher?
		 *
		 * In order to permit views to be nested within
		 * other views, we need to flush the content back out whenever
		 * we are beyond the first level of output buffering so that
		 * it can be seen and included properly by the first included
		 * template and any subsequent ones. Oy!
		 *
		 */
		if (ob_get_level() > $this->_mg_ob_level + 1)
		{
			ob_end_flush();
		}
		else
		{
			$_mg_MG->output->append_output(ob_get_contents());
			@ob_end_clean();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Load class
	 *
	 * This function loads the requested class.
	 *
	 * @param	string	the item that is being loaded
	 * @param	mixed	any additional parameters
	 * @param	string	an optional object name
	 * @return	void
	 */
	protected function _mg_load_class($class, $params = NULL, $object_name = NULL)
	{
		// Get the class name, and while we're at it trim any slashes.
		// The directory path can be included as part of the class name,
		// but we don't want a leading slash
		$class = str_replace('.php', '', trim($class, '/'));

		// Was the path included with the class name?
		// We look for a slash to determine this
		$subdir = '';
		if (($last_slash = strrpos($class, '/')) !== FALSE)
		{
			// Extract the path
			$subdir = substr($class, 0, $last_slash + 1);

			// Get the filename from the path
			$class = substr($class, $last_slash + 1);
		}

		// We'll test for both lowercase and capitalized versions of the file name
		foreach (array(ucfirst($class), strtolower($class)) as $class)
		{
			$subclass = APPPATH.'libraries/'.$subdir.config_item('subclass_prefix').$class.'.php';

			// Is this a class extension request?
			if (file_exists($subclass))
			{
				$baseclass = BASEPATH.'libraries/'.ucfirst($class).'.php';

				if ( ! file_exists($baseclass))
				{
					log_message('error', "Unable to load the requested class: ".$class);
					show_error("Unable to load the requested class: ".$class);
				}

				// Safety:  Was the class already loaded by a previous call?
				if (in_array($subclass, $this->_mg_loaded_files))
				{
					// Before we deem this to be a duplicate request, let's see
					// if a custom object name is being supplied.  If so, we'll
					// return a new instance of the object
					if ( ! is_null($object_name))
					{
						$MG =& get_instance();
						if ( ! isset($MG->$object_name))
						{
							return $this->_mg_init_class($class, config_item('subclass_prefix'), $params, $object_name);
						}
					}

					$is_duplicate = TRUE;
					log_message('debug', $class." class already loaded. Second attempt ignored.");
					return;
				}

				include_once($baseclass);
				include_once($subclass);
				$this->_mg_loaded_files[] = $subclass;

				return $this->_mg_init_class($class, config_item('subclass_prefix'), $params, $object_name);
			}

			// Lets search for the requested library file and load it.
			$is_duplicate = FALSE;
			foreach ($this->_mg_library_paths as $path)
			{
				$filepath = $path.'libraries/'.$subdir.$class.'.php';

				// Does the file exist?  No?  Bummer...
				if ( ! file_exists($filepath))
				{
					continue;
				}

				// Safety:  Was the class already loaded by a previous call?
				if (in_array($filepath, $this->_mg_loaded_files))
				{
					// Before we deem this to be a duplicate request, let's see
					// if a custom object name is being supplied.  If so, we'll
					// return a new instance of the object
					if ( ! is_null($object_name))
					{
						$MG =& get_instance();
						if ( ! isset($MG->$object_name))
						{
							return $this->_mg_init_class($class, '', $params, $object_name);
						}
					}

					$is_duplicate = TRUE;
					log_message('debug', $class." class already loaded. Second attempt ignored.");
					return;
				}

				include_once($filepath);
				$this->_mg_loaded_files[] = $filepath;
				return $this->_mg_init_class($class, '', $params, $object_name);
			}

		} // END FOREACH

		// One last attempt.  Maybe the library is in a subdirectory, but it wasn't specified?
		if ($subdir == '')
		{
			$path = strtolower($class).'/'.$class;
			return $this->_mg_load_class($path, $params);
		}

		// If we got this far we were unable to find the requested class.
		// We do not issue errors if the load call failed due to a duplicate request
		if ($is_duplicate == FALSE)
		{
			log_message('error', "Unable to load the requested class: ".$class);
			show_error("Unable to load the requested class: ".$class);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Instantiates a class
	 *
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	string	an optional object name
	 * @return	null
	 */
	protected function _mg_init_class($class, $prefix = '', $config = FALSE, $object_name = NULL)
	{
		// Is there an associated config file for this class?  Note: these should always be lowercase
		if ($config === NULL)
		{
			// Fetch the config paths containing any package paths
			$config_component = $this->_mg_get_component('config');

			if (is_array($config_component->_config_paths))
			{
				// Break on the first found file, thus package files
				// are not overridden by default paths
				foreach ($config_component->_config_paths as $path)
				{
					// We test for both uppercase and lowercase, for servers that
					// are case-sensitive with regard to file names. Check for environment
					// first, global next
					if (defined('ENVIRONMENT') AND file_exists($path .'config/'.ENVIRONMENT.'/'.strtolower($class).'.php'))
					{
						include($path .'config/'.ENVIRONMENT.'/'.strtolower($class).'.php');
						break;
					}
					elseif (defined('ENVIRONMENT') AND file_exists($path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php');
						break;
					}
					elseif (file_exists($path .'config/'.strtolower($class).'.php'))
					{
						include($path .'config/'.strtolower($class).'.php');
						break;
					}
					elseif (file_exists($path .'config/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path .'config/'.ucfirst(strtolower($class)).'.php');
						break;
					}
				}
			}
		}

		if ($prefix == '')
		{
			if (class_exists('MG_'.$class))
			{
				$name = 'MG_'.$class;
			}
			elseif (class_exists(config_item('subclass_prefix').$class))
			{
				$name = config_item('subclass_prefix').$class;
			}
			else
			{
				$name = $class;
			}
		}
		else
		{
			$name = $prefix.$class;
		}

		// Is the class name valid?
		if ( ! class_exists($name))
		{
			log_message('error', "Non-existent class: ".$name);
			show_error("Non-existent class: ".$class);
		}

		// Set the variable name we will assign the class to
		// Was a custom class name supplied?  If so we'll use it
		$class = strtolower($class);

		if (is_null($object_name))
		{
			$classvar = ( ! isset($this->_mg_varmap[$class])) ? $class : $this->_mg_varmap[$class];
		}
		else
		{
			$classvar = $object_name;
		}

		// Save the class name and object name
		$this->_mg_classes[$class] = $classvar;

		// Instantiate the class
		$MG =& get_instance();
		if ($config !== NULL)
		{
			$MG->$classvar = new $name($config);
		}
		else
		{
			$MG->$classvar = new $name;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Autoloader
	 *
	 * The config/autoload.php file contains an array that permits sub-systems,
	 * libraries, and helpers to be loaded automatically.
	 *
	 * @param	array
	 * @return	void
	 */
	private function _mg_autoloader()
	{
		if (defined('ENVIRONMENT') AND file_exists(APPPATH.'config/'.ENVIRONMENT.'/autoload.php'))
		{
			include(APPPATH.'config/'.ENVIRONMENT.'/autoload.php');
		}
		else
		{
			include(APPPATH.'config/autoload.php');
		}

		if ( ! isset($autoload))
		{
			return FALSE;
		}

		// Autoload packages
		if (isset($autoload['packages']))
		{
			foreach ($autoload['packages'] as $package_path)
			{
				$this->add_package_path($package_path);
			}
		}

		// Load any custom config file
		if (count($autoload['config']) > 0)
		{
			$MG =& get_instance();
			foreach ($autoload['config'] as $key => $val)
			{
				$MG->config->load($val);
			}
		}

		// Autoload helpers and languages
		foreach (array('helper', 'language') as $type)
		{
			if (isset($autoload[$type]) AND count($autoload[$type]) > 0)
			{
				$this->$type($autoload[$type]);
			}
		}

		// A little tweak to remain backward compatible
		// The $autoload['core'] item was deprecated
		if ( ! isset($autoload['libraries']) AND isset($autoload['core']))
		{
			$autoload['libraries'] = $autoload['core'];
		}

		// Load libraries
		if (isset($autoload['libraries']) AND count($autoload['libraries']) > 0)
		{
			// Load the database driver.
			if (in_array('database', $autoload['libraries']))
			{
				$this->database();
				$autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
			}

			// Load all other libraries
			foreach ($autoload['libraries'] as $item)
			{
				$this->library($item);
			}
		}

		// Autoload models
		if (isset($autoload['model']))
		{
			$this->model($autoload['model']);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Object to Array
	 *
	 * Takes an object as input and converts the class variables to array key/vals
	 *
	 * @param	object
	 * @return	array
	 */
	protected function _mg_object_to_array($object)
	{
		return (is_object($object)) ? get_object_vars($object) : $object;
	}

	// --------------------------------------------------------------------

	/**
	 * Get a reference to a specific library or model
	 *
	 * @param 	string
	 * @return	bool
	 */
	protected function &_mg_get_component($component)
	{
		$MG =& get_instance();
		return $MG->$component;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep filename
	 *
	 * This function preps the name of various items to make loading them more reliable.
	 *
	 * @param	mixed
	 * @param 	string
	 * @return	array
	 */
	protected function _mg_prep_filename($filename, $extension)
	{
		if ( ! is_array($filename))
		{
			return array(strtolower(str_replace('.php', '', str_replace($extension, '', $filename)).$extension));
		}
		else
		{
			foreach ($filename as $key => $val)
			{
				$filename[$key] = strtolower(str_replace('.php', '', str_replace($extension, '', $val)).$extension);
			}

			return $filename;
		}
	}
}

/* End of file Loader.php */
/* Location: ./system/core/Loader.php */
