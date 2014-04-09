#!/usr/bin/env python
import rospy
from geometry_msgs.msg import Pose2D
from nav_msgs.msg import Odometry
from math import atan2

def callback(data):
	pose = Pose2D()
	pose.x = data.pose.pose.position.x
	pose.y = data.pose.pose.position.y
	q0 = data.pose.pose.orientation.w
	q1 = data.pose.pose.orientation.x
	q2 = data.pose.pose.orientation.y
	q3 = data.pose.pose.orientation.z
	pose.theta = atan2(2*(q0*q3+q1*q2),1-2*(q2*q2+q3*q3))
	pub.publish(pose)
    
rospy.init_node('robot_pose')
pub = rospy.Publisher('pose', Pose2D)
rospy.Subscriber("base_pose_ground_truth", Odometry, callback)
rospy.spin()