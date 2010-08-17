<?
	header('Content-type: text/html; charset=UTF-8');

	$_SERVER[REMOTE_USER] = 'cal';

	if (!$_SERVER[REMOTE_USER]){
		die("Sorry, need to be signed in...");
	}

?>
<html>
<head>
<title>Player</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="sm2/flashblock.css" />
<script type="text/javascript" src="sm2/soundmanager2.js"></script>
<script type="text/javascript" src="sm2/flashblock.js"></script>
<script>

var vol = 100;
var playing_id = null;
var g_tracks = {};
var g_playlists = {};
var g_search = '';
var g_user = '<?=$_SERVER[REMOTE_USER]?>';
var g_list = 0;
var g_renaming_list = 0;

var g_interval = null;
var g_state_at = 0;
var g_song_pos = 0;
var g_song_dur = 0;
var g_song_name = '';

var g_dragging = false;
var g_dragging_target = null;

function ge(x){
	return document.getElementById(x);
}

function escapeXML(s){
	s = ""+s;
	return s.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function playingHTML(){
	return "<img src=\"images/playing.gif\" width=\"13\" height=\"12\" />";
}

function ajaxify(url, args, handler){

	startProgress();

	var req = new XMLHttpRequest();
	req.onreadystatechange = function(){

		var l_f = handler;

		if (req.readyState == 4){

			if (req.status == 200){

				this.onreadystatechange = null;
				eval('var obj = '+req.responseText);
				l_f(obj);
			}else{
				l_f({
					'ok'	: 0,
					'error'	: "Non-200 HTTP status: "+req.status,
					'debug'	: req.responseText
				});
			}

			stopProgress();
		}
	}

	req.open('POST', url, 1);
	//req.setRequestHeader("Method", "POST "+url+" HTTP/1.1");
	req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	var args2 = [];
	for (i in args){
		args2[args2.length] = escape(i)+'='+escape(args[i]);
	}

	req.send(args2.join('&'));
}

function getPlaylists(){

	ajaxify('ajax.php', {'q': 'get_playlists'}, function(o){

		g_playlists = o.lists;
		buildLibrary();
	});

}

function getTracks(start){

	if (!start){
		ge('playlistbody').innerHTML = '';
	}

	startProgress();

	var args = {
		'q'	: 'get_tracks',
		'start'	: start ? start : 0,
		's'	: g_search,
		'l'	: g_list,
	};

	ajaxify('ajax.php', args, function(o){

		buildPlaylist(o);

		ge('status').innerHTML = o.total+' tracks';

		stopProgress();
	});
}

function getState(){
	ajaxify('ajax.php', {'q': 'get_state'}, function(o){

		if (o.ok){
			updatePlayState(o);
			ge('current').innerHTML = escapeXML(o.current);

			if (o.current != g_song_name){

				g_song_name = o.current;
				getPlaylist();
				updateArtwork();
			}
		}
	});
}

function doPrev(){
	var prev = null;
	for (var i in g_tracks){
		if (i == playing_id){
			if (prev){
				playback(prev);
			}else{
				var last = null;
				for (var i in g_tracks){ last = i; }
				playback(last);
			}
			return;
		}
		prev = i;
	}
}

function doNext(){
	var found = false;
	for (var i in g_tracks){
		if (found){
			playback(i);
			return;
		}
		if (i == playing_id){
			found = true;
		}
	}
	for (var i in g_tracks){
		playback(i);
		return;
	}
}

function doPlay(){
	// make sure we have a song
	if (!song){
		findASong();
		return;
	}

	// go!
	song.togglePause();
}

function updatePlayState(o){

	ge('playbtnimg').src = (o.state == 'playing') ? 'images/btn_pause.gif' : 'images/btn_play.gif';

	updatePlaybackHead(o.pos, o.dur);

	if (o.state == 'playing'){
		var remain = o.dur - o.pos;

		g_state_at = new Date().getTime();
		g_song_pos = o.pos;
		g_song_dur = o.dur;

		if (!g_interval){
			g_interval = window.setInterval('playbackInterval()', 1000);
		}
	}else{
		if (g_interval){
			window.clearInterval(g_interval);
			g_interval = null;
		}
	}

	ge('volumeknob').style.left = ((14 - 6) + (74 * o.volume / 100)) + 'px';
}

function updateArtwork(){
	ajaxify('artwork.php', {}, function(o){
		if (o.ok){
			var d = new Date();
			ge('artwork').src = 'artwork.png?cb='+d.getTime();
		}
	});
}

function playbackInterval(){

	var now = new Date().getTime();

	var elapsed = Math.round((now - g_state_at) / 1000);

	var pos = g_song_pos + elapsed;

	if (pos > g_song_dur){

		getState();
		window.clearInterval(g_interval);
		g_interval = null;
	}else{
		updatePlaybackHead(pos, g_song_dur);
	}
}

function updatePlaybackHead(){

	var pos = song.position;
	var dur = getDuration();
	var ldd = song.duration;

	ge('position-done').innerHTML = format_ms(pos);
	ge('position-todo').innerHTML = '-'+format_ms(dur - pos);

	ge('position-inner').style.width = (100 * pos / dur) + '%';

	ge('position-loaded').style.width = (100 * (1 - (ldd / dur)))+'%';
}

function format_ms(ms){

	var s = Math.floor(ms / 1000);

	var m = Math.floor(s / 60);
	s -= m * 60;

	s = ''+s;
	if (s.length == 1) s = '0'+s;

	return m+':'+s;
}

function posClicked(e){
	if (!e) var e = window.event;

	var frac = e.layerX / 300;

	var pos = Math.round(frac * getDuration());

	if (pos > song.duration){

		// we've not buffered this far yet, so don't seek

	}else{
		song.setPosition(pos);
	}
}

function volClicked(e){
	if (!e) var e = window.event;

	var frac = 0;

	if (e.layerX > 14){

		var frac = (e.layerX - 14) / 74;
		if (frac > 1) frac = 1;
	}

	vol = Math.round(100 * frac);

	try {
		soundManager.setVolume('song', vol);
	} catch(e){
	}

	updateVolumePos();
}

function updateVolumePos(){

	ge('volumeknob').style.left = ((14 - 6) + (74 * vol / 100)) + 'px';
}

function getPlaylist(){

	ajaxify('ajax.php', {'q': 'playlist'}, function(o){
		if (o.ok){
			ge('searchinput').value = '';
			buildPlaylist(o);
		}
	});
}

function doSearch(term){
	g_search = term;
	getTracks();
}

var g_loaded = 0;
var g_total = 0;

function buildPlaylist(o){

	g_loaded = o.num + o.start;
	g_total = o.total;

	if (o.start == 0){

		g_tracks = o.tracks;

		ge('playlistbody').innerHTML = buildPlaylistHTML(o);
	}else{

		var html = buildPlaylistHTML(o);

		var note = ge('loadmore');
		note.parentNode.removeChild(note);

		ge('playlistbody').innerHTML += html;

		for (var i in o.tracks){
			g_tracks[i] = o.tracks[i];
		}
	}
}

function loadMore(){

	if (g_loaded < g_total){

		ge('loadmore').innerHTML = '<div>Loading...</div>';

		getTracks(g_loaded);
	}
}


function buildPlaylistHTML(o){

	var html = '';
	var r = 1;

	var keys = [];
	for (var id in o.tracks){ keys[keys.length] = id; }

	for (var i=0; i<keys.length; i++){
		var id = keys[i];

		var class = (r % 2) ? 'row-1' : 'row-2';
		r++;
		if (o.tracks[id].current) class += ' current';

		html += "<tr id=\"track"+id+"\" class=\""+class+"\"";
		html += " onclick=\"select('"+id+"', event); return false\"";
		html += " ondblclick=\"playback('"+id+"'); return false\"";
		html += " onmousedown=\"startTrackDrag(event, '"+id+"'); return false\" onselectstart=\"return false\">\n";

		if (id == playing_id){
			html += "<td id=\"playing"+id+"\">"+playingHTML()+"</td>\n";
		}else{
			html += "<td id=\"playing"+id+"\">&nbsp;</td>\n";
		}

		html += "<td>"+o.tracks[id].id+"</td>\n";
		html += "<td>"+escapeXML(o.tracks[id].t)+"</td>\n";
		html += "<td>"+escapeXML(o.tracks[id].n)+"</td>\n";
		html += "<td>"+escapeXML(o.tracks[id].ar)+"</td>\n";
		html += "<td>"+escapeXML(o.tracks[id].al)+"</td>\n";
		html += "</tr>\n";
	}

	if (o.num + o.start < o.total){

		var got = o.num + o.start;

		var text = "Loaded "+got+" of "+o.total+" tracks. ";

		html += "<tr id=\"loadmore\">\n";
		html += "<td colspan=\"6\" onclick=\"loadMore();\" onmousedown=\"return false\" onselectstart=\"return false\">";
		html += "<div>"+text+"Click to load more</div>";
		html += "</td>\n";
		html += "</tr>\n";
	}

	return html;
}

function select(id, e){
	if (e.shiftKey){
		console.log('yikes!');
	}else if (e.ctrlKey){
		toggleSelection(id);
	}else{
		clearSelection();
		addToSelection(id);
	}
	last_selected = id;
}

var selection = {};
var last_selected = null;

function clearSelection(id){
	var keys = [];
	for (var i in selection){
		keys.push(i);
	}
	for (var i=0; i<keys.length; i++){
		removeFromSelection(keys[i]);
	}
	selection = {};
}

function toggleSelection(id){
	if (selection[id]){
		removeFromSelection(id);
	}else{
		addToSelection(id);
	}
}

function removeFromSelection(id){
	delete selection[id];
	var row = ge('track'+id);
	if (row){
		if (row.className == 'row-1 current'){
			row.className = 'row-1';	
		}else{
			row.className = 'row-2';
		}
	}
}

function addToSelection(id){
	selection[id] = 1;
	var row = ge('track'+id);
	if (row.className == 'row-1'){
		row.className = 'row-1 current';
	}else{
		row.className = 'row-2 current';
	}
}

function inSelection(id){
	return selection[id] ? 1 : 0;
}

function playback(id){
	if (playing_id == id) return;
	if (playing_id){
		var playing = ge('playing'+playing_id);
		if (playing){
			playing.innerHTML = '&nbsp;';
		}
	}
	ge('playing'+id).innerHTML = playingHTML();
	playing_id = id;
	playSong(id);
}

var g_preloadFrame = 1;
var g_progressTimer = null;

function startProgress(){
	if (g_progressTimer) return;
	document.getElementById("progress").style.visibility = "visible";	
	document.getElementById("progress-img").src = 'images/zoom-spin-'+g_preloadFrame+'.png'; 
	g_progressTimer = setInterval("animateProgress()", 100);
}

function animateProgress(){
	g_preloadFrame++;
	if (g_preloadFrame > 12) g_preloadFrame = 1;
	document.getElementById("progress-img").src = 'images/zoom-spin-'+g_preloadFrame+'.png';
}

function stopProgress(){
	document.getElementById("progress").style.visibility = "hidden";
	clearInterval(g_progressTimer);
	g_progressTimer = null;
}


var song = null;
var gotDur = false;

soundManager.url = 'sm2/';
soundManager.debugMode = false;
soundManager.onload = function() {
};

function playSong(idx){

	stopSong();
	gotDur = false;
	useDur = 0;

	var playdata = {
		id: 'song',
		url: 'play.php?id='+g_tracks[idx].id,
		autoPlay: true,
		volume: vol,
		onid3: function(){
			if (this.id3.TLEN){
				gotDur = true;
				useDur = this.id3.TLEN;
			}
		},
		whileplaying: function(){
		        updatePlaybackHead()
		},
		whileloading: function(){
			updatePlaybackHead();
		},
		onplay: function(){
			ge('playbtnimg').src = 'images/btn_pause.gif';
		},
		onresume: function(){
			ge('playbtnimg').src = 'images/btn_pause.gif';
		},
		onpause: function(){
			ge('playbtnimg').src = 'images/btn_play.gif';
		},
		onfinish: function(){
			// play the next
			doNext();
		},
	};
	song = soundManager.createSound(playdata);

	ge('current').innerHTML = escapeXML(g_tracks[idx].ar + ' - ' + g_tracks[idx].t);
}

function getDuration(){
	if (song.bytesLoaded == song.bytesTotal) return song.duration;
	if (gotDur) return useDur;
	return song.durationEstimate;
}

function stopSong(){
	if (song){
		song.destruct();
		song = null;
	}
}

function findASong(){

	for (var i in g_tracks){
		playback(i);
		return;
	}
}

function buildLibrary(){

	var html = '';

	html += '<div class="library">LIBRARY</div>\n';
	html += '<div class="playlist selected" id="music" onclick="loadList(0);"><div class="inner">Music</div></div>\n';

	if (g_user){
		var found = false;
		html += '<div class="library">YOUR PLAYLISTS</div>\n';

		if (g_playlists[g_user]){
			for (var i in g_playlists[g_user]){

				found = true;
				html += '<div class="playlist" id="playlist'+i+'" onclick="loadList('+i+');"><div class="inner">'+escapeXML(g_playlists[g_user][i])+'</div></div>\n';
			}
		}

		if (!found){
			html += '<div class="playlist">none found :(</div>';
		}
	}

	ge('playlists').innerHTML = html;
}

function loadList(x){

	if (x == g_list){
		if (x && x != g_renaming_list){
			// start a rename?
			stopRenamingList();
			startRenamingList(x);
		}
		return;
	}

	stopRenamingList();

	if (g_list){
		ge('playlist'+g_list).className = 'playlist';
	}else{
		ge('music').className = 'playlist';
	}

	g_list = x;

	if (g_list){
		ge('playlist'+g_list).className = 'playlist selected';
	}else{
		ge('music').className = 'playlist selected';
	}

	getTracks();
}

function stopRenamingList(){
	if (!g_renaming_list) return;
	ge('playlist'+g_renaming_list).innerHTML = '<div class="inner">'+escapeXML(getListName(g_renaming_list))+'</div>';
	g_renaming_list = 0;
}

function startRenamingList(id){
	g_renaming_list = id;
	ge('playlist'+id).innerHTML = '<div class="inner"><input id="renamelist'+id+'" type="text" /></div>';
	var edit = ge('renamelist'+id);
	edit.value = getListName(id);
	edit.onkeypress = function(e){
		if (!e) var e = window.event;
		if (e.keyCode == 27){
			stopRenamingList();
		}
		if (e.keyCode == 13 || e.keyCode == 10){
			saveRenamingList();
		}
	}
	edit.focus();
}

function saveRenamingList(){
	var edit = ge('renamelist'+g_renaming_list);
	g_playlists[g_user][g_renaming_list] = edit.value;
	// save value here...
	ajaxify('ajax.php', {
		'q'	: 'rename_playlist',
		'id'	: g_renaming_list,
		'name'	: edit.value,
	}, function(o){
	});
	stopRenamingList();
}

function getListName(id){
	for (var u in g_playlists){
		if (g_playlists[u][id]) return g_playlists[u][id];
	}
	return '?';
}

function startTrackDrag(e, id){

	// check that the track is in the current selection, else we wont
	// try and start a drag.

	if (!inSelection(id)) return;


	// this might be the start of a drag. record mouse
	// position and wait to see if we move enough...

	g_dragging = false;
	g_dragging_target = null;
	g_drag_start = clientToGlobal(eventPoint(e), ge('track'+id));

	document.addEventListener("mousemove", doc_mousemove, false);
	document.addEventListener("mouseup"  , doc_mouseup, false);

	//console.log('starting drag of track '+id+' at', p);
}

function doc_mousemove(e){

	var p = eventPoint(e);

	// are we dragging yet? have we moved far enough?
	if (!g_dragging){
		if (distance_between(p, g_drag_start) <= 10) return;
		g_dragging = true;

		var box = ge('dragbox');
		box.innerHTML = 'Dragging some tracks... '+get_selection_ids();
		box.style.left = (p[0]+10)+'px';
		box.style.top = (p[1]+10)+'px';
		box.style.display = 'block';
	}else{
		var box = ge('dragbox');
		box.style.left = (p[0]+10)+'px';
		box.style.top = (p[1]+10)+'px';
	}

	// find out if we're over a list right now

	var over_playlist = null;

	if (g_playlists[g_user]){
		for (var i in g_playlists[g_user]){

			var list = ge('playlist'+i);

			if (point_within_element(p, list)){

				over_playlist = i;
			}
		}
	}

	if (over_playlist != g_dragging_target){

		if (g_dragging_target){
			dehighlightDropTarget(g_dragging_target);
		}

		if (over_playlist){
			highlightDropTarget(over_playlist);
		}

		g_dragging_target = over_playlist;
	}
}

function highlightDropTarget(i){
	addClass(ge('playlist'+i), 'droptarget');
}

function dehighlightDropTarget(i){
	removeClass(ge('playlist'+i), 'droptarget');
}

function doc_mouseup(e){

	document.removeEventListener("mousemove", doc_mousemove, false);
	document.removeEventListener("mouseup"  , doc_mouseup, false);

	if (g_dragging){
		g_dragging = false;
		ge('dragbox').style.display = 'none';

		if (g_dragging_target){
			dehighlightDropTarget(g_dragging_target);

			if (g_list != g_dragging_target){

				//console.log('dropping tracks into '+g_dragging_target);

				ajaxify('ajax.php', {
					'q'		: 'add_to_playlist',
					'id'		: g_dragging_target,
					'tracks'	: get_selection_ids(),
				}, function(o){
					if (!o.ok) alert('error: '+o.error);
				});
			}
		}
	}
}

function get_selection_ids(){

	var out = [];

	for (var i in selection){
		out.push(g_tracks[i].id);
	}

	return out.join(',');
}

function addClass(elm, c){
	var classes = elm.className.split(' ');
	for (var i=classes.length-1; i>=0; i--){
		if (classes[i]==c) return;
	}
	elm.className += ' '+c;
}

function removeClass(elm, c){
	var out = [];
	var classes = elm.className.split(' ');
	for (var i=classes.length-1; i>=0; i--){
		if (classes[i]!=c) out.push(classes[i]);
	}
	elm.className = out.join(' ');
}

function distance_between(p1, p2){
	var dx = p1[0] - p2[0];
	var dy = p1[1] - p2[1];
	return Math.sqrt(dx*dx + dy*dy);
}

function eventPoint(e){

	if (e.currentTarget == document){
		return  [e.clientX + document.body.scrollLeft, e.clientY + document.body.scrollTop];
	}

	return [e.clientX + document.body.scrollLeft - e.currentTarget.offsetLeft, e.clientY + document.body.scrollTop - e.currentTarget.offsetTop];
}

function clientToGlobal(p, client){

	return [p[0] + client.offsetLeft, p[1] + client.offsetTop];
}

function point_within_element(pt, elm){

	var elm_x = elm.offsetLeft;
	var elm_y = elm.offsetTop;
	var ref = elm;

	while (ref.offsetParent && ref.offsetParent != document.body){
		ref = ref.offsetParent;
		elm_x += ref.offsetLeft;
		elm_y += ref.offsetTop;
	}

	if (pt[0] < elm_x) return false;
	if (pt[1] < elm_y) return false;

	if (pt[0] > elm_x + elm.offsetWidth) return false;
	if (pt[1] > elm_y + elm.offsetHeight) return false;

	return true;
}

function array_keys(a){
	var out = [];
	for (var i in a) out.push(i);
	return out;
}

function deleteElm(e){
	e.parentNode.removeChild(e);
}

document.onkeydown = function(e){

	if (e.keyCode == 46 || e.keyCode == 8){

		if (g_list){

			var gsel = array_keys(selection);

			ajaxify('ajax.php', {
				'q'		: 'remove_from_playlist',
				'id'		: g_list,
				'tracks'	: gsel.join(','),
			}, function(o){
				if (!o.ok){
					alert('error: '+o.error);
					return;
				}
				var lgsel = gsel;
				for (var i=lgsel.length-1; i>=0; i--){
					delete g_tracks[lgsel[i]];

					deleteElm(ge('track'+lgsel[i]));
				}
			});		

		}
	}
};

</script>
<style>

#topbar {
	position: absolute;
	top: 10px;
	left: 10px;
	right: 10px;
	height: 75px;
	background-image: url(images/top_bg.gif);
	background-repeat: repeat-x;
	background-color: #969696;
	border-left: 1px solid #606060;
	border-right: 1px solid #606060;
	border-top: 1px solid #606060;
	border-bottom: 1px solid #6A6C70;
	-moz-border-radius-topleft: 3px;
	-moz-border-radius-topright: 3px;
	-webkit-border-top-left-radius: 3px;
	-webkit-border-top-right-radius: 3px;
	border-top-left-radius: 3px;
	border-top-right-radius: 3px;
}

#play {
	position: absolute;
	left: 60px;
	top: 27px;
}

#prev {
	position: absolute;
	left: 23px;
	top: 30px;
}

#next {
	position: absolute;
	left: 103px;
	top: 30px;
}

a img {
	border: 0;
}

#volumebg {
	position: absolute;
	left: 155px;
	top: 40px;
	width: 107px;
	height: 11px;
	background-image: url(images/volume_bg.gif);
}

#volumeknob {
	position: absolute;
	top: 0px;
	left: 10px;
	background-image: url(images/volume_knob.gif);
	width: 12px;
	height: 12px;
}

#title {
	position: absolute;
	top: 2px;
	left: 10px;
	right: 10px;
	text-align: center;
	font-family: Helvetica, Arial, sans-serif;
	font-weight: bold;
	font-size: 12px;
}

#textbox {
	position: absolute;
	background-color: pink;
	left: 315px;
	top: 22px;
	bottom: 7px;
	right: 234px;
	background-image: url(images/status_bg.gif);
	background-repeat: repeat-x;
	background-color: #DEE1CA;

	/* outer */
	border-left: 1px solid #A6A897;
	border-top: 1px solid #696B5E;
	border-right: 1px solid #A6A997;
	border-bottom: 1px solid #CFCFCF;

	-moz-border-radius: 3px;
	-webkit-border-radius: 3px;

	font-family: Helvetica, Arial, sans-serif;
	font-size: 10px;
}

#search {
	position: absolute;
	width: 148px;
	height: 37px;
	top: 31px;
	right: 23px;
	background-image: url(images/search.gif);
}

#searchinput {
	position: absolute;
	top: 3px;
	left: 24px;
	width: 114px;
	height: 17px;
	border: 0px;
	margin: 0px;
	padding: 0px;
}

#midblock {
	position: absolute;
	left: 10px;
	top: 87px;
	right: 10px;;
	bottom: 39px;
	border-left: 1px solid #606060;
	border-right: 1px solid #606060;
}

#btmbar {
	position: absolute;
	left: 10px;
	right: 10px;
	height: 27px;
	bottom: 10px;
	background-image: url(images/btm_bg.gif);
	background-repeat: repeat-x;
	background-color: #979797;
	border-left: 1px solid #606060;
	border-right: 1px solid #606060;
	border-top: 1px solid #6A6C70;
	border-bottom: 1px solid #606060;
	-moz-border-radius-bottomleft: 3px;
	-moz-border-radius-bottomright: 3px;
	-webkit-border-bottom-left-radius: 3px;
	-webkit-border-bottom-right-radius: 3px;
	border-bottom-left-radius: 3px;
	border-bottom-right-radius: 3px;
}

#sidebar {
	position: absolute;
	left: 0px;
	width: 200px;
	top: 0px;
	bottom: 0px;
	background-color: #D9DEE7;
	border-right: 1px solid #404040;
	overflow: hidden;
}

#artwork {
	position: absolute;
	width: 180px;
	left: 10px;
	top: 10px;
}

#content {
	position: absolute;
	left: 201px;
	right: 0px;
	top: 0px;
	bottom: 0px;
	background-color: #fff;
	overflow: auto;
}

#current {
	position: absolute;
	top: 6px;
	left: 0px;
	width: 100%;
	text-align: center;
	font-size: 14px;
}

#position {
	position: absolute;
	top: 31px;
	left: 50%;
}

#position-done {
	position: absolute;
	top: -2px;
	left: -180px;
	width: 40px;
}

#position-todo {
	position: absolute;
	top: -2px;
	left: 155px;
	width: 40px;
}

#position-outer {
	position: absolute;
	top: 0px;
	left: -150px;
	width: 300px;
	border: 1px solid black;
}

#position-inner {
	width: 0%;
	height: 7px;
	background-image: url(images/progress.gif);
	float: left;
}

#position-loaded {
	width: 0%;
	height: 7px;
	background-color: #ccc;
	float: right;
}

#buttons {
	position: absolute;
	left: 14px;
	top: 4px;
}


table#playlist {
	border-collapse: collapse;
	font-size: 11px;
	font-family: Helvetica, Arial, sans-serif;
	width: 100%;
}

table#playlist td {
	cursor: default;
}

td, th {
	vertical-align: top;
	padding: 2px 4px;
}

th {
	font-weight: bold;
	background-image: url(images/th_bg.gif);
	background-color: #C7C7C7;
	background-repeat: repeat-x;
	border-bottom: 1px solid #555555;
	text-align: left;
	border-right: 1px solid #B2B2B2;
}

td {
	border-right: 1px solid #D9D9D9;
}

tr.row-1 td {
	background: #F1F5FA;
	border-bottom: 1px solid #F1F5FA;
	color: #000000;
}

tr.row-2 td {
	background: #ffffff;
	border-bottom: 1px solid #ffffff;
	color: #000000;
}

tr.current td {
	color: #fff;
	background: #3D80DF;
	border-bottom: 1px solid #7DAAEA;
	border-right: 1px solid #346DBE;
}

tr#loadmore td {
	background: #F1F5FA;
	color: #000000;
	padding: 2px 1px;
	text-align: center;
	border-collapse: separate;
}

tr#loadmore td div {
	padding: 0.6em;
	border: 1px solid #666;
}

#progress {
	position: fixed;
	left: 50%;
	top: 50%;
	visibility: hidden;
}
#progress img {
	position: absolute;
	left: -25px;
	top: -25px;
}

#soundmanager-debug {
	position: absolute;
	left: 100px;
	top: 500px;
	width: 400px;
	height: 200px;
	overflow: auto;
	background-color: pink;
}

.library {
	font-family: Helvetica, Arial, sans-serif;
	font-size: 11px;
	line-height: 20px;
	height: 20px;
	cursor: default;
	padding-left: 10px;
	padding-top: 6px;
	color: #627080;
	font-weight: bold;
}

.playlist {
	color: #000;
	padding-left: 22px;
	font-size: 11px;
	line-height: 20px;
	font-family: Helvetica, Arial, sans-serif;
	height: 20px;
	cursor: default;
	overflow: hidden;
	text-overflow: ellipsis;
}

.playlist.selected {
	background-color: #5594D2;
	background-image: url(images/sidebar_selected.gif);
	background-position: bottom left;
	background-repeat: repeat-x;
	color: #fff;
	font-weight: bold;
}

.playlist .inner {
	background-image: url(images/playlist_unsel.gif);
	background-position: center left;
	background-repeat: no-repeat;
	padding-left: 20px;
	height: 20px;
	text-overflow: ellipsis;
}

.playlist.selected .inner {
	background-image: url(images/playlist_sel.gif);
}

.playlist#music .inner {
	background-image: url(images/library_unsel.gif);
}

.playlist.selected#music .inner {
	background-image: url(images/library_sel.gif);
}

.playlist input {
	height: 18px;
	margin: 1px 0;
	border: 0;
	font-size: 11px;
	font-family: Helvetica, Arial, sans-serif;
}

#status {
	text-align: center;
	font-size: 11px;
	font-family: Helvetica, Arial, sans-serif;
	line-height: 25px;
}

#dragbox {
	display: none;
	position: absolute;
	z-index: 10000;
	border: 1px solid black;
	font-size: 11px;
	font-family: Helvetica, Arial, sans-serif;
	background-color: #fff;
}

.playlist.droptarget {
	color: lime;
}

.playlist.selected.droptarget {
	color: lime;
}

</style>
</head>
<body>

<div id="topbar">
	<div id="title">Player</div>

	<div id="prev"><a href="#" onclick="doPrev(); return false;"><img src="images/btn_prev.gif" width="31" height="32" /></a></div>
	<div id="play"><a href="#" onclick="doPlay(); return false;"><img src="images/btn_play.gif" width="37" height="38" id="playbtnimg" /></a></div>
	<div id="next"><a href="#" onclick="doNext(); return false;"><img src="images/btn_next.gif" width="31" height="32" /></a></div>

	<div id="volumebg" onclick="volClicked(event)">
		<div id="volumeknob"></div>
	</div>

	<div id="textbox">
		<div id="position">
			<div id="position-done">0:00</div>
			<div id="position-outer" onclick="posClicked(event)"><div id="position-inner"></div><div id="position-loaded"></div></div>
			<div id="position-todo">0:00</div>
		</div>
		<div id="current">...</div>
	</div>

	<div id="search">
		<form action="./" method="get" onsubmit="doSearch(ge('searchinput').value); return false">
		<input type="text" id="searchinput" />
		</form>
	</div>
</div>
<div id="midblock">
	<div id="sidebar">

<? if (0){ ?>
		<a href="#" onclick="updateArtwork(); return false;"><img src="artwork.png" id="artwork" /></a>
<? } ?>

		<div id="playlists">
			<!-- stuff will go here -->
		</div>

	</div>
	<div id="content">

		<div id="progress"><img src="images/zoom-spin-1.png" id="progress-img"></div>

		<table id="playlist">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th>GUID</th>
					<th width="33%">Name</th>
					<th>#</th>
					<th width="33%">Artist</th>
					<th width="33%">Album</th>
				</tr>
			</thead>
			<tbody id="playlistbody">
			</tbody>
		</table>

	</div>
</div>
<div id="btmbar">
	<div id="buttons">
		<a href="#" onclick="getState(); return false"><img src="images/sync.gif" width="40" height="20" /></a>
	</div>
	<div id="status"></div>
</div>

<div id="dragbox"></div>

<div id="sm2-container" class="swf-default"></div>


<script>
updateVolumePos();
getPlaylists();
getTracks();
</script>

</body>
</html>
