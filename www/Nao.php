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
		
	<script src="js/codemirror.js"></script>
	<script src="./mode/python/python.js"></script>
	
	<script src="js/Nav2D.js"></script>
	<script src="js/navigator/Navigator.js"></script>
	<script src="js/navigator/OccupancyGridClientNav.js"></script>
	
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

	var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("moduleCode"),{
		lineNumbers : true
	});
	
	$('.CodeMirror').resizable({
	  resize: function() {
	    myCodeMirror.setSize("100%", Math.max(400,$(this).height()));
	  }
	});
	
	var runscServer  = '/nao_run_script';
	var runscAction  = '/rpn/RunScriptAction';
	var runningGoal;
	
	var runscClient = new ROSLIB.ActionClient({
		ros : ros,
		serverName : runscServer,
		actionName : runscAction
	});
	
	$( "#moduleRun" ).button();
	$( "#moduleStop" ).button();
	$( "#moduleStop" ).button('disable');
	
	$( "#moduleStop" ).on( "click", function( event, ui ) {
		runningGoal.cancel();
	} );
	
	$( "#moduleRun" ).on( "click", function( event, ui ) {
		$( "#moduleRun" ).button('disable');
		$( "#moduleStop" ).button('enable');
		  var goal = new ROSLIB.Goal({
			actionClient : runscClient,
			goalMessage : {
				name : 'naoscript',
				code : myCodeMirror.getValue(),
				user_id : user_id
			}
		});
		goal.on('feedback', function(feedback) {});
		goal.on('result', function(result) {
			var filename = result.name
			document.getElementById("moduleOutput").value += result.output;
			$( "#moduleRun" ).button('enable');
			$( "#moduleStop" ).button('disable');
		});
		goal.send();
		runningGoal = goal;
	} );
	
	$( "#clearOutput" ).button();
	$( "#clearOutput" ).on( "click", function( event, ui ) {
		document.getElementById("moduleOutput").value = ""
	});

});



	</script>
</head>

<body>
	<table>
		<tr>
			<td>
			</td>
			<td>Python script:
			</td>
		</tr>
		<tr>
			<td>				
				<div id="mjpeg"></div>
			</td>
			<td bgcolor="#000000" >
				<textarea  rows="40" cols="50" id="moduleCode">
#!/usr/bin/env python
from api.nao import *
start()

# Write your commands here

moveHead(0.0,0.0)
sleep(1.0)

</textarea>
			</td>
		</tr>
		<tr>
		<td align="center">
			<div id="moduleRun">Run</div>
			<div id="moduleStop">Stop</div>
		</td>
		<td >Output:<br>
			<textarea readonly rows="10" cols="50" style="overflow:auto;resize:vertical" id="moduleOutput"></textarea>
	</td>
	</tr>
	<tr>
		<td>
		</td>
		<td align="center">
			<div id="clearOutput">Clear output</div>
		</td>
	</tr>
	</table>
</body>

</html>
