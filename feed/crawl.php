<?
	include('../include/init.php');
	include('../getid3/getid3.php');

	define('GETID3_HELPERAPPSDIR', realpath(GETID3_INCLUDEPATH.'apps').DIRECTORY_SEPARATOR);


	$getID3 = new getID3;

	$rows = db_fetch_all("SELECT * FROM tracks WHERE updated=0");

	foreach ($rows as $row){

		$info = $getID3->analyze($row['file']);

		$tags = array(
			'artist'	=> null,
			'album'		=> null,
			'year'		=> null,
			'num'		=> null,
			'track'		=> null,
		);

		try_set($tags[artist], $info[tags][id3v2][artist]);
		try_set($tags[artist], $info[tags][id3v1][artist]);

		try_set($tags[album], $info[tags][id3v2][album]);
		try_set($tags[album], $info[tags][id3v1][album]);

		try_set($tags[year], $info[tags][id3v2][year]);
		try_set($tags[year], $info[tags][id3v1][year]);

		try_set($tags[num], $info[tags][id3v2][track_number]);
		try_set($tags[num], $info[tags][id3v1][number]);

		try_set($tags[track], $info[tags][id3v2][title]);
		try_set($tags[track], $info[tags][id3v1][title]);
		try_set($tags[track], filename_to_track($row['file']));

		$hash = array();
		foreach ($tags as $k => $v){
			if (isset($v)){
				$hash[$k] = AddSlashes($v);
			}
		}
		if (count($hash)){

			$hash[last_scanned] = time();

			db_update('tracks', $hash, "id=$row[id]");
		}

		echo '. ';
	}

	echo "ok!";

	function filename_to_track($file){

		$bits = explode('/', $file);
		$bits = explode('.', array_pop($bits));
		array_pop($bits);
		return implode('.', $bits);
	}

	function try_set(&$target, $a){

		if (isset($target)) return;

		if (is_array($a)){
			foreach ($a as $x){
				if (strlen($x)){
					$target = $x;
					return;
				}
			}
			return;
		}

		if (strlen($a)){
			$target = $a;
		}
	}

?>