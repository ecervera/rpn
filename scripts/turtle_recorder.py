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
    turtle = req.name
    modulename = turtle + '_' + time.strftime("%Y%m%d_%H%M%S", time.gmtime())
    filename = rospy.get_param('~bag_folder') + modulename + '.bag'
    rosbag_proc[turtle] = subprocess.Popen(['rosbag','record','rosout',turtle+'/cmd_vel',turtle+'/pose',turtle+'/pen','--duration', '3600', '-O',filename])
    return TurtleStartRecordResponse(0,filename)

def handle_stop(req):
    rospy.loginfo("Stop recording " + req.name)
    turtle = req.name 
    terminate_process_and_children(rosbag_proc[turtle])
    return 0

def turtle_recorder():
    global rosbag_proc
    rospy.init_node('turtle_recorder')
    sr = rospy.Service('~start', TurtleStartRecord, handle_start)
    sp = rospy.Service('~stop', TurtleStopRecord, handle_stop)
    rosbag_proc = {}
    rospy.spin()

if __name__ == "__main__":
    turtle_recorder()
