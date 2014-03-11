<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('dump')){

		function dump($varVal, $isExit = FALSE){
				ob_start();
				var_dump($varVal);
				$varVal = ob_get_clean();
				$varVal = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $varVal);
				echo '<pre>'.$varVal.'</pre>';
				$isExit && exit();
		}
}
