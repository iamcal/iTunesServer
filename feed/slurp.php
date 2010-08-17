<?
echo "TRUNCATE cal.tracks;\n";

$handle = @fopen("list.txt", "r");
if ($handle) {
while (!feof($handle)) {
$line = trim(fgets($handle, 4096));
if (preg_match('!\.mp3$!', $line)){

	list($junk, $album, $track) = explode('/', $line, 3);

	$track = substr($track, 0, -4);
	$bits = explode('-', $album);

	if (count($bits) == 1){
		$album = '';
		$artist = $bits[0];
		$year = '';
	}else if (count($bits) == 2){
		$artist = trim($bits[0]);
		$album = trim($bits[1]);
		$year = '';
	}else{
		$artist = trim(array_shift($bits));
		$year = trim(array_shift($bits));
		if (!preg_match('!^\d+$!', $year)){
			array_unshift($bits, $year);
			$year = '';
		}
		$album = implode('-', $bits);
	}

	$num = 0;
	if (preg_match('!^(\d+)[-.\s]+(.*)!', $track, $m)){
		$num = intval($m[1]);
		$track = $m[2];
	}

	echo insertify('cal.tracks', array(
		'artist'	=> AddSlashes($artist),
		'album'		=> AddSlashes($album),
		'year'		=> AddSlashes($year),
		'num'		=> AddSlashes($num),
		'track'		=> AddSlashes($track),
		'file'		=> AddSlashes($line),
	)).";\n";

	flush();

}
}
fclose($handle);
}


	function insertify($tbl, $hash){
		$fields = array_keys($hash);
		return "INSERT INTO $tbl (`".implode('`,`',$fields)."`) VALUES ('".implode("','",$hash)."')";
	}
?>
