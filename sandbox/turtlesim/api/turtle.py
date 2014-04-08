#!/usr/bin/env python

import math, rospy, random

from std_srvs.srv import Empty as EmptyServiceCall
from geometry_msgs.msg import Twist
from turtlesim.srv import TeleportAbsolute, TeleportRelative, SetPen
from rpn.msg import Pen

def dice():
	return random.randint(1,6)

Heads = 0
Tails = 1

def coin():
	return random.choice([Heads,Tails])

def sleep(t=1.0):
	rospy.sleep(t)

def start():
	global turtle_vel, vel, turtle_teleport_absolute, turtle_teleport_relative, turtle_setpen, pen, turtle_pen
	vel = Twist()
	pen = Pen()
	rospy.init_node('turtle_controller', anonymous=True)
	rospy.wait_for_service('teleport_absolute')
	turtle_teleport_absolute = rospy.ServiceProxy('teleport_absolute', TeleportAbsolute)
	rospy.wait_for_service('teleport_relative')
	turtle_teleport_relative = rospy.ServiceProxy('teleport_relative', TeleportRelative)
	rospy.wait_for_service('set_pen')
	turtle_setpen = rospy.ServiceProxy('set_pen', SetPen)
	pen.r = 255
	pen.g = 255
	pen.b = 255
	pen.width = 2
	pen.off = 0
	turtle_vel = rospy.Publisher('cmd_vel', Twist)
	turtle_pen = rospy.Publisher('pen', Pen)
	sleep(0.7)
	setpen()
	sleep(0.3)

Red = (255,0,0)
Green = (0,255,0)
Blue = (0,0,255)
Yellow = (255,255,0)
White = (255,255,255)
Black = (0,0,0)
Cyan = (0,255,255)
Magenta = (255,0,255)

def pen(c):
	penColor(c[0],c[1],c[2])

def setpen():
	global turtle_setpen, pen, turtle_pen
	r = pen.r
	g = pen.g
	b = pen.b
	width = pen.width
	off = pen.off
	turtle_setpen(r,g,b,width,off)
	turtle_pen.publish(pen)
	sleep(0.1)

def penUp():
	global pen
	pen.off = 1
	setpen()

def penDown():
	global pen
	pen.off = 0
	setpen()

def penWidth(width):
	global pen
	pen.width = width
	setpen()

def penColor(r,g,b):
	global pen
	pen.r = r
	pen.g = g
	pen.b = b
	setpen()

def teleportAbsolute(x=0.0,y=0.0,th=0.0):
	turtle_teleport_absolute(x,y,math.radians(th))
	sleep()

def teleportRelative(d=0.0,th=0.0):
	turtle_teleport_relative(d,math.radians(th))
	sleep()

def loginfo(s=''):
	rospy.loginfo(s)

def display(s=''):
	loginfo(s)

def forward(v=1.0,t=1.0):
	vel.linear.x = v
	vel.angular.z = 0.0
	turtle_vel.publish(vel)
	sleep(t)

def fd(v=1.0,t=1.0):
	forward(v,t)

def backward(v=1.0,t=1.0):
	forward(-v,t)

def bk(v=1.0,t=1.0):
	backward(v,t)
    
def turn(a=-90.0,t=1.0):
	vel.linear.x = 0.0
	vel.angular.z = math.radians(a)
	turtle_vel.publish(vel)
	sleep(t)
    
def right(a=90.0,t=1.0):
	turn(-a,t)

def rt(a=90.0,t=1.0):
	right(a,t)

def left(a=90.0,t=1.0):
	turn(a,t)

def lt(a=90.0,t=1.0):
	left(a,t)

def rightArc(a=90.0,r=1.0,t=1.0):
	vel.linear.x = math.radians(a) * r
	vel.angular.z = -math.radians(a)
	turtle_vel.publish(vel)
	sleep(t)

def leftArc(a=90.0,r=1.0,t=1.0):
	vel.linear.x = math.radians(a) * r
	vel.angular.z = math.radians(a)
	turtle_vel.publish(vel)
	sleep(t)

def rightSquare(v=1.0):
	for counter in range(4):
		forward(v)
		right()

def leftSquare(v=1.0):
	for counter in range(4):
		forward(v)
		left()

def square(v=1.0):
	rightSquare(v)

def rightCircle(r=1.0):
	for counter in range(4):
		rightArc(90,r)

def leftCircle(r=1.0):
	for counter in range(4):
		leftArc(90,r)

def circle(r=1.0):
	rightCircle(r)
