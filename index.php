<?
	include('include/init.php');

	header('Content-type: text/html; charset=UTF-8');

	$user = 'cal';
	$auth = auth_create_token($user);
?>
<html>
<head>
	<title>Player</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<link rel="stylesheet" type="text/css" media="all" href="css/player.css?<?=time()?>" />
	<link rel="stylesheet" type="text/css" media="all" href="sm2/flashblock.css" />

	<script type="text/javascript" src="sm2/soundmanager2.js"></script>
	<script type="text/javascript" src="sm2/flashblock.js"></script>
	<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="js/core.js?<?=time()?>"></script>
	<script type="text/javascript"> var g_user = '<?=$user?>'; var g_auth = '<?=$auth?>'; </script>

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

<div id="progress"><img src="images/zoom-spin-1.png" id="progress-img"></div>

<div id="info-dialog">
<div class="inner">

	<table>
		<tr>
			<td>Title:</td>
			<td><input type="text" id="edit-track" style="width: 300px;" value="" /></td>
		</tr>
		<tr>
			<td>Artist:</td>
			<td><input type="text" id="edit-artist" style="width: 300px;" value="" /></td>
		</tr>
		<tr>
			<td>Album:</td>
			<td><input type="text" id="edit-album" style="width: 300px;" value="" /></td>
		</tr>
		<tr>
			<td>Track #:</td>
			<td><input type="text" id="edit-num" style="width: 30px;" value="" /></td>
		</tr>
	</table>

	<input type="button" value="Save Changes" onclick="doneEditTrack();" />
	<input type="button" value="Cancel" onclick="$('#info-dialog').hide();" />

</div>
</div>

<div id="contextmenu"></div>

<div id="dragbox"></div>

<div id="sm2-container" class="swf-default"></div>


<script>
updateVolumePos();
getPlaylists();
getTracks();
</script>

</body>
</html>
