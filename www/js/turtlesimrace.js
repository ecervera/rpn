var TurtleSimRace = (function() {

  var TurtleSimRace = function(options) {
    var that = this;
    options = options || {};
    this.ros     = options.ros;
    this.context = options.context;
		this.background = options.background;
    this.turtle  = null;
		
  };

  TurtleSimRace.prototype.spawnTurtle = function(name) {
    var that = this;
    var initialPose = {
      x : that.context.canvas.width / 2
    , y : that.context.canvas.height / 2
    };

    that.turtle = new Turtle({
      name    : name,
      ros     : that.ros,
      pose    : initialPose,
      context : that.context,
			background : that.background,
			r : 255,
			g : 255,
			b : 255,
			width : 2,
			off : 0
    });

  };

  TurtleSimRace.prototype.draw = function() {
    //this.context.fillStyle = "rgb(69,86,255)"
    //this.context.fillRect(0, 0, this.context.canvas.width, this.context.canvas.height);
    this.context.drawImage(this.background,0,0)
		this.turtle.draw();
  };

  return TurtleSimRace;
}());

