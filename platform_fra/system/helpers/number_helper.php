<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Mobage Number Helpers
 *
 * @package		Mobage
 * @subpackage	Helpers
 * @category	Helpers
 * @author		mobage platformDev Team
 * @link		http://mobagepf.com/user_guide/helpers/number_helper.html
 */

// ------------------------------------------------------------------------

/**
 * Formats a numbers as bytes, based on size, and adds the appropriate suffix
 *
 * @access	public
 * @param	mixed	// will be cast as int
 * @return	string
 */
if ( ! function_exists('byte_format'))
{
	function byte_format($num, $precision = 1)
	{
		$MG =& get_instance();
		$MG->lang->load('number');

		if ($num >= 1000000000000)
		{
			$num = round($num / 1099511627776, $precision);
			$unit = $MG->lang->line('terabyte_abbr');
		}
		elseif ($num >= 1000000000)
		{
			$num = round($num / 1073741824, $precision);
			$unit = $MG->lang->line('gigabyte_abbr');
		}
		elseif ($num >= 1000000)
		{
			$num = round($num / 1048576, $precision);
			$unit = $MG->lang->line('megabyte_abbr');
		}
		elseif ($num >= 1000)
		{
			$num = round($num / 1024, $precision);
			$unit = $MG->lang->line('kilobyte_abbr');
		}
		else
		{
			$unit = $MG->lang->line('bytes');
			return number_format($num).' '.$unit;
		}

		return number_format($num, $precision).' '.$unit;
	}
}


/* End of file number_helper.php */
/* Location: ./system/helpers/number_helper.php */
