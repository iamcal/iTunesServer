<?
	#
	# $Id$
	#

	define('INCLUDE_DIR', dirname(__FILE__));


	#
	# load the config & libraries
	#

	if (!@include(INCLUDE_DIR.'/config.php')){
		echo "You need to rename <code>include/config.php.example</code> to <code>include/config.php</code> and modify the settings.";
	}
	include(INCLUDE_DIR.'/lib_db.php');
	include(INCLUDE_DIR.'/lib_misc.php');
	include(INCLUDE_DIR.'/lib_lastfm.php');
	include(INCLUDE_DIR.'/lib_http.php');
	include(INCLUDE_DIR.'/lib_auth.php');


	#
	# functions that don't fit elsewhere
	#

	function dumper($foo){

		echo "<pre style=\"text-align: left;\">";
		echo HtmlSpecialChars(var_export($foo, 1));
		echo "</pre>\n";
	}
?>
