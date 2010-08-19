<?
	$cfg = array();

	$cfg[db_name]			= 'music';
	$cfg[db_host]			= 'localhost';
	$cfg[db_user]			= 'root';
	$cfg[db_pass]			= 'root';

	$cfg[token_secret]		= 'enter-sceret-here';

	$cfg[file_roots] = array(
		'C:/Documents and Settings/Administrator/Desktop/music',
		#'C:/Documents and Settings/Administrator/My Documents/My Music/iTunes/iTunes Music',
	);


	#
	# load the libraries
	#

	define('INCLUDE_DIR', dirname(__FILE__));

	include(INCLUDE_DIR.'/lib_db.php');
	include(INCLUDE_DIR.'/lib_misc.php');


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
