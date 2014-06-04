#!/usr/bin/env python
import roslib; roslib.load_manifest('rpn')

import rospy
import actionlib

from rpn.msg import RunScriptAction, RunScriptResult

import subprocess, os, signal, time
from datetime import datetime

def terminate_process_and_children(p):
	ps_command = subprocess.Popen("ps -o pid --ppid %d --noheaders" % p.pid, shell=True, stdout=subprocess.PIPE)
	ps_output = ps_command.stdout.read()
	retcode = ps_command.wait()
	assert retcode == 0, "ps command returned %d" % retcode
	for pid_str in ps_output.split("\n")[:-1]:
			os.kill(int(pid_str), signal.SIGINT)
	p.terminate()
    
def change_user():
	#os.setgid(65534)
	#os.setuid(65534)
	pass

class StageServer:
	def __init__(self):
		self.server = actionlib.ActionServer('stage_run_script', RunScriptAction, self.execute, self.cancel, False)
		self.goalHandle = {}
		self.goal = {}
		self.pm = {}
		self.cancelled = {}
		self.executing = {}
		self.filename = {}
		self.result = {}
		self.user_id = {}
                self.bagfile = {}
                self.start_time = {}
		self.server.start()

	def cancel(self, goalHandle):
		goal = goalHandle.get_goal()
		bot = goal.name
		self.cancelled[bot] = True
		terminate_process_and_children(self.pm[bot])
		
	def execute(self, goalHandle):
		goal = goalHandle.get_goal()
		bot = goal.name
		self.goalHandle[bot] = goalHandle
		self.goal[bot] = goal
		self.user_id[bot] = goal.user_id
                self.bagfile[bot] = goal.bagfile
		modulename = goal.name + '_' + time.strftime("%Y%m%d_%H%M%S", time.gmtime())
		self.filename[bot] = '/home/rpn/catkin_ws/src/rpn/sandbox/stage/'+ modulename

		goalHandle.set_accepted()
		
		with open(self.filename[bot]+'.py', "w") as text_file:
			text_file.write(goal.code)
		os.chmod(self.filename[bot]+'.py', 0755)
				
		self.result[bot] = RunScriptResult()
		self.result[bot].name = modulename
		
		my_env = os.environ
		my_env["ROS_NAMESPACE"] = bot
		with open(self.filename[bot]+'.output', "w") as text_file:
			self.pm[bot] = subprocess.Popen('timeout -s 9 900 rosrun rpn sandbox/stage/' + modulename+'.py',
									stdout=text_file,
									stderr=subprocess.STDOUT,
									preexec_fn=change_user,
									env=my_env,
									shell=True)
		self.cancelled[bot] = False
		self.executing[bot] = True
		self.start_time[bot] = datetime.now()
                rospy.loginfo("Running " + modulename + '.py from ' + goal.user_id)
                rospy.loginfo("Bag file: " + goal.bagfile)
    
def poll(event):
	global server
	terminated = []
	for bot in server.executing:
		if server.executing[bot]:
			if server.pm[bot].poll() is None:
				pass
			else:
				server.executing[bot] = False
				server.result[bot].return_value = server.pm[bot].returncode
				with open(server.filename[bot]+'.output', "r") as text_file:
					server.result[bot].output = text_file.read()
				if server.cancelled[bot]:
					server.result[bot].output += 'Aborted!\n'
					server.goalHandle[bot].set_aborted(server.result[bot])
				else:
					server.goalHandle[bot].set_succeeded(server.result[bot])
				rospy.loginfo("Terminated " + server.result[bot].name + '.py from ' + server.user_id[bot])
				terminated.append(bot)
                                # Insert into DB
	for bot in terminated:
                server.executing.pop(bot,None)

if __name__ == '__main__':
	global server
	rospy.init_node('stage_server')
	rospy.loginfo("Starting stage_server...")
	server = StageServer()
	rospy.Timer(rospy.Duration(0.2), poll)
	rospy.spin()
