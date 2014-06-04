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
    p.terminate()

def handle_start(req):
    rospy.loginfo("Start recording " + req.name)
    bot = req.name
    modulename = bot + '_' + time.strftime("%Y%m%d_%H%M%S", time.gmtime())
    filename = rospy.get_param('~bag_folder') + modulename + '.bag'
    rosbag_proc[bot] = subprocess.Popen(['/opt/ros/hydro/bin/rosbag','record','rosout',bot+'/base_pose_ground_truth',bot+'/pose',bot+'/base_scan',bot+'/cmd_vel',bot+'/odom','--duration', '1800', '-O',filename, '/clock:=/'+bot+'/clock'])
    return BotStartRecordResponse(0,filename)

def handle_stop(req):
    rospy.loginfo("Stop recording " + req.name)
    bot = req.name 
    terminate_process_and_children(rosbag_proc[bot])
    return 0

def stage_recorder():
    global rosbag_proc
    rospy.init_node('stage_recorder')
    sr = rospy.Service('~start', BotStartRecord, handle_start)
    sp = rospy.Service('~stop', BotStopRecord, handle_stop)
    rosbag_proc = {}
    rospy.spin()

if __name__ == "__main__":
    stage_recorder()
