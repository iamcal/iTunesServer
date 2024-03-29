<?
	include('include/init.php');

	if (!$cfg[allow_anon_users] && !$cfg[user][id]){

		header("location: login.php");
		exit;
	}

	header('Content-type: text/html; charset=UTF-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html>
<head>
	<title>iTunes Server</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<link rel="stylesheet" type="text/css" media="all" href="css/player.css?<?=time()?>" />
	<link rel="stylesheet" type="text/css" media="all" href="sm2/flashblock.css" />

	<script type="text/javascript" src="sm2/soundmanager2.js"></script>
	<script type="text/javascript" src="sm2/flashblock.js"></script>
	<script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
	<script type="text/javascript" src="js/core.js?<?=time()?>"></script>

<script type="text/javascript">
<? if ($cfg[user][id]){ ?>
var g_user = '<?=$cfg[user][name]?>';
var g_auth = '<?=auth_create_token($cfg[user][name])?>';
<? }else{ ?>
var g_user = null;
var g_auth = '<?=auth_create_token('anon')?>';
<? } ?>
</script>

</head>
<body>

<div id="topbar">
	<div id="title">iTunes Server</div>

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
		<a href="#" onclick="updateArtwork(); return false;">ART<img src="artwork.png" id="artwork" /></a>
<? } ?>

		<div id="login">
<? if ($cfg[user][id]){ ?>
			Hello <?=HtmlSpecialChars($cfg[user][name])?> [<a href="logout.php">Logout</a>]<br />
<? }else{ ?>
			Guest [<a href="login.php">Log in</a>]<br />
<? } ?>
		</div>

		<div id="playlists">
			<!-- stuff will go here -->
		</div>

	</div>
	<div id="content">

		<table id="playlist">
			<thead>
				<tr>
					<th id="col-head-num"><div>&nbsp;</div></th>
					<th>GUID</th>
					<th width="33%" id="col-head-track"><div>Name</div></th>
					<th>#</th>
					<th width="33%" id="col-head-artist"><div>Artist</div></th>
					<th width="33%" id="col-head-album"><div>Album</div></th>
				</tr>
			</thead>
			<tbody id="playlistbody">
			</tbody>
		</table>

	</div>
</div>
<div id="btmbar">
	<div id="buttons">
<? if (0){ ?>
		<a href="#"><img src="images/sync.gif" width="40" height="20" /></a>
<? } ?>
	</div>
	<div id="status"></div>
</div>

<div id="progress"><img src="images/zoom-spin-1.png" id="progress-img"></div>

<div class="dialog" id="info-dialog">
<a class="dialog-shade" onclick="$('#info-dialog').hide(); return false;" href="#"></a>
<div class="dialog-inner">

	<h1>Edit Details</h1>

	<div class="dialog-guts">

		<label for="edit-track">Title:</label><br />
		<input type="text" id="edit-track" style="width: 400px;" value="" /><br />

		<label for="edit-artist">Artist:</label><br />
		<input type="text" id="edit-artist" style="width: 400px;" value="" /><br />

		<label for="edit-album">Album:</label><br />
		<input type="text" id="edit-album" style="width: 400px;" value="" /><br />

		<label for="edit-num">Track #:</label><br />
		<input type="text" id="edit-num" style="width: 30px;" value="" /><br />
	</div>

	<div class="dialog-buttons">
		<input type="button" value="Save Changes" onclick="doneEditTrack();" />
		<input type="button" value="Cancel" onclick="$('#info-dialog').hide();" />
	</div>
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
