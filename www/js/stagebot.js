var StageBot = (function() {

	var StageBot = function(options) {
		var that = this;
		options = options || {};
		that.ros     = options.ros;
		that.name    = options.name;
		that.context = options.context;
		that.background = options.background;
		that.sim = options.sim;
		that.index = options.index;
		that.mpix = options.mpix;
		that.killed = options.killed;
		that.x = null;
		that.y = null;
		that.th = null;
		that.ranger = null;
		that.trailX = new Array();
		that.trailY = new Array();
		that.trailTH = new Array();
		that.image = new Image();
		that.image.src = 'images/'+options.image;
		that.draw();

		that.rangerListener = new ROSLIB.Topic({
			ros : that.ros,
			name : '/' + that.name + '/base_scan',
			messageType : 'sensor_msgs/LaserScan'
		});

		that.rangerListener.subscribe(function(message) {
			that.ranger = new Array();
			for (var i=0;i<12;i++)
				that.ranger.push(message.ranges[i]);
		});
		
		that.poseListener = new ROSLIB.Topic({
			ros : that.ros,
			name : '/' + that.name + '/pose',
			messageType : 'geometry_msgs/Pose2D'
		});
		
		that.poseListener.subscribe(function(message) {
			that.x = message.x;
			that.y = message.y;
			that.th = message.theta;
			if (that.trailX.length==0) {
				that.trailX.push(that.x);
				that.trailY.push(that.y);
				that.trailTH.push(that.th);
			} else {
				//var lastPos = that.trailX.length - 1;
				//if (that.x!=that.trailX[lastPos] || that.y!=that.trailY[lastPos] || that.th!=that.trailTH[lastPos]) {
					that.trailX.push(that.x);
					that.trailY.push(that.y);					
					that.trailTH.push(that.th);
					if (that.trailX.length>50) {
						that.trailX.shift();
						that.trailY.shift();
						that.trailTH.shift();
					}
				//}
			}
			//if (that.index==0) {
			if (that.index==that.sim.bot.length-1) {
				that.sim.draw();
			}
			//console.log('x:'+that.x+ ' y:'+that.y);
		});
	};
	
	StageBot.prototype.draw = function() {
		
		//this.context.clearRect(0,0,this.context.canvas.width,this.context.canvas.height);
		var bw = this.background.width;
		var bh = this.background.height;
		var cw = this.context.canvas.width; // square canvas, width=height
		var ratio = bh/bw;
		//this.context.drawImage(this.background,0,cw*(1-ratio)/2,cw,cw*ratio);
		var scale = cw/bw;
		
		var mpix = this.mpix/scale;
		var imageWidth  = 0.25/mpix;
		var imageHeight = 0.25/mpix;
		
		if($("#dispTrail").is(':checked')){
			for (var i=0;i<this.trailX.length-1;i++) {
			    if (this.trailX[i]!=this.trailX[i+1] || this.trailY[i]!=this.trailY[i+1] || this.trailTH[i]!=this.trailTH[i+1]) {
				//x = -this.trailY[i] / mpix + cw/2;
				//y = -this.trailX[i] / mpix + cw/2;
				x =  this.trailX[i] / mpix + cw/2;
				y = -this.trailY[i] / mpix + cw/2;
				this.context.save();
				this.context.globalAlpha = i/this.trailX.length/2;
				this.context.translate(x,y);
				this.context.rotate(-this.trailTH[i]);
				this.context.drawImage(
					this.image,
					-(imageWidth / 2),
					-(imageHeight / 2),
					imageWidth,
					imageHeight
				);
				this.context.restore();
			    }
			}	
		}
		this.context.globalAlpha = 1.0;
		this.context.save();
		//var x = -this.y / mpix + cw/2;
		//var y = -this.x / mpix + cw/2;
		var x =  this.x / mpix + cw/2;
		var y = -this.y / mpix + cw/2;
		if ((x!=null)&&(y!=null)) {
			this.context.translate(x, y);
			this.context.rotate(-this.th);
			if($("#dispData").is(':checked')){
			if (this.ranger!=null) {
				var ctx = this.context;
				for (var i=0;i<12;i++) {
					ctx.beginPath();
					ctx.fillStyle="rgba(136, 255, 255, 0.5)";
					ctx.moveTo(0,0);
					//ctx.lineTo(Math.sin(i*Math.PI/6-Math.PI/36)*this.ranger[i]/mpix,Math.cos(i*Math.PI/6-Math.PI/36)*this.ranger[i]/mpix);
					//ctx.lineTo(Math.sin(i*Math.PI/6)*this.ranger[i]/mpix,Math.cos(i*Math.PI/6)*this.ranger[i]/mpix);
					//ctx.lineTo(Math.sin(i*Math.PI/6+Math.PI/36)*this.ranger[i]/mpix,Math.cos(i*Math.PI/6+Math.PI/36)*this.ranger[i]/mpix);
					ctx.lineTo(-Math.cos(i*Math.PI/6-Math.PI/36)*this.ranger[i]/mpix, Math.sin(i*Math.PI/6-Math.PI/36)*this.ranger[i]/mpix);
					ctx.lineTo(-Math.cos(i*Math.PI/6)*this.ranger[i]/mpix, Math.sin(i*Math.PI/6)*this.ranger[i]/mpix);
					ctx.lineTo(-Math.cos(i*Math.PI/6+Math.PI/36)*this.ranger[i]/mpix, Math.sin(i*Math.PI/6+Math.PI/36)*this.ranger[i]/mpix);
					ctx.fill();
				}
			}
			}
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
	return StageBot;
}());

