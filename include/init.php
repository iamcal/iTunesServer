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


	#
	# functions that don't fit elsewhere
	#

	function dumper($foo){

		echo "<pre style=\"text-align: left;\">";
		echo HtmlSpecialChars(var_export($foo, 1));
		echo "</pre>\n";
	}


	function auth_create_token($name){

		$time = time();
		$base = "{$name}-{$time}";
		$sig = sha1($GLOBALS[cfg][token_secret].$base);
		return "{$base}-{$sig}";
	}

	function auth_check_token($token){

		$bits = explode('-', $token);
		$sig = array_pop($bits);
		$time = array_pop($bits);
		$name = implode('-', $bits);

		$test = sha1($GLOBALS[cfg][token_secret]."{$name}-{$time}");

		if ($test != $sig) return 0;

		return $name;
	}
?>
