#!/usr/bin/env python

import rospy, actionlib

from nao_msgs.msg import RunBehaviorAction, RunBehaviorGoal, JointAnglesWithSpeed

from geometry_msgs.msg import Twist
from std_msgs.msg import String

from naoqi import ALProxy

def laugh():
	aup = ALProxy("ALAudioPlayer","10.1.230.32",9559)
	aup.playFile("/home/nao/sounds/woody-woodpecker-laugh.mp3")

def sleep(t=1.0):
	rospy.sleep(t)

def start():
	global nao_cmd_vel, nao_action_client, nao_angles, nao_speech
	rospy.init_node('nao_chef', anonymous=True)
	nao_cmd_vel = rospy.Publisher('/freezer/cmd_vel', Twist)
	nao_speech = rospy.Publisher('/freezer/speech', String)
	nao_angles = rospy.Publisher('/freezer/joint_angles',JointAnglesWithSpeed)
	nao_action_client = actionlib.SimpleActionClient('/freezer/run_behavior', RunBehaviorAction)
	nao_action_client.wait_for_server()
	sleep(1.0)

def loginfo(s=''):
	rospy.loginfo(s)

def talk(text):
	global nao_speech
	msg = String()
	msg.data = text
	nao_speech.publish(msg)

def moveHead(pitch,yaw,speed=0.1,relative=1):
	global nao_angles
	msg = JointAnglesWithSpeed()
	msg.joint_names = ['HeadPitch','HeadYaw']
	msg.joint_angles = [pitch, yaw]
	msg.speed = speed
	msg.relative = relative
	nao_angles.publish(msg)

def move(v,w):
	global nao_cmd_vel
	vel = Twist()
	if v>1.0:
		v = 1.0
	if v<-1.0:
		v = -1.0
	vel.linear.x = v
	vel.angular.z = w
	nao_cmd_vel.publish(vel)

def stop():
	move(0,0)

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

