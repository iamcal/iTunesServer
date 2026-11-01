<?php
	#
	# $Id$
	#

	db_connect();

	#################################################################

	function db_connect() {

		# errors come back through db_error(), as they did with the mysql extension, not as exceptions
		mysqli_report(MYSQLI_REPORT_OFF);

		$GLOBALS['cfg']['db_conn'] = @mysqli_connect($GLOBALS['cfg']['db_host'] ?? null, $GLOBALS['cfg']['db_user'] ?? null, $GLOBALS['cfg']['db_pass'] ?? null);

		if ($GLOBALS['cfg']['db_conn']) {
			@mysqli_select_db($GLOBALS['cfg']['db_conn'], $GLOBALS['cfg']['db_name'] ?? '');

			# the mysql extension's default, which the stored file paths were written through
			@mysqli_set_charset($GLOBALS['cfg']['db_conn'], 'latin1');
			return 1;
		}

		return 0;
	}

	#################################################################

	function db_query($qstring) {

		if ($_GET['debugsql'] ?? null){
			echo "QUERY: ".HtmlSpecialChars($qstring)."<br />\n";
		}

		if (!$GLOBALS['cfg']['db_conn']){
			echo "<p>No MySQL conenction.</p>";
			return;
		}

		$result = @mysqli_query($GLOBALS['cfg']['db_conn'], $qstring);

		if (!$result){
			echo "<p>".db_error()."</p>";
		}else{
			return $result;
		}
	}

	#################################################################

	function db_insertid() {
		return $GLOBALS['cfg']['db_conn'] ? mysqli_insert_id($GLOBALS['cfg']['db_conn']) : 0;
	}

	#################################################################

	function db_error() {
		return $GLOBALS['cfg']['db_conn'] ? mysqli_error($GLOBALS['cfg']['db_conn']) : mysqli_connect_error();
	}

	#################################################################

	function db_insert($tbl, $hash){
		$fields = array_keys($hash);
		db_query("INSERT INTO $tbl (`".implode('`,`',$fields)."`) VALUES ('".implode("','",$hash)."')");
		return db_insertid();
	}

	function db_insert_on_dupe($tbl, $hash, $hash2){
		$fields = array_keys($hash);

		$bits = array();
		foreach(array_keys($hash2) as $k){
			$bits[] = "`$k`='$hash2[$k]'";
		}

		db_query("INSERT INTO $tbl (`".implode('`,`',$fields)."`) VALUES ('".implode("','",$hash)."') ON DUPLICATE KEY UPDATE ".implode(', ',$bits));
		return db_insertid();
	}

	#################################################################

	function db_update($tbl, $hash, $where){
		$bits = array();
		foreach(array_keys($hash) as $k){
			$bits[] = "`$k`='$hash[$k]'";
		}
		db_query("UPDATE $tbl SET ".implode(', ',$bits)." WHERE $where");
	}

	#################################################################

	function db_num_rows($qhandle){

		if ($qhandle instanceof mysqli_result){
			return mysqli_num_rows($qhandle);
		}else{
			return 0;
		}
	}

	#################################################################

	function db_fetch_hash($qhandle){
		return $qhandle instanceof mysqli_result ? mysqli_fetch_array($qhandle, MYSQLI_ASSOC) : false;
	}

	function db_fetch_list($qhandle){
		return $qhandle instanceof mysqli_result ? mysqli_fetch_array($qhandle, MYSQLI_NUM) : false;
	}

	#################################################################

	function db_escape_like($string){
		return str_replace(array('%','_'), array('\\%','\\_'), $string);
	}

	#################################################################

	function db_escape_rlike($string){
		return preg_replace("/([.\[\]*^\$()])/", '\\\$1', $string);
	}

	#################################################################

	function db_fetch_one($sql){
		return db_fetch_hash(db_query($sql));
	}

	#################################################################

	function db_fetch_all($sql){
		$out = array();
		$result = db_query($sql);
		while($row = db_fetch_hash($result)){
			$out[] = $row;
		}
		return $out;
	}

	#################################################################
?>
