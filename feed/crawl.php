<?php
	include('../include/init.php');
	include('../vendor/autoload.php');


	$getID3 = new \JamesHeinrich\GetID3\GetID3;

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

		try_set($tags['artist'], $info['tags']['id3v2']['artist'] ?? null);
		try_set($tags['artist'], $info['tags']['id3v1']['artist'] ?? null);

		try_set($tags['album'], $info['tags']['id3v2']['album'] ?? null);
		try_set($tags['album'], $info['tags']['id3v1']['album'] ?? null);

		try_set($tags['year'], $info['tags']['id3v2']['year'] ?? null);
		try_set($tags['year'], $info['tags']['id3v1']['year'] ?? null);

		try_set($tags['num'], $info['tags']['id3v2']['track_number'] ?? null);
		try_set($tags['num'], $info['tags']['id3v1']['track_number'] ?? null);

		try_set($tags['track'], $info['tags']['id3v2']['title'] ?? null);
		try_set($tags['track'], $info['tags']['id3v1']['title'] ?? null);
		try_set($tags['track'], filename_to_track($row['file']));

		$hash = array();
		foreach ($tags as $k => $v){
			if (isset($v)){
				$hash[$k] = AddSlashes($v);
			}
		}
		if (count($hash)){

			$hash['last_scanned'] = time();

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
				if (strlen($x ?? '')){
					$target = $x;
					return;
				}
			}
			return;
		}

		if (strlen($a ?? '')){
			$target = $a;
		}
	}

?>