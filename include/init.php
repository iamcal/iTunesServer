<?
	$cfg = array();

	$cfg[db_name]			= 'music';
	$cfg[db_host]			= 'localhost';
	$cfg[db_user]			= 'root';
	$cfg[db_pass]			= 'root';

	$cfg[file_roots] = array(
		'C:/Documents and Settings/Administrator/Desktop/music',
	);

$_SERVER[REMOTE_USER] = 'cal';

	define('INCLUDE_DIR', dirname(__FILE__));

	include(INCLUDE_DIR.'/lib_db.php');
	include(INCLUDE_DIR.'/lib_misc.php');
?>
