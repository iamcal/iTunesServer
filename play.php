<?
	include('include/init.php');

	$row = db_fetch_one("SELECT * FROM tracks WHERE id=".intval($_GET[id]));

	$info = pathinfo($row[file]);

	if (is_file($file = utf8_encode($row[file]))){

		switch ($info[extension]){

			case 'mp3':
			case 'm4a':
				header('Content-type: audio/mpeg');
				break;

			case 'html':
				header('Content-type: text/html');
				break;

			case 'jpg':
			case 'jpeg':
				header('Content-type: image/jpeg');
				break;

			default:
				header('Content-type: text/plain');
		}

		$s = stat($file);
		header('Content-length: ' . $s['size']);
		readfile($file);
	}else{

		header('Content-type: text/plain');

		echo "can't find file $file";
	}
?>
