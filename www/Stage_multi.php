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

	<script src="http://cdn.robotwebtools.org/EventEmitter2/current/eventemitter2.min.js"></script>
	<script src="http://cdn.robotwebtools.org/roslibjs/current/roslib.min.js"></script>
		
	<script src="js/codemirror.js"></script>
	<script src="mode/python/python.js"></script>
	
	<script src="js/stagebot.js"></script>
	<script src="js/stagesim.js"></script>
	
	<script>

	function getURLParameter(name) {
		return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
	}

	var user_id = "<?php echo $username; ?>" ;
	var world = getURLParameter('world');

	var rosURL = 'ws://'+location.hostname+':9092';
	var botName = '';

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
		var killBot = new ROSLIB.Service({
			ros : ros,
			name : '/stage_scheduler/release',
			serviceType : 'rpn/Release'
		});
		var request = new ROSLIB.ServiceRequest({
			name : botName
		});
		killBot.callService(request, function(){});
		
		var stopBotRecord = new ROSLIB.Service({
			ros : ros,
			name : '/stage_recorder/stop',
			serviceType : 'rpn/BotStopRecord'
		});
		stopBotRecord.callService(request, function(result){});
	};
		
	$(document).ready(function(){
		document.title = 'Stage robot simulator';

		var context = document.getElementById('world').getContext('2d');
		
		var mpix = 10 / 689;
		if (world=='circular_maze'){
			mpix = 15 / 689;
		}
		var stageSim = new StageSim({
			ros     : ros,
			context : context,
			background : 'images/'+world+'.png',
			mpix : mpix
		});

		var spawnBot = new ROSLIB.Service({
			ros : ros,
			name : '/stage_scheduler/acquire',
			serviceType : 'rpn/Acquire'
		});
	
		var request = new ROSLIB.ServiceRequest({
			world : world
		});
	
		spawnBot.callService(request, function(result){
			botName = result.name;
			
			stageSim.spawnBot(botName+'/robot_0','bot.png');
			stageSim.spawnBot(botName+'/robot_1','bot_green.png');
			stageSim.spawnBot(botName+'/robot_2','bot_yellow.png');
			
			var startBotRecord = new ROSLIB.Service({
				ros : ros,
				name : '/stage_recorder/start',
				serviceType : 'rpn/BotStartRecord'
			});
			var reqrec = new ROSLIB.ServiceRequest({
				name : botName
			});
			startBotRecord.callService(reqrec, function(result){
			});
		});

		var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("moduleCode"),{
			lineNumbers : true
		});
		myCodeMirror.setSize(null,600);

		$('.CodeMirror').resizable({
			resize: function() {
				myCodeMirror.setSize("100%", Math.max(248,$(this).height()));
			}
		});

		var runscServer  = '/stage_run_script';
		var runscAction  = '/rpn/RunScriptAction';
		var runningGoal;
 
		var runscClient = new ROSLIB.ActionClient({
			ros : ros,
			serverName : runscServer,
			actionName : runscAction
		});

		var rosoutSubscriber = new ROSLIB.Topic({
			ros : ros,
			name : 'rosout',
			messageType : 'rosgraph_msgs/Log'
		} );
		rosoutSubscriber.subscribe(function(message) {
			if (message.name.indexOf(botName+'/stage_controller') != -1) {
				document.getElementById("moduleOutput").value += message.msg;
				var textarea = document.getElementById('moduleOutput');
				textarea.scrollTop = textarea.scrollHeight;
			}
		} );
		$( "#moduleRun" ).button();
		$( "#moduleStop" ).button();
		$( "#moduleStop" ).button('disable');
		$( "#dispData" ).button();
		$( "#dispTrail" ).button();
		
		$( "#moduleStop" ).on( "click", function( event, ui ) {
			runningGoal.cancel();
		} );

		$( "#moduleRun" ).on( "click", function( event, ui ) {
			$( "#moduleRun" ).button('disable');
			$( "#moduleStop" ).button('enable');
			var goal = new ROSLIB.Goal({
				actionClient : runscClient,
				goalMessage : {
					name : botName,
					code : myCodeMirror.getValue(),
					user_id : user_id
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
			<canvas id="world" width="600" height="600" style="border: 0px"></canvas>
		</td>
		<td bgcolor="#ffffff" >
<textarea  rows="60" cols="50" id="moduleCode">
#!/usr/bin/env python
from api.stage import *
start_multi()

# Write your commands here

for i in range(10):
    robot_0.move(1,0)
    robot_1.move(1,0)
    robot_2.move(1,0)
    sleep(0.1)
    loginfo('0 x:%.1f y:%.1f th:%.1f\n'%robot_0.getPose())
    loginfo('1 x:%.1f y:%.1f th:%.1f\n'%robot_1.getPose())
    loginfo('2 x:%.1f y:%.1f th:%.1f\n'%robot_2.getPose())

robot_0.stop()
robot_1.stop()
robot_2.stop()

</textarea>
		</td>
	</tr>
	<tr>
		<td align="center">
			<input type="checkbox" id="dispData"><label for="dispData">Data</label>
			<input type="checkbox" id="dispTrail"><label for="dispTrail">Trail</label>
			<div id="moduleRun">Run</div>
			<div id="moduleStop">Stop</div>
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
