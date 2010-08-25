<?

	function http_get($url){

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); # Get around error 417
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS['cfg']['http_timeout']);

		$body = curl_exec($ch);
		$info = curl_getinfo($ch);
		$headers = array();

		curl_close($ch);

	        if ($info['http_code'] != "200"){

			return array(
				'ok'		=> 0,
				'error'		=> 'http_failed',
				'code'		=> $info['http_code'],
				'url'		=> $url,
				'info'		=> $info,
				'headers'	=> $headers,
				'body'		=> $body,
			);
		}

		return array(
			'ok'		=> 1,
			'data'		=> $body,
			'url'		=> $url,
			'info'		=> $info,
			'headers'	=> $headers,
		);
	}
?>