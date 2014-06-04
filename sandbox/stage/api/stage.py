#!/usr/bin/env python

import rospy
import math

from geometry_msgs.msg import Twist, PoseStamped 
from sensor_msgs.msg import LaserScan
from nav_msgs.msg import Odometry

def loginfo(s=''):
	rospy.loginfo(s)

def display(s=''):
	loginfo(s)

def sleep(t=0.2,warn=True):
	if warn and t>0.2:
		loginfo('Warning: sleep time greater than watchdog timeout, robot may stop!\n')
	rospy.sleep(t)

def callback_base_scan(data):
	global base_scan_ranges
	base_scan_ranges = data.ranges

def callback_odom(data):
	global odom_pose_pose_position, odom_pose_pose_orientation
	odom_pose_pose_position = data.pose.pose.position
	odom_pose_pose_orientation = data.pose.pose.orientation

def start():
	global cmd_vel_publisher, move_simple_base
	rospy.init_node('stage_controller', anonymous=True)
	cmd_vel_publisher = rospy.Publisher('cmd_vel', Twist)
	rospy.Subscriber("base_scan", LaserScan, callback_base_scan)
	#rospy.Subscriber("odom", Odometry, callback_odom)
	rospy.Subscriber("base_pose_ground_truth", Odometry, callback_odom)
	move_simple_base = rospy.Publisher("move_base_simple/goal", PoseStamped)
	sleep(1.0,False)

def goto(x,y,th):
	ps = PoseStamped()
	ps.header.frame_id = "map"
	ps.header.stamp = rospy.Time.now()
	ps.pose.position.x = x+5.7
	ps.pose.position.y = y+5.4
	ps.pose.position.z = 0
	ps.pose.orientation.x = 0
	ps.pose.orientation.y = 0
	ps.pose.orientation.z = math.sin(th/2)
	ps.pose.orientation.w = math.cos(th/2)
	move_simple_base.publish(ps)

def move(v,w):
	twist = Twist()
	twist.linear.x = v
	twist.angular.z = w
	cmd_vel_publisher.publish(twist)
	if v>1.0 or v<-1.0:
		loginfo("Warning: linear velocity out of bounds! (-1, +1 m/s)\n")
	if w>math.pi/2 or w<-math.pi/2:
		loginfo("Warning: angular velocity out of bounds! (-90, +90 deg/s)\n")

def moveXY(vx,vy,w):
	twist = Twist()
	twist.linear.x = vx
	twist.linear.y = vy
	twist.angular.z = w
	cmd_vel_publisher.publish(twist)

def getPosition():
	position = odom_pose_pose_position
	x = position.x
	y = position.y
	return (x,y)

def getOrientation():
	orientation = odom_pose_pose_orientation
	th = 2*math.atan2(orientation.z,orientation.w)
	return th

def getPose():
	position = odom_pose_pose_position
	orientation = odom_pose_pose_orientation
	x = position.x
	y = position.y
	th = 2*math.atan2(orientation.z,orientation.w)
	return (x,y,th)

def getRanges():
	return base_scan_ranges

BACK = 0
BACK_RIGHT = 1
RIGHT_BACK = 2
RIGHT = 3
RIGHT_FRONT = 4
FRONT_RIGHT = 5
FRONT = 6
FRONT_LEFT = 7
LEFT_FRONT = 8
LEFT = 9
LEFT_BACK = 10
BACK_LEFT = 11

def forward(v):
	move(v,0.0)

def fd(v):
	forward(v)

def backward(v):
	forward(-v)

def bk(v):
	backward(v)
    
def stop():
	forward(0.0)

def turn(w):
	move(0.0,w)
    
def right(w):
	turn(-w)

def rt(w):
	right(w)

def left(w):
	turn(w)

def lt(w):
	left(w)

class Robot:
	def __init__(self,name):
		self.name = name
	def callback_base_scan(self,data):
		self.base_scan_ranges = data.ranges

	def callback_odom(self,data):
		self.odom_pose_pose_position = data.pose.pose.position
		self.odom_pose_pose_orientation = data.pose.pose.orientation

	def start(self):
		self.cmd_vel_publisher = rospy.Publisher(self.name+'/cmd_vel', Twist)
		rospy.Subscriber(self.name+"/base_scan", LaserScan, self.callback_base_scan)
		rospy.Subscriber(self.name+"/base_pose_ground_truth", Odometry, self.callback_odom)

	def move(self,v,w):
		twist = Twist()
		twist.linear.x = v
		twist.angular.z = w
		self.cmd_vel_publisher.publish(twist)

	def stop(self):
		self.move(0,0)

	def getPose(self):
		position = self.odom_pose_pose_position
		orientation = self.odom_pose_pose_orientation
		x = position.x
		y = position.y
		th = 2*math.atan2(orientation.z,orientation.w)
		return (x,y,th)

	def getRanges(self):
		return self.base_scan_ranges

robot_0 = Robot('robot_0')
robot_1 = Robot('robot_1')
robot_2 = Robot('robot_2')

def start_multi():
	global robot_0, robot_1, robot_2
	rospy.init_node('stage_controller', anonymous=True)
	robot_0.start()
	robot_1.start()
	robot_2.start()
	sleep(1.0,False)


