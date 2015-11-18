/*
3DHOP - 3D Heritage Online Presenter
Copyright (c) 2014, Marco Callieri - Visual Computing Lab, ISTI - CNR
All rights reserved.    

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Constructs a TurntableTrackball object.
 * @class Interactor which implements a Turntable controller with bounds.
 */
function TurnTableTrackball() {
}

TurnTableTrackball.prototype = {

	setup : function (options) {
		options = options || {};
		var opt = sglGetDefaultObject({
			startCenter   : [ 0.0, 0.0, 0.0 ],
			startPhi      : 0.0,
			startTheta    : 0.0,
			startDistance : 2.0,
			minMaxDist    : [0.2, 4.0],
			minMaxPhi     : [-180, 180],
			minMaxTheta   : [-80.0, 80.0],
		}, options);
		
		
		this._action = SGL_TRACKBALL_NO_ACTION;
		this._new_action = true;

		this._matrix = SglMat4.identity();

		// trackball center
		this._center = opt.startCenter;
		
		// starting/default parameters
		this._startPhi = sglDegToRad(opt.startPhi);   	//phi (horizontal rotation)
		this._startTheta = sglDegToRad(opt.startTheta);	//theta (vertical rotation)
		this._startDistance = opt.startDistance;   		//distance

		// current parameters
		this._phi = this._startPhi;
		this._theta = this._startTheta;
		this._distance = this._startDistance;
		
		// target paramenters
		this._targetPhi = this._startPhi;
		this._targetTheta = this._startTheta;
		this._targetDistance = this._startDistance; 
		
		//animation data
		this._isAnimating = false;
		this._speedPhi = 0.0;
		this._speedTheta = 0.0;		
		this._speedDistance = 0.0;

		// limits
		this._minMaxDist  = opt.minMaxDist;
		if((opt.minMaxPhi[0] == -180)&&(opt.minMaxPhi[1] == 180))
			this._limitPhi = false;		
		else
			this._limitPhi = true;		
		this._minMaxPhi   = opt.minMaxPhi;
		this._minMaxPhi[0] = sglDegToRad(this._minMaxPhi[0]);
		this._minMaxPhi[1] = sglDegToRad(this._minMaxPhi[1]);
		this._minMaxTheta = opt.minMaxTheta;		
		this._minMaxTheta[0] = sglDegToRad(this._minMaxTheta[0]);
		this._minMaxTheta[1] = sglDegToRad(this._minMaxTheta[1]);
		
		this._start = [0.0, 0.0];
		this.reset();
	},

    clamp: function(value, low, high) {
      if(value < low) return low;
      if(value > high) return high;
      return value;
    },

    _computeMatrix: function() {
      var m = SglMat4.identity();
	  	  
	  // centering
	  m = SglMat4.mul(m, SglMat4.translation([-this._center[0], -this._center[1], -this._center[2]]));	  
	  
	  // zoom
	  m = SglMat4.mul(m, SglMat4.translation([0.0, 0.0, -this._distance]));
	  
  	  // rotation
      m = SglMat4.mul(m, SglMat4.rotationAngleAxis(this._theta, [1.0, 0.0, 0.0]));	  
  
	  // tilt
      m = SglMat4.mul(m, SglMat4.rotationAngleAxis(this._phi, [0.0, -1.0, 0.0]));
	  
      this._matrix = m;	
    },

	getState : function () {
		return [sglRadToDeg(this._phi), sglRadToDeg(this._theta), this._distance];
	},

	setState : function (newstate) {
		// stop animation
		this._isAnimating = false;		
		
		this._phi      = sglDegToRad(newstate[0]);
		this._theta    = sglDegToRad(newstate[1]);		
		this._distance = newstate[2];
		
		//check limits
		if(this._limitPhi)
			this._phi = this.clamp(this._phi, this._minMaxPhi[0], this._minMaxPhi[1]);	  
		this._theta = this.clamp(this._theta, this._minMaxTheta[0], this._minMaxTheta[1]);		
		this._distance = this.clamp(this._distance, this._minMaxDist[0], this._minMaxDist[1]);
		
		this._computeMatrix();		
	},	

	animateToState : function (newstate) {
		// stop animation
		this._isAnimating = false;
		
		this._targetPhi = sglDegToRad(newstate[0]);
		this._targetTheta = sglDegToRad(newstate[1]);
		this._targetDistance = newstate[2];

		//check limits
		if(this._limitPhi)
			this._targetPhi = this.clamp(this._targetPhi, this._minMaxPhi[0], this._minMaxPhi[1]);	  
		this._targetTheta = this.clamp(this._targetTheta, this._minMaxTheta[0], this._minMaxTheta[1]);		
		this._targetDistance = this.clamp(this._targetDistance, this._minMaxDist[0], this._minMaxDist[1]);		
		
		// setting base velocities 
		this._speedPhi = Math.PI;		
		this._speedTheta = Math.PI;	
		this._speedDistance = 2.0;		
		
		//if phi unconstrained rotation, it is necessary to find a good rotation direction
		if(!this._limitPhi){ 
			// clamp current phi angle, to prevent endless unwinding
			while(this._phi > 2*Math.PI)
				this._phi -= 2*Math.PI;
			while(this._phi < 0) 
				this._phi += 2*Math.PI;	
				
			// determine minimal, clamped target phi angle
			var clampedangle = this._targetPhi;
			while(clampedangle > 2*Math.PI)
				clampedangle -= 2*Math.PI;
			while(clampedangle < 0) 
				clampedangle += 2*Math.PI;
			
			if(Math.abs(clampedangle - this._phi) < Math.PI) { // standard rotation
				if(clampedangle > this._phi){
					this.speedphi = Math.PI;		
				}
				else{
					this.speedphi = -Math.PI;		
				}
			}
			else{
				if(clampedangle > this._phi){
					clampedangle = (clampedangle - 2*Math.PI)
					this.speedphi = -Math.PI;		
				}
				else{
					clampedangle = (clampedangle + 2*Math.PI)			
					this.speedphi = Math.PI;		
				}		
			}		

			this._targetPhi = clampedangle;
		}

		// find max animation time to set a time limit and then synchronize all movements		
		var timePhi      = Math.abs((this._targetPhi - this._phi) / this._speedPhi);
		var timeTheta    = Math.abs((this._targetTheta - this._theta) / this._speedTheta);
		var timeDistance = Math.abs((this._targetDistance - this._distance) / this._speedDistance);

		var maxtime = Math.max( timePhi, Math.max( timeTheta, timeDistance ));
		
		if (maxtime > 1.0)
			maxtime = 1.0;
			
		this._speedPhi *= timePhi / maxtime;
		this._speedTheta *= timeTheta / maxtime;
		this._speedDistance *= timeDistance / maxtime;			
		
		// start animation
		this._isAnimating = true;
	},
	
	tick : function (dt) {
		if(!this._isAnimating) return false;
		
		var deltaPhi      = this._speedPhi * dt;
		var deltaTheta    = this._speedTheta * dt;
		var deltaDistance = this._speedDistance * dt;
		
		var diffPhi      = this._targetPhi - this._phi;
		var diffTheta    = this._targetTheta - this._theta;
		var diffDistance = this._targetDistance - this._distance;
				
		if (diffPhi > deltaPhi) 
			this._phi += deltaPhi;
		else if (diffPhi < -deltaPhi) 
			this._phi -= deltaPhi;
		else 
			this._phi = this._targetPhi;			
			
		if (diffTheta > deltaTheta) 
			this._theta += deltaTheta;
		else if (diffTheta < -deltaTheta) 
			this._theta -= deltaTheta;
		else 
			this._theta = this._targetTheta;

		if (diffDistance > deltaDistance) 
			this._distance += deltaDistance;
		else if (diffDistance < -deltaDistance) 
			this._distance -= deltaDistance;
		else 
			this._distance = this._targetDistance;		
			
		if(this._phi == this._targetPhi) 
			if(this._theta == this._targetTheta)
				if(this._distance == this._targetDistance)
					this._isAnimating = false;		
		
		this._computeMatrix();		
		return true;
	},
	
	get action()  { return this._action; },
	
	set action(a) { if(this._action != a) this._new_action = true; this._action = a; },

	get matrix() { return this._matrix; },

	reset : function () {
		this._matrix = SglMat4.identity();
		this._action = SGL_TRACKBALL_NO_ACTION

		this._phi = this._startPhi;
		this._theta = this._startTheta;
		this._distance = this._startDistance;
		
		this._isAnimating = false;		
	},

	track : function(m, x, y, z) {
        if(this._new_action) {
            this._start[0] = x;
		    this._start[1] = y;
            this._new_action = false;
        } 		

        var dx = this._start[0] - x; 
        var dy = this._start[1] - y;
        this._start[0] = x;
        this._start[1] = y;
        
		switch (this._action) {
			case SGL_TRACKBALL_ROTATE:
				this._isAnimating = false; //stopping animation
				this.rotate(m, dx, dy);
			break;

			case SGL_TRACKBALL_PAN:
			break;

			case SGL_TRACKBALL_SCALE:
				this._isAnimating = false; //stopping animation
				this.scale(m, z);
			break;

			default:
			break;
		}
        return this._computeMatrix();
	},
	
    rotate: function(m, dx, dy) {
		this._phi += dx;
		if(this._limitPhi)
			this._phi = this.clamp(this._phi, this._minMaxPhi[0], this._minMaxPhi[1]);	  
		
		// avoid eternal accumulation of rotation, just for the sake of cleanliness
		if (this._phi > 20.0) this._phi = this._phi - 20.0;
		if (this._phi < -20.0) this._phi = this._phi + 20.0;
		
		this._theta += dy; 
		this._theta = this.clamp(this._theta, this._minMaxTheta[0], this._minMaxTheta[1]);
    },

	scale : function(m, s) {
		this._distance *= s;
		this._distance = this.clamp(this._distance, this._minMaxDist[0], this._minMaxDist[1]);		
	}
};
/***********************************************************************/
