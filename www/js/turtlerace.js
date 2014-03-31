var Turtle = (function() {

  var Turtle = function(options) {
    var that = this;
    options = options || {};
    that.ros     = options.ros;
    that.name    = options.name;
    that.context = options.context;
	that.background = options.background;
    that.r = options.r;
    that.g = options.g;
    that.b = options.b;
    that.width = options.width;
    that.off = options.off;

    // Keeps track of the turtle's current position and velocity.
    that.orientation = 0;
    that.angularVelocity = 0;
    that.linearVelocity  = 0;
		that.x = null; //that.context.canvas.width / 2;
		that.y = null; //that.context.canvas.height / 2;
		that.offpath = false;
		that.finish = false;
		that.trailX = new Array();
		that.trailY = new Array();
		that.trailR = new Array();
		that.trailG = new Array();
		that.trailB = new Array();
		that.trailW = new Array();
		
    var turtle_names = ['diamondback.png', 'fuerte.png', 'robot-turtle.png', 'turtle.png', 'box-turtle.png', 'electric.png', 'groovy.png', 'sea-turtle.png'];
    var randomIndex = Math.floor(Math.random() * turtle_names.length);
    var randomTurtle = turtle_names[randomIndex];

    // Represents the turtle as a PNG image.
    that.image = new Image();
    that.image.src = 'images/'+randomTurtle;
    that.draw();

		that.listenerPen = new ROSLIB.Topic({
			ros : that.ros,
			name : '/' + that.name + '/pen',
			messageType : 'r3po/Pen'
		});

		that.listenerPen.subscribe(function(message) {
			that.r = message.r;
			that.g = message.g;
			that.b = message.b;
			that.width = message.width;
			that.off = message.off;
		});
				
		that.listener = new ROSLIB.Topic({
			ros : that.ros,
			name : '/' + that.name + '/pose',
			messageType : 'turtlesim/Pose'
		});
		
		that.listener.subscribe(function(message) {
			that.x = message.x * that.context.canvas.width / 11.08;
			that.y = that.context.canvas.height - message.y * that.context.canvas.height / 11.08;
			that.orientation = message.theta;
			if (that.off==0) {
			if (that.trailX.length==0) {
				that.trailX.push(that.x);
				that.trailY.push(that.y);
				that.trailR.push(that.r);
				that.trailG.push(that.g);
				that.trailB.push(that.b);
				that.trailW.push(that.width);
			} else {
				var lastPos = that.trailX.length - 1;
				if (that.x!=that.trailX[lastPos] || that.y!=that.trailY[lastPos]) {
					that.trailX.push(that.x);
					that.trailY.push(that.y);					
					that.trailR.push(that.r);
					that.trailG.push(that.g);
					that.trailB.push(that.b);
					that.trailW.push(that.width);
				}
			}
		}
			that.draw();
			//console.log('x:'+that.x+ ' y:'+that.y);
			if (that.x>=262 && that.y<=79 && !that.offpath) {
				that.finish = true;
				$( "div#turtleGoal" ).text('Congratulations, you did it!!!');
				stopChr();
			}
		});
  };
	
  Turtle.prototype.draw = function() {
    //this.context.fillStyle = "rgb(69,86,255)"
		//this.context.fillRect(0, 0, this.context.canvas.width, this.context.canvas.height);
    this.context.drawImage(this.background,0,0)

    var x = this.x;
    var y = this.y;
		
		if ((x!=null)&&(x!=null)) {
		  var imgData=this.context.getImageData(this.x, this.y, 1, 1);
			if (!this.finish && imgData.data[0]==153 && imgData.data[1]==102 && imgData.data[2]==51) {
				this.offpath = true;
				$( "div#turtleGoal" ).text('The turtle moved off the path! Try again?');
				stopChr();
			}
    }		
		
    //this.context.fillStyle = "WHITE"
		for (i=0;i<this.trailX.length;i++) {
	    this.context.fillStyle = 'rgb('+this.trailR[i]+','+this.trailG[i]+','+this.trailB[i]+')';
			var w = this.trailW[i];
			this.context.fillRect(this.trailX[i]-w/2, this.trailY[i]-w/2, w, w);			
		}
    this.context.save();
		
		if ((x!=null)&&(x!=null)) {
    var imageWidth  = this.image.width;
    var imageHeight = this.image.height;

    this.context.translate(x, y);
    this.context.rotate(-this.orientation);
    this.context.drawImage(
      this.image,
     -(imageWidth / 2),
     -(imageHeight / 2),
     imageWidth,
     imageHeight
    );
	}
    this.context.restore();
  };
	
  return Turtle;
}());

