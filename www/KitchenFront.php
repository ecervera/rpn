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
	<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui
.css" />

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
	
	<script>

		
	$(document).ready(function(){
	
	document.title = "The Humanoid Kitchen";
	
	setInterval(function() {
	    var myImageElement = document.getElementById('kitchenCam3');
	    myImageElement.src = 'http://robotprogramming.uji.es:8088/axis-cgi/jpg/image.cgi?resolution=352x288&rand=' + Math.random();
	}, 1000);

});

	</script>
</head>

<body>
<div style="text-align:center">

	<table>
		<tr>
			<td>				
				<img src="http://robotprogramming.uji.es:8088/axis-cgi/jpg/image.cgi?resolution=352x288" id="kitchenCam3" />
			</td>
		</tr>
	</table>
</div>
</body>

</html>
