<?php
/*
 * comments-helper
 *
 * General Purpose Library
 * It contains static functions.
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( !function_exists('wp_safe_redirect')) {
    require_once (ABSPATH . WPINC . '/pluggable.php');
}

class commentsHelper {

	/**
	 * Wrapper for print_r() that formats the array for HTML output
	 *
	 * @return void
	 */
    function pre_print_r($txt) {
		print("<pre>\n"); 
		print_r($txt); 
		print("</pre>\n");
	}
	
	/**
	 * Checks whether the supplied $number is even
	 *
	 * @return boolean
	 */
    function is_even($number) {
		if ($number % 2 == 0 )
		{
			// The number is even
			return true;
		}
    		else
		{
			// The number is odd
			return false;
		}
	}

    function redirect($location = '') {
		if(empty($location))
		{
			$location = $_SERVER['PHP_SELF'];

			if($_GET)
			{
                /* Sanitize $_GET to prevent XSS. */
                $_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
				$args = '?';
				foreach($_GET as $var => $value)
				{
					$args .= "$var=$value&";
				}
			}
		}
        wp_safe_redirect($location . $args);
		exit();
	}
}
?>
