<?
	#
	# $Id$
	#

	####################################################################################################

	#
	# convert a token into a session and store it in the users table
	#

	function lastfm_get_session($token){

		#
		# make the api call
		#

		$ret = lastfm_api_call(array(
			'method'	=> 'auth.getSession',
			'token'		=> $token,
		));

		if (!$ret[ok]) return $ret;

		if ($ret[data][session][name] && $ret[data][session][key]){

			db_update('users', array(
				'lastfm_user'	=> AddSlashes($ret[data][session][name]),
				'lastfm_token'	=> AddSlashes($ret[data][session][key]),
			), "name='cal'");

			return array(
				'ok' => 1,
			);
		}

		return array(
			'ok'	=> 0,
			'error'	=> 'no_session',
			'data'	=> $ret[data],
		);
	}

	####################################################################################################

	function lastfm_api_call($args){

		#
		# extra args
		#

		$args[format] = 'json';
		$args[api_key] = $GLOBALS[cfg][lastfm_apikey];


		#
		# build signature
		#

		ksort($args);
		$base = '';
		foreach ($args as $k => $v){
			if ($k == 'format') continue;
			if ($k == 'callback') continue;
			$base .= $k.$v;
		}
		$args[api_sig] = md5($base.$GLOBALS[cfg][lastfm_secret]);


		#
		# build URL
		#

		$bits = array();
		foreach ($args as $k => $v){
			$bits[] = urlencode($k).'='.urlencode($v);
		}

		$url = 'http://ws.audioscrobbler.com/2.0/?'.implode('&', $bits);


		#
		# fetch it
		#

		$ret = http_get($url);

		if (!$ret) return $ret;


		#
		# parse
		#

		$obj = json_decode($ret[data], true);

		if ($obj){
			if ($obj[error]){
				return array(
					'ok'	=> 0,
					'error'	=> 'api_error',
					'data'	=> $obj,
				);
			}

			return array(
				'ok' => 1,
				'data' => $obj,
			);
		}

		return array(
			'ok'	=> 0,
			'error'	=> 'bad_json',
			'data'	=> $ret[data],
		);
	}

	####################################################################################################
?>