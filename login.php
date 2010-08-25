<?
	#
	# $Id$
	#

	include('include/init.php');

	auth_ensure_loggedout();


	#
	# are we logging in?
	#

	$u = trim($_POST[username]);
	$p = trim($_POST[password]);
	$bad_login = 0;

	if (strlen($u) && strlen($p)){

		$u_enc = AddSlashes($u);
		$row = db_fetch_hash(db_query("SELECT * FROM users WHERE name='$u_enc'"));

		if (auth_compare_password($p, $row[password])){

			auth_set_login($row);

			header("location: ./");
			exit;
		}


		#
		# this is for boot strapping - create a user row, try and login with the password
		# you want, and then copy the shown hash into the password field in the database.
		#

		echo "Bootstrap hash: ".auth_hash_password($p);

		$bad_login = 1;
	}


	#
	# show login page
	#
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html>
<head>
	<title>iTunes Server - Login</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>

#login {
	margin: 20% auto 0 auto;
	width: 150px;
	border: 2px solid #666;
	padding: 1em;
}

</style>
</head>
<body>


	<div id="login">
<? if ($cfg[allow_authed_users]){ ?>

		<form action="login.php" method="post">
	<? if ($bad_login){ ?>
			LOGIN ERROR<br />
	<? } ?>
			Username: <input type="text" name="username" /><br />
			Password: <input type="password" name="password" /><br />
			<input type="submit" value="Log In" />
		</form>

	<? if ($cfg[allow_anon_users]){ ?>
		Or <a href="./">use anonymously</a>.
	<? } ?>
<? }else{ ?>
	<? if ($cfg[allow_anon_users]){ ?>
		You can only <a href="./">use this anonymously</a>.
	<? }else{ ?>
		Autnenticated and anonymous access are both turned off. Oops!
	<? } ?>
<? } ?>
	</div>


</body>
</html>