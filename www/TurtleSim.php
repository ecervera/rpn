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
            'sha1',
            file_get_contents('../config/SecretFile.secret',NULL, NULL, 0, 16).
            $_SERVER['REMOTE_ADDR'].
            'localhost'.$salt.$t.
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

	<script src="http://cdn.robotwebtools.org/EventEmitter2/current/eventemitter2.min.js"></script>
	<script src="http://cdn.robotwebtools.org/roslibjs/current/roslib.min.js"></script>
		
	<script src="js/codemirror.js"></script>
	<script src="mode/python/python.js"></script>
	
	<script src="js/turtle.js"></script>
	<script src="js/turtlesim.js"></script>
	
	<script>

        var user_id = "<?php echo $username; ?>" ;
	var bagfile = '';
	
	var rosURL = 'ws://'+location.hostname+':9090';
	var turtleName = '';

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

	window.onbeforeunload=function(){
		var killTurtle = new ROSLIB.Service({
			ros : ros,
			name : '/kill',
			serviceType : 'turtlesim/Kill'
		});

		var request = new ROSLIB.ServiceRequest({
			name : turtleName
		});
	
		killTurtle.callService(request, function(){			
		});

		var stopTurtleRecord = new ROSLIB.Service({
			ros : ros,
			name : '/turtle_recorder/stop',
			serviceType : 'rpn/TurtleStopRecord'
		});

		var reqrec = new ROSLIB.ServiceRequest({
			name : turtleName
		});
		stopTurtleRecord.callService(reqrec, function(result){
		});
	};
		
	$(document).ready(function(){
	
	document.title = 'The Turtle Arena';
	
    var context = document.getElementById('world').getContext('2d');
    var turtleSim = new TurtleSim({
      ros     : ros,
      context : context
    });

		var spawnTurtle = new ROSLIB.Service({
			ros : ros,
			name : '/spawn',
			serviceType : 'turtlesim/Spawn'
		});
	
		var request = new ROSLIB.ServiceRequest({
			x : 5.5,
			y : 5.5,
			theta : 0
		});

		spawnTurtle.callService(request, function(result){
			turtleName = result.name;
			turtleSim.spawnTurtle(turtleName);
			turtleSim.draw();

			var startTurtleRecord = new ROSLIB.Service({
				ros : ros,
				name : '/turtle_recorder/start',
				serviceType : 'rpn/TurtleStartRecord'
			});
	
			var reqrec = new ROSLIB.ServiceRequest({
				name : turtleName
			});
			startTurtleRecord.callService(reqrec, function(result){
				//console.log('Bagfile: '+result.filename);
				bagfile = result.filename;
			});
		});
    
	var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("moduleCode"),{
		lineNumbers : true
	});
	
	$('.CodeMirror').resizable({
	  resize: function() {
	    myCodeMirror.setSize("100%", Math.max(248,$(this).height()));
	  }
	});
	
	var runscServer  = '/turtlesim_run_script';
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
				name : turtleName,
				code : myCodeMirror.getValue(),
				user_id : user_id,
				bagfile : bagfile
			}
		});
		goal.on('feedback', function(feedback) {});
		goal.on('result', function(result) {
			var filename = result.name
			var outputLines = result.output.split(/\r?\n/);
				for(var i=0;i<outputLines.length;i++) {
					if (outputLines[i].indexOf('WallTime') == -1) {
						if (outputLines[i].indexOf('catkin_ws') == -1) {
							if (outputLines[i].length>0) {
							document.getElementById("moduleOutput").value += outputLines[i] + '\n';
							var textarea = document.getElementById('moduleOutput');
							textarea.scrollTop = textarea.scrollHeight;
							}
						} else {
							line = outputLines[i].split('line');
							document.getElementById("moduleOutput").value += 'Line ' + line[1] + '\n';
							var textarea = document.getElementById('moduleOutput');
							textarea.scrollTop = textarea.scrollHeight;
						}
					}
				}
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

	$( "#clearBackground" ).button();
	$( "#clearBackground" ).on( "click", function( event, ui ) {
		turtleSim.turtle.trailX = new Array();
		turtleSim.turtle.trailY = new Array();
		turtleSim.turtle.trailR = new Array();
		turtleSim.turtle.trailG = new Array();
		turtleSim.turtle.trailB = new Array();
		turtleSim.turtle.trailW = new Array();		
		turtleSim.draw();	
	});

	$( "#restartTurtle" ).button();
	$( "#restartTurtle" ).on( "click", function( event, ui ) {
		var resetTurtle = new ROSLIB.Service({
			ros : ros,
			name : '/'+turtleName+'/teleport_absolute',
			serviceType : 'turtlesim/TeleportAbsolute'
		});
	
		var request = new ROSLIB.ServiceRequest({
			x : 5.5,
			y : 5.5,
			theta : 0
		});
	
		resetTurtle.callService(request, function(){	
			turtleSim.turtle.trailX = new Array();
			turtleSim.turtle.trailY = new Array();
			turtleSim.turtle.trailR = new Array();
			turtleSim.turtle.trailG = new Array();
			turtleSim.turtle.trailB = new Array();
			turtleSim.turtle.trailW = new Array();
			turtleSim.draw();	
		});
	});

});



	</script>
</head>

<body>
	<table>
		<tr>
			<td>
			</td>
			<td>Script:
			</td>
		</tr>
		<tr>
			<td>
				<canvas id="world" width="296" height="296" style="border: 0px"></canvas>
			</td>
			<td bgcolor="#000000" >
<textarea  rows="30" cols="50" id="moduleCode">
#!/usr/bin/env python
from api.turtle import *
start()

# Write your commands here

</textarea>
			</td>
		</tr>
		<tr>
			<td align="center">
			<table>
			<tr>
			<td align="right">
			<div id="moduleRun">Run</div>
			</td><td align="left">
			<div id="moduleStop">Stop</div>
			</td></tr>
			<tr><td align="right">
			<div id="clearBackground">Clear</div>
			</td><td align="left">
			<div id="restartTurtle">Reset</div>
			</td></tr>
			</table>
		</td>
		<td >Output:<br>
			<textarea readonly rows="5" cols="50" style="overflow:auto;resize:vertical" id="moduleOutput"></textarea>
	</td>
	</tr>
	<tr>
		<td align="center">
		</td>
		<td align="center">
			<div id="clearOutput">Clear output</div>
		</td>
	</tr>
	</table>
</body>

</html>
