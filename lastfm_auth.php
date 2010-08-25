<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# exchange the token for a session key (and store it)
	#

	$ret = lastfm_get_session($_GET[token]);

	if (!$ret[ok]){
		echo "there was a problem!";
		dumper($ret);
		exit;
	}


	#
	# ok, we're good
	#

	header("location: ./");
	exit;
?>