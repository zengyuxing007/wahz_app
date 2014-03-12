<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Mobage Language Helpers
 *
 * @package		Mobage
 * @subpackage	Helpers
 * @category	Helpers
 * @author		mobage platformDev Team
 * @link		http://mobagepf.com/user_guide/helpers/language_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Lang
 *
 * Fetches a language variable and optionally outputs a form label
 *
 * @access	public
 * @param	string	the language line
 * @param	string	the id of the form element
 * @return	string
 */
if ( ! function_exists('lang'))
{
	function lang($line, $id = '')
	{
		$MG =& get_instance();
		$line = $MG->lang->line($line);

		if ($id != '')
		{
			$line = '<label for="'.$id.'">'.$line."</label>";
		}

		return $line;
	}
}

// ------------------------------------------------------------------------
/* End of file language_helper.php */
/* Location: ./system/helpers/language_helper.php */
