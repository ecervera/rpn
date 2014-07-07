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
// Authentication

	$validSalt = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
        	     'abcdefghijklmnopqrstuvwxyz'.
        	     '0123456789';
    
        $salt = '';
        $valid = strlen($validSalt);
    
        // seed the random number generator
        mt_srand((double)microtime()*1000000);
    
        // grab 16 random characters for our salt
        for ($i = 0; $i < 16; $i++) {
            $salt .= $validSalt[mt_rand(0, $valid-1)];
        }

	$t = time();
        $end = $t + 3600;
    
	$mac = hash(
            'sha512',
            file_get_contents('../config/SecretFile.secret',NULL, NULL, 0, 16).
            $_SERVER['REMOTE_ADDR'].
            $_SERVER['SERVER_NAME'].$salt.$t.
            'user'.$end
        );
?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8" />
	<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" />
	<link rel="stylesheet" href="./css/codemirror.css">
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>

	<script src="js/robotwebtools/EaselJS/current/easeljs.min.js"></script>
	<script src="js/robotwebtools/EventEmitter2/current/eventemitter2.min.js"></script>
	<script src="js/robotwebtools/mjpegcanvasjs/current/mjpegcanvas.min.js"></script>
	<script src="js/robotwebtools/roslibjs/current/roslib.min.js"></script>
	<script src="js/robotwebtools/ros2djs/current/ros2d.min.js"></script>
		
	<script>

        var user_id = "<?php echo $username; ?>" ;

	var rosURL = 'ws://'+location.hostname+':9094';
		
	$(document).ready(function(){
		
		var ros = new ROSLIB.Ros({
			url : rosURL
		});

	// Authentication
	var mac = '<?php echo $mac; ?>';
	var client = "<?php echo $_SERVER['REMOTE_ADDR']; ?>";
	var dest = location.hostname;
	var rand = '<?php echo $salt; ?>';
	var t = <?php echo $t; ?>;
	var level = 'user';
	var end = <?php echo $end; ?>;

	ros.authenticate(mac, client, dest, rand, t, level, end);

	var overlay = document.createElement("canvas");
	overlay.width=320;
	overlay.height=240;
	var ovctx = overlay.getContext("2d");
	ovctx.strokeStyle = "00FF00";

	listenerTracker = new ROSLIB.Topic({
			ros : ros,
			name : '/freezer/nao_camera/object_position',
			messageType : 'geometry_msgs/PoseStamped'
		});
	listenerTracker.subscribe(function(msg) {
		var x = msg.pose.position.x;
		var y = msg.pose.position.y;
		var z = msg.pose.position.z;
		var f = 554;
		var u = f*x/z + 320;
		var v = f*y/z + 240;
		u = u / 2;
		v = v / 2;
		ovctx.clearRect(0,0,320,240);
		ovctx.beginPath();
		ovctx.moveTo(u-5,v);
		ovctx.lineTo(u+5,v);
		ovctx.stroke();
		ovctx.beginPath();
		ovctx.moveTo(u,v-5);
		ovctx.lineTo(u,v+5);
		ovctx.stroke();
	});

    // Create the main viewer.
    var viewer = new MJPEGCANVAS.Viewer({
      divID : 'mjpeg',
      host : 'robotprogramming.uji.es',
      width : 320,
      height : 240,
      topic : '/freezer/nao_camera/image_raw',
      overlay : overlay
    });
});



	</script>
</head>

<body>
				<div id="mjpeg"></div>
</body>

</html>
