<?
	include('../include/init.php');


	#
	# spider each of the paths in our config, looking for files
	#

	foreach ($cfg[file_roots] as $path){
		spider_path($path);
	}

	function spider_path($path){

		$todo = array();

		if (is_dir($path)){
			if ($dh = opendir($path)){
				while (($file = readdir($dh)) !== false){
					if (substr($file, 0, 1) == '.') continue;
					if (is_dir("$path/$file")){
						$todo[] = "$path/$file";
					}
					if (is_file("$path/$file")){
						found_file("$path/$file");
					}
				}
				closedir($dh);
			}
		}

		foreach ($todo as $p){
			spider_path($p);
		}
	}

	function found_file($file){
		if (preg_match('!\.(mp3)$!i', $file)){
			insert_track($file);
			echo '.';
		}
	}

	function insert_track($file){

		$bits = explode('/', $file);
		$bits = explode('.', array_pop($bits));
		array_pop($bits);
		$simple = implode('.', $bits);

		db_insert_on_dupe('tracks', array(
			'track'		=> AddSlashes($simple),
			'file'		=> AddSlashes($file),
			'last_seen'	=> time(),
		), array(
			'last_seen'	=> time(),
		));
	}
?>