define topurg ranger
(
  sensor( 			
    range [ 0.0  30.0 ]
    fov 360
    samples 13
  )
)

define erratic position
(
  size [0.250 0.250 0.250]
  origin [0.000 0.000 0.000 0.000]
  gui_nose 1
  drive "car"
  wheelbase 1.0
  topurg(pose [ 0.050 0.000 0.000 0.000 ])
)

