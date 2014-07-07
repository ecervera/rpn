<?php 
// Load up the Basic LTI Support code
require_once 'ims-blti/blti.php';

// Initialize, all secrets are 'secret', do not set session, and do not redirect
$context = new BLTI("secret", false, false);
if ( $context->valid ) {
    $username = $context->getUserEmail();
} else {
    exit("<p style=\"color:red\">Could not establish context.<p>\n");
}
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8" />
</head>

<body>
<div style="text-align:center">

	<table>
		<tr>
			<td>		
			<embed type="application/x-vlc-plugin" width="352" height="288" target="rtsp://robotprogramming.uji.es:554/axis-media/media.amp" />
			</td>
		</tr>
	</table>
</div>
</body>

</html>
