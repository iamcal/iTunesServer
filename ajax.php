<?
	include('include/init.php');


	#
	# returns (a portion of) a playlist
	#

	if ($_REQUEST[q] == 'get_tracks'){

		$start = intval($_REQUEST[start]);
		$num = 500;

		$where = "1";
		if ($_REQUEST[s]){
			$s = "'%".db_escape_like(AddSlashes($_REQUEST[s]))."%'";
			$where = "(t.artist LIKE $s OR t.album LIKE $s OR t.track LIKE $s)";
		}

		$select = "t.*";
		$tables = "tracks AS t";
		$order = "t.artist ASC, t.album ASC, t.num ASC, t.track ASC";

		if ($_REQUEST[l]){
			$l = $_REQUEST[l];
			$select .= ", l.id AS uid";
			$tables .= ", playlist_tracks AS l";
			$where .= " AND l.track_id=t.id AND l.playlist_id=".intval($_REQUEST[l]);
			$order = "l.in_order ASC";
		}

		$rows = db_fetch_all("SELECT $select FROM $tables WHERE $where ORDER BY $order LIMIT $start, $num");
		list($total) = db_fetch_list(db_query("SELECT COUNT(t.id) FROM $tables WHERE $where"));
		

		$tracks = array();
		foreach ($rows as $row){

			$uid = "t$row[id]";
			if ($l){
				$uid = "l$row[uid]";
			}

			$tracks[$uid] = array(
				'id' => $row[id],
				'ar' => $row[artist],
				'al' => $row[album],
				'n' => $row[num],
				't' => $row[track],
			);
		}

		exit_with_json(array(
			'ok'		=> 1,
			'start'		=> $start,
			'num'		=> $num,
			'total'		=> intval($total),
			'tracks'	=> $tracks,
		));
	}


	#
	# get the full list of playlists
	#

	if ($_REQUEST[q] == 'get_playlists'){

		$rows = db_fetch_all("SELECT * FROM playlists ORDER BY user ASC, date_create ASC");
		$out = array();
		foreach ($rows as $row){
			$out[$row[user]][$row[id]] = $row[name];
		}

		exit_with_json(array(
			'ok'	=> 1,
			'lists'	=> $out,
		));
	}


	#
	# playlist editing
	#

	if ($_REQUEST[q] == 'rename_playlist'){

		$name = AddSlashes($_REQUEST[name]);
		$user = AddSlashes($_SERVER[REMOTE_USER]);
		$id = intval($_REQUEST[id]);

		db_query("UPDATE playlists SET name='$name' WHERE id=$id AND user='$user'");

		exit_with_json(array(
			'ok'	=> 1,
		));
	}

	if ($_REQUEST[q] == 'add_to_playlist'){

		$tracks = explode(',', $_REQUEST[tracks]);

		$playlist = intval($_REQUEST[id]);
		$user = AddSlashes($_SERVER[REMOTE_USER]);

		$list = db_fetch_one("SELECT * FROM playlists WHERE id=$playlist AND user='$user'");
		if (!$list[id]){
			exit_with_json(array(
				'ok'	=> 0,
				'error'	=> "list not found",
			));
		}

		list($max) = db_fetch_list(db_query("SELECT MAX(in_order) FROM playlist_tracks WHERE playlist_id=$playlist"));

		foreach ($tracks as $id){

			$max++;

			db_insert('playlist_tracks', array(
				'playlist_id'	=> $playlist,
				'track_id'	=> intval($id),
				'in_order'	=> $max,
			));
		}

		exit_with_json(array(
			'ok'	=> 1,
		));
	}

	if ($_REQUEST[q] == 'remove_from_playlist'){

		$tracks = explode(',', $_REQUEST[tracks]);

		$playlist = intval($_REQUEST[id]);
		$user = AddSlashes($_SERVER[REMOTE_USER]);

		$list = db_fetch_one("SELECT * FROM playlists WHERE id=$playlist AND user='$user'");
		if (!$list[id]){
			exit_with_json(array(
				'ok'	=> 0,
				'error'	=> "list not found",
			));
		}

		foreach ($tracks as $id){

			if (preg_match('!^l(\d+)$!', $id, $m)){

				db_query("DELETE FROM playlist_tracks WHERE playlist_id=$playlist AND id=$m[1]");
			}
		}

		exit_with_json(array(
			'ok'	=> 1,
		));
	}



	if ($_REQUEST[q] == 'update_track'){


		db_update("tracks", array(
			'artist'	=> AddSlashes($_REQUEST[ar]),
			'album'		=> AddSlashes($_REQUEST[al]),
			#'year'		=> AddSlashes($_REQUEST[y]),
			'num'		=> AddSlashes($_REQUEST[n]),
			'track'		=> AddSlashes($_REQUEST[t]),
			'updated'	=> 1, # marks pending changes that need to be pushed back to ID3
		), "id=".intval($_REQUEST[id]));


		exit_with_json(array(
			'ok'	=> 1,
		));
	}



	exit_with_json(array(
		'ok' => 0,
		'error' => 'no backend',
	));



	#################################################################################################################################################################

	if ($_REQUEST[q] == 'get_state'){

		$state = get_full_state();
		$current = trim(run_command('tell app "iTunes" to get (name, artist, album) of current track'));

		exit_with_json(array(
			'ok'		=> 1,
			'current'	=> $current,
			'volume'	=> $state[volume],
			'state'		=> $state[state],
			'pos'		=> $state[pos],
			'dur'		=> $state[dur],
		));
	}

	if ($_REQUEST[q] == 'set_volume'){
		run_command('tell app "iTunes" to set sound volume to '.intval($_REQUEST[v]));

		$state = get_full_state();

		exit_with_json(array(
			'ok' 		=> 1,
			'volume'	=> $state[volume],
			'state'		=> $state[state],
			'pos'		=> $state[pos],
			'dur'		=> $state[dur],
		));
	}

	if ($_REQUEST[q] == 'volume_up'){
		run_command('tell app "iTunes" to set sound volume to sound volume + 5');
		$volume = intval(run_command('tell app "iTunes" to sound volume'));
		exit_with_json(array(
			'ok' => 1,
			'volume' => $volume,
		));
	}

	if ($_REQUEST[q] == 'volume_down'){
		run_command('tell app "iTunes" to set sound volume to sound volume - 5');
		$volume = intval(run_command('tell app "iTunes" to sound volume'));
		exit_with_json(array(
			'ok' => 1,
			'volume' => $volume,
		));
	}

	if ($_REQUEST[q] == 'prev'){
		run_command('tell app "iTunes" to previous track');
		exit_with_json(array('ok' => 1));
	}

	if ($_REQUEST[q] == 'next'){
		run_command('tell app "iTunes" to next track');
		exit_with_json(array('ok' => 1));
	}

	if ($_REQUEST[q] == 'play_toggle'){

		run_command('tell app "iTunes" to playpause');
		$state = get_full_state();

		exit_with_json(array(
			'ok' 		=> 1,
			'volume'	=> $state[volume],
			'state'		=> $state[state],
			'pos'		=> $state[pos],
			'dur'		=> $state[dur],
		));
	}

	if ($_REQUEST[q] == 'seek'){

		run_command('tell app "iTunes" to set player position to '.intval($_REQUEST[pos]));
		$state = get_full_state();

		exit_with_json(array(
			'ok' 		=> 1,
			'volume'	=> $state[volume],
			'state'		=> $state[state],
			'pos'		=> $state[pos],
			'dur'		=> $state[dur],
		));
	}

	if ($_REQUEST[q] == 'jump_to'){

		run_command('tell app "iTunes" to reveal track index '.intval($_REQUEST[index]).' of current playlist');
		run_command('tell app "iTunes" to play track index '.intval($_REQUEST[index]).' of current playlist');
		$state = get_full_state();

		exit_with_json(array(
			'ok' 		=> 1,
			'volume'	=> $state[volume],
			'state'		=> $state[state],
			'pos'		=> $state[pos],
			'dur'		=> $state[dur],
		));
	}






	function get_full_state(){

		$bits = explode(', ', trim(run_command('tell app "iTunes" to get (sound volume, player state, player position, duration of current track)')));

		return array(
			'volume'	=> intval($bits[0]),
			'state'		=> $bits[1],
			'pos'		=> intval($bits[2]),
			'dur'		=> intval($bits[3]),
		);
	}


	if ($_REQUEST[q] == 'playlist'){

		exit_with_json(get_playlist_context());
	}

	function get_playlist_context(){

		#
		# get current index
		#

		$ret = execute_script_itunes(
			"set cur_index to index of current track\n".
			"set cur_length to count of tracks of current playlist\n".
			"return ((cur_index as text) & \"/\" & (cur_length as text))"
		);

		if (!$ret[ok]){
			return $ret;
		}

		list($cur, $max) = explode('/', $ret[output]);


		#
		# fetch tracks
		#

		$pre_scope = 10;
		$post_scope = 30;

		$script = "set out to \"\"\n";

		for ($i=$cur-$pre_scope; $i<=$cur+$post_scope; $i++){

			if ($i < 1) continue;
			if ($i > $max) continue;

			$script .= "set temp_name to name of track $i of current playlist\n";
			$script .= "set temp_artist to artist of track $i of current playlist\n";
			$script .= "set temp_album to album of track $i of current playlist\n";
			$script .= "set out to out & \"$i:::\" & temp_name & \":::\" & temp_artist & \":::\" & temp_album & \"\\n\"\n";
		}

		$script .= "return out\n";

		$ret = execute_script_itunes($script);

		if (!$ret[ok]){
			return $ret;
		}

		$lines = explode("\n", $ret[output]);
		$tracks = array();

		foreach ($lines as $line){

			list($index, $name, $artist, $album) = explode(':::', $line);

			$tracks[$index] = array(
				'name'		=> $name,
				'artist'	=> $artist,
				'album'		=> $album,
			);
		}

		$tracks[$cur][current] = 1;

		return array(
			'ok' => 1,
			'tracks' => $tracks,
		);
	}

	if ($_REQUEST[q] == 'search'){

		$term = AddSlashes($_REQUEST[term]);

		$script  = 'set tracks_list to search current playlist for "'.$term.'"'."\n";
		$script .= 'set tracks_ref to a reference to tracks_list'."\n";
		$script .= 'set out to (index of current track as text) & "\\n"'."\n";
		$script .= 'repeat with t in tracks_ref'."\n";

		$script .= "set temp_name to name of t\n";
		$script .= "set temp_artist to artist of t\n";
		$script .= "set temp_album to album of t\n";
		$script .= "set temp_idx to index of t\n";

		$script .= "set out to out & temp_idx & \":::\" & temp_name & \":::\" & temp_artist & \":::\" & temp_album & \"\\n\"\n";

		$script .= 'end repeat'."\n";
		$script .= 'return out'."\n";

		$ret = execute_script_itunes($script);

		if (!$ret[ok]) exit_with_json($ret);

		$lines = explode("\n", $ret[output]);
		$cur = array_shift($lines);

		$tracks = array();

		foreach ($lines as $line){

			list($index, $name, $artist, $album) = explode(':::', $line);

			$tracks[$index] = array(
				'name'		=> $name,
				'artist'	=> $artist,
				'album'		=> $album,
			);
		}

		if ($tracks[$cur]){
			$tracks[$cur][current] = 1;
		}

		exit_with_json(array(
			'ok' => 1,
			'tracks' => $tracks,
		));
	}



	# other commands:
	# play track 13 of user playlist "Sparkle and Fade"
	# http://dougscripts.com/itunes/itinfo/info02.php


	#
	# what playlist are we on?
	# get current playlist => user playlist id 86174
	# get name of user playlist id 86174 => Music
	# get playlists =>
	# library playlist id 56368, user playlist id 86174, user playlist id 110413, user playlist id 110416,
	# user playlist id 85764, user playlist id 154088, user playlist id 85708, user playlist id 153699,
	# user playlist id 80987, user playlist id 85074, user playlist id 85705, user playlist id 85106,
	# user playlist id 85078, user playlist id 140218, user playlist id 138865, user playlist id 153985,
	# user playlist id 153627, user playlist id 121568, user playlist id 110722, user playlist id 112064,
	# user playlist id 110425, user playlist id 127634, user playlist id 138892, user playlist id 153702,
	# user playlist id 153991, user playlist id 154004, user playlist id 206327, user playlist id 127612,
	# user playlist id 153612, user playlist id 153694
	#
	# get special kind of user playlist id 206327 => none / Music / TV Shows



	exit_with_json(array(
		'ok'	=> 0,
		'error'	=> 'Unknown method: '.$_REQUEST[q],
	));
?>
