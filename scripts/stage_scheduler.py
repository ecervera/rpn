#!/usr/bin/env python

import rospy
from rpn.srv import *
import subprocess, os, signal, time

def terminate_process_and_children(p):
    ps_command = subprocess.Popen("ps -o pid --ppid %d --noheaders" % p.pid, shell=True, stdout=subprocess.PIPE)
    ps_output = ps_command.stdout.read()
    retcode = ps_command.wait()
    assert retcode == 0, "ps command returned %d" % retcode
    for pid_str in ps_output.split("\n")[:-1]:
            os.kill(int(pid_str), signal.SIGINT)
            #print "Killed %d \n" % int(pid_str)
    p.terminate()

def handle_acquire(req):
	global stage_proc, number
	stage = 'stage'+str(number)
	number = number + 1
	world = req.world 
	my_env = os.environ
	my_env["ROS_NAMESPACE"] = stage
	stage_proc[stage] = []
	if world.find('multi')==-1:
		if world.find('nav')==-1:
			world = world + '.world'
			stage_proc[stage].append(subprocess.Popen('rosrun stage_ros stageros -g $(rospack find rpn)/stage/' + world + ' /clock:=clock',
										env=my_env,
										shell=True))
			stage_proc[stage].append(subprocess.Popen('rosrun rpn robot_pose.py /clock:=clock',env=my_env,shell=True))
		else:
			pos = world.find("_")+1
			world = world[pos:] + '/' + world[pos:] + '.launch'
			#world = world[pos:] 
			stage_proc[stage].append(subprocess.Popen('roslaunch $(rospack find rpn)/stage/nav/' + world, env=my_env, shell=True))
			#stage_proc[stage].append(subprocess.Popen('rosrun map_server map_server $(rospack find rpn)/stage/nav/' + world + '/map.yaml',env=my_env,shell=True))
			#stage_proc[stage].append(subprocess.Popen('rosrun rpn robot_pose.py',env=my_env,shell=True))
			#stage_proc[stage].append(subprocess.Popen('rosrun amcl amcl _initial_pose_x:=-1 _initial_pose_y:=-1 _odom_model_type:=omni scan:=base_scan',env=my_env,shell=True))
			#stage_proc[stage].append(subprocess.Popen('rosrun move_base move_base',env=my_env,shell=True))
			#stage_proc[stage].append(subprocess.Popen('rosrun stage_ros stageros -g $(rospack find rpn)/stage/nav/' + world + '/' + world + '.world' + ' /clock:=/'+stage+'/clock', env=my_env,shell=True))
	else:
		world = world + '.world'
		remap =  ' /robot_0/base_pose_ground_truth:=/%s/robot_0/base_pose_ground_truth' % stage
		remap += ' /robot_0/base_scan:=/%s/robot_0/base_scan' % stage
		remap += ' /robot_0/cmd_vel:=/%s/robot_0/cmd_vel' % stage
		remap += ' /robot_0/odom:=/%s/robot_0/odom' % stage
		remap += ' /robot_1/base_pose_ground_truth:=/%s/robot_1/base_pose_ground_truth' % stage
		remap += ' /robot_1/base_scan:=/%s/robot_1/base_scan' % stage
		remap += ' /robot_1/cmd_vel:=/%s/robot_1/cmd_vel' % stage
		remap += ' /robot_1/odom:=/%s/robot_1/odom' % stage
		remap += ' /robot_2/base_pose_ground_truth:=/%s/robot_2/base_pose_ground_truth' % stage
		remap += ' /robot_2/base_scan:=/%s/robot_2/base_scan' % stage
		remap += ' /robot_2/cmd_vel:=/%s/robot_2/cmd_vel' % stage
		remap += ' /robot_2/odom:=/%s/robot_2/odom' % stage
		stage_proc[stage].append(subprocess.Popen('rosrun stage_ros stageros -g $(rospack find rpn)/stage/' + world + ' /clock:=clock' + remap,
										env=my_env,
										shell=True))
		for robot in range(3):		
			my_env["ROS_NAMESPACE"] = stage + ('/robot_%s' % robot)
			stage_proc[stage].append(subprocess.Popen('rosrun rpn robot_pose.py',env=my_env,shell=True))

	#subprocess.Popen('sleep 1; rosparam set /use_sim_time false',shell=True)
	return AcquireResponse(stage)

def handle_release(req):
	global stage_proc, pose_proc
	stage = req.name
	for pid in stage_proc[stage]:
		terminate_process_and_children(pid)
	return 0

def stage_scheduler():
	global stage_proc, pose_proc, number
	rospy.init_node('stage_scheduler')
	sr = rospy.Service('~acquire', Acquire, handle_acquire)
	sp = rospy.Service('~release', Release, handle_release)
	number = 1
	stage_proc = {}
	pose_proc = {}
	rospy.spin()

if __name__ == "__main__":
	stage_scheduler()
