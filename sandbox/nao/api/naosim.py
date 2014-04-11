#!/usr/bin/env python

import rospy, actionlib

from nao_msgs.msg import RunBehaviorAction, RunBehaviorGoal, JointAnglesWithSpeed

from geometry_msgs.msg import Twist

def sleep(t=1.0):
	rospy.sleep(t)

def start():
	global nao_cmd_vel, nao_action_client, nao_angles
	rospy.init_node('nao_controller', anonymous=True)
	nao_cmd_vel = rospy.Publisher('/nao1/cmd_vel', Twist)
	nao_angles = rospy.Publisher('/nao1/joint_angles',JointAnglesWithSpeed)
	nao_action_client = actionlib.SimpleActionClient('/nao1/run_behavior', RunBehaviorAction)
	nao_action_client.wait_for_server()
	sleep(1.0)

def loginfo(s=''):
	rospy.loginfo(s)

def moveHead(pitch,yaw,speed=0.1,relative=1):
        global nao_angles
        msg = JointAnglesWithSpeed()
        msg.joint_names = ['HeadPitch','HeadYaw']
        msg.joint_angles = [pitch, yaw]
        msg.speed = speed
        msg.relative = relative
        nao_angles.publish(msg)

def move(x,y,th):
	global nao_cmd_vel
	vel = Twist()
	vel.linear.x = x
	vel.linear.y = y
	vel.angular.z = th
	nao_cmd_vel.publish(vel)

def stop():
	move(0,0,0)

def stand_up():
	global nao_action_client
	goal = RunBehaviorGoal()
	goal.behavior = 'stand_up'
	nao_action_client.send_goal(goal)
	nao_action_client.wait_for_result(rospy.Duration.from_sec(10.0))

def sit_down():
	global nao_action_client
	goal = RunBehaviorGoal()
	goal.behavior = 'sit_down'
	nao_action_client.send_goal(goal)
	nao_action_client.wait_for_result(rospy.Duration.from_sec(10.0))

