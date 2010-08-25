<?
	#
	# $Id$
	#

	$cfg[user] = null;

	if ($cfg[allow_authed_users]){
		auth_check();
	}

	##############################################################
	#
	# check to see if anyone is authed
	#

	function auth_check(){

		if (!$_COOKIE[u]) return;
		list($id, $hash) = explode('-', $_COOKIE[u]);

		$id = intval($id);
		$row = db_fetch_hash(db_query("SELECT * FROM users WHERE id=$id"));

		if ($row[id] && $row[password] == $hash){

			$GLOBALS[cfg][user] = $row;
		}
	}

	function auth_set_login($row){

		$value = "$row[id]-$row[password]";
		$expire = time() + (10 * 365 * 24 * 60 * 60);

		setcookie('u', $value, $expire, $GLOBALS[cfg][cookie_path], $GLOBALS[cfg][cookie_domain]);
	}

	function auth_logout(){

		$expire = time() - (60 * 60);

		setcookie('u', '0', $expire, $GLOBALS[cfg][cookie_path], $GLOBALS[cfg][cookie_domain]);
	}

	##############################################################

	function auth_hash_password($password){

		$salt = substr(md5(time() . $password), 0, 2);
		return $salt.sha1($password.$salt);
	}

	function auth_compare_password($password, $db_hash){

		#
		# the first 2 bytes are the salt
		#

		$salt = substr($db_hash, 0, 2);
		$hash = substr($db_hash, 2);

		if (sha1($password.$salt) == $hash){

			return true;
		}

		return false;
	}

	##############################################################

	function auth_ensure_loggedout(){

		if ($GLOBALS[cfg][user][id]){
			header('location: ./');
			exit;
		}
	}

	function auth_ensure_loggedin(){

		if (!$GLOBALS[cfg][user][id]){
			header('location: login.php');
			exit;
		}
	}

	##############################################################

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

	##############################################################
?>