/*************************************************************************/
/*                                                                       */
/*  WebMultiRes                                                          */
/*  JavaScript 3D Graphics Library on top of SpiderGL for web            */
/*  visualization of high resolution RTI images                          */
/*                                                                       */
/*  Copyright (C) 2013-2015                                              */
/*  Gianpaolo Palma                                                      */
/*  Visual Computing Laboratory                                          */
/*  ISTI - Italian National Research Council (CNR)                       */
/*  http://vcg.isti.cnr.it/rti/webviewer.php                             */
/*  mailto: gianpaolo[DOT]palma[AT]isti[DOT]cnr[DOT]it                   */
/*                                                                       */
/*                                                                       */
/*  This program is free software: you can redistribute it and/or modify */
/*  it under the terms of the GNU General Public License as published by */
/*  the Free Software Foundation, either version 3 of the License, or    */
/*  (at your option) any later version.                                  */
/*                                                                       */
/*  This program is distributed in the hope that it will be useful,      */
/*  but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/*  GNU General Public License for more details.                         */
/*                                                                       */ 
/*  You should have received a copy of the GNU General Public License    */
/*  along with this program.  If not, see <http://www.gnu.org/licenses/> */
/*************************************************************************/

function createRtiViewer(idDiv, imageUrlOrFunction, width, height)
{
	var canvasHeight = height;
	var canvasWidth = width;
	var fullscreen = false;
	var canvasContainer = $($("#"+idDiv)[0]);
	var canvasDiv = document.createElement("div");
	canvasDiv.id = idDiv + "_div";
	canvasDiv.style.height = height + "px";
	canvasDiv.style.width = width + "px";
	canvasDiv.style.margin = "auto";
	canvasDiv.style.position = "relative";
	canvasContainer.append(canvasDiv);
	
	var canvasNode = $($("#"+idDiv + "_div")[0]);
	var canvasName = idDiv + "_webgl";
	var canvas = document.createElement("canvas");
	canvas.id = canvasName;
	canvas.width = width;
	canvas.height = height;
	canvasNode.append(canvas);
	
	var aNode = document.createElement("a");
	aNode.href = "http://vcg.isti.cnr.it/rti/webviewer.php";
	aNode.target ="_blank";
	
	var aNodeDiv = document.createElement("div");
	aNodeDiv.style.width = 80 + "px";
	aNodeDiv.style.height = 50 + "px";
	
	aNode.appendChild(aNodeDiv);
	aNode.style.position = "absolute";
	aNode.style.bottom = "0px";
	aNode.style.left = "0px";
	aNode.style.cssFloat = "left";
	canvasNode.append(aNode);
		
	var toolbar = document.createElement("div");
	toolbar.setAttribute("class", "toolbar");
	toolbar.style.position = "absolute";
	toolbar.style.top = "10px";
	toolbar.style.left = "10px";
	toolbar.style.cssFloat = "left";
	toolbar.style.width = "40px";
	toolbar.style.height = "200px";
	toolbar.style.visibility = "hidden";
	toolbar.innerHTML = '<button class = "toolbarButton" id = "zoomIn"></button><button class = "toolbarButton" id = "zoomOut"></button><button class = "toolbarButton" id = "light"></button><button class = "toolbarButton" id = "fullscreen"></button><button class = "toolbarButton" id = "help"></button>'
	canvasNode.append(toolbar);
	
	var divHelp = document.createElement("div");
	divHelp.id = idDiv + "_guide";
	divHelp.style.width = "100%";
	divHelp.style.height = "100%";
	divHelp.style.position = "absolute";
	divHelp.style.left = "0px";
	divHelp.style.top = "0px";
	divHelp.style.color = "#FFFFFF";
	divHelp.style.backgroundColor="rgba(0, 0, 0, 0.9)";
	
	divHelp.innerHTML = '<div id = "guideTable"><div id = "guideCell"> <div id = "guideList"><h3>WebRTIViewer<br/></h3><ul><li>Pan: LeftMouseButton + MouseMove.</li><li>Zoom in: MouseWhell or press the button <span><img src = "/assets/webViewer/css/icons/zoomin.png" alt=""/></span></li><li>Zoom out: MouseWheel or press the button <span><img src = "/assets/webViewer/css/icons/zoomout.png" alt=""/></span></li><li>To change the light direction: activate the light with the button <span><img src = "/assets/webViewer/css/icons/light.png" alt=""/></span> and press LeftMouseButton + MouseMove or Ctrl + LeftMouseButton + MouseMove.</li><li>Fullscreen: press the button <span><img src = "/assets/webViewer/css/icons/full.png" alt=""/></span> to active the fullscreen mode and the button <span><img src = "/assets/webViewer/css/icons/full_on.png" alt=""/></span> to exit</li><li>To reset the viewpoint: press R.</li></ul><a href="http://vcg.isti.cnr.it/rti/webviewer.php" target = "_black">Visual Computing Lab ISTI CNR Pisa</a></div></div></div>'
	divHelp.style.display = "none";
	canvasNode.append(divHelp);
	
	$( "#"+idDiv + "_div #zoomIn" ).button({
      icons: {
        primary: "zoomInIcon toolbarIcon"
      },
	  text: false,
      label: "Zoom In"
    }).click(function(){multiResRTI.startZoomIn();});
	
	$( "#"+idDiv + "_div #zoomOut" ).button({
      icons: {
        primary: "zoomOutIcon toolbarIcon"
      },
	  text: false,
      label: "Zoom Out"
    }).click(function(){multiResRTI.startZoomOut();});

	$( "#"+idDiv + "_div #light" ).button({
      icons: {
        primary: "lightIcon toolbarIcon"
      },
      text: false,
	  label: "Light Off"
    }).click(function(){
		var options;
		if ( $.trim($( this ).text()) == "Light Off" ) 
		{
			options = {
				label: "Light On",
				icons: {
					primary: "lightOnIcon toolbarIcon"
				}
			};
			multiResRTI.setMode(1);
		} else {
			options = {
				label: "Light Off",
				icons: {
					primary: "lightIcon toolbarIcon"
				}
			};
			multiResRTI.setMode(0);
		}
		$( this ).button( "option", options );
	});
	
	$( "#"+idDiv + "_div #help" ).button({
      icons: {
        primary: "helpIcon toolbarIcon"
      },
	  text: false,
      label: "Help"
    }).click(function(){$( "#"+idDiv + "_guide" ).show();});
	
	
	function fullScreenOn(){
		var i = document.getElementById(idDiv + "_div");
		if (i.requestFullscreen) {
			i.requestFullscreen();
		} else if (i.webkitRequestFullscreen) {
			i.webkitRequestFullscreen();
		} else if (i.mozRequestFullScreen) {
			i.mozRequestFullScreen();
		} else if (i.msRequestFullscreen) {
			i.msRequestFullscreen();
		}
		
		i.style.height = screen.height + "px";
		i.style.width = screen.width + "px";
		var canvas = $("#" + idDiv + "_webgl");
		canvas.height(screen.height);
		canvas.width(screen.width);
		canvas.attr("width", screen.width);
		canvas.attr("height", screen.height);
		multiResRTI.resize();
		fullscreen = true
		var options = {
					label: "Exit Fullscreen",
					icons: {
						primary: "fullOnIcon toolbarIcon"
					}
				};
		$( "#"+idDiv + "_div #fullscreen").button( "option", options );
	};
	
	function fullScreenOff(){
		var i = document.getElementById(idDiv + "_div");
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		}
		i.style.height = canvasHeight + "px";
		i.style.width = canvasWidth + "px";
		var canvas = $("#" + idDiv + "_webgl");
		canvas.height(canvasHeight);
		canvas.width(canvasWidth);
		canvas.attr("width", canvasWidth);
		canvas.attr("height", canvasHeight);
		multiResRTI.resize();
		fullscreen = false;
		var options = {
					label: "Active Fullscreen",
					icons: {
						primary: "fullIcon toolbarIcon"
					}
				};
		$( "#"+idDiv + "_div #fullscreen").button( "option", options );
	};
	
	$( "#"+idDiv + "_div #fullscreen" ).button({
      icons: {
        primary: "fullIcon toolbarIcon"
      },
	  text: false,
      label: "Active Fullscreen"
    }).click(
	function(){
			var options;
			if (!fullscreen)
				fullScreenOn();
			else
				fullScreenOff();
			$("#"+idDiv + "_div #fullscreen").removeClass("ui-state-hover");
		}
	);
	
	document.addEventListener("MSFullscreenChange", function () {
    if(!document.msFullscreenElement) fullScreenOff();
	}, false);
	document.addEventListener("mozfullscreenchange", function () {
		if(!document.mozFullScreen) fullScreenOff();
	}, false);
	document.addEventListener("webkitfullscreenchange", function () {
		if(!document.webkitIsFullScreen) fullScreenOff();
	}, false);
		
	$( "#"+idDiv + "_guide" ).click(function(){$( "#"+idDiv + "_guide" ).hide();});
	
	
	var divError = document.createElement("div");
	divError.id = idDiv + "_error";
	divError.style.width = "100%";
	divError.style.height = "100%";
	divError.style.position = "absolute";
	divError.style.left = "0px";
	divError.style.top = "0px";
	divError.style.color = "#FFFFFF";
	divError.style.backgroundColor="rgba(0, 0, 0, 1.0)";
	
	divError.innerHTML = '<canvas id = "testCanvas" width= "800" height= "600" contenteditable="true"></canvas>';
	divError.style.display = "none";
	canvasNode.append(divError);
	if (sglGetCanvasContext("testCanvas") == null)
	{
		divError.style.display = "block";
		divError.innerHTML = '<div id = "contentError" style = "width: 90%; height: 100%; text-align:left; padding:0% 5% 0% 5%; font-family:  Verdana,sans-serif; overflow-y: scroll;"><h3>WebGL is not available or not enabled.</h3><p><a href="http://en.wikipedia.org/wiki/WebGL">WebGL</a> is the technology we use to display the RTI images. It is currently supported, but sometimes not enabled by default, by recent versions of <a href="http://www.mozilla.org/firefox">Firefox</a>, <a href="http://www.google.com/chrome">Chrome</a> and <a href="http://www.apple.com/safari">Safari</a>. An external plugins is available for <a href="http://microsoft.com/ie">Internet  Explorer</a>.<br/> It also requires a moderately recent and capable hardware and up to date drivers.</p><p><b>Chrome</b><br/>Type "chrome://flags" in the address bar.<br/>Disable (yes, it is a confusing double negation!) the WebGL entry.<br/>Click the <em>relaunch now</em> button at the bottom.</p><p><b>Firefox</b><br/>Type "about:config" in the address bar<br/>Type "webgl" in the <em>Filter</em>.<br/>Doubleclick the entry <b>webgl.force-enable</b>, and restart Firefox.</p><p><b>Safari</b><br/>Open the Safari menu and select Preferences.<br/>Then, click the Advanced tab in the Preferences window.<br/>Then, at the bottom of the window, check the Show Develop menu in menu bar checkbox.<br/>Then, open the Develop menu in the menu bar and select Enable WebGL.</p><p><b>Internet Explorer (from version 9.0)</b><br/>Install the <a href="http://www.google.com/chromeframe">Chrome Frame</a> <br/>Open the Tools menu and select Manage add-ons.<br/>Then, enable the ChromeFrame BHO plugins and restart Internet Explorer.<br/></p><p><b>Installing updated drivers</b><br/>If the visualization of the RTI image fails you must update the drivers of your graphics cards.</p></div>'
	}
	else
	{
		divError.innerHTML = '';
		var multiResRTI = new MultiRes(canvasName);
		if($.type(imageUrlOrFunction) === "string") {
			multiResRTI.setImageUrl(imageUrlOrFunction);	
		}
		else {
			multiResRTI.setImageFunction(imageUrlOrFunction);
		}
		
		function isRelitable(type){
			if (type ==  "IMAGE")
			{
				$( "#"+idDiv + "_div #light" ).css("display", "none");
			}
			$( ".toolbar").css("visibility", "visible");
		};
		multiResRTI.setOnLoadImageCallback(isRelitable);
		sglRegisterCanvas(canvasName, multiResRTI, 10.0);
	}
}



function log(msg) 
{
	//console.log(msg);
}

function MultiRes(canvas)
{
	this.mode = 0;
	this.stepAnimation = 0;
	this.idTimer;
	this.flipAngles = [];
	this.flipAngles[0] = 0.0;
	this.canvas = canvas;
	this.rendering = false;
	this.onDrawCallback = null;
	this.animating = false;
	this.moveToCenter = false;
	this.imageUrl = "";
	this.imageFunction = null;
	this.OnLoadImageCallback = null;
	
	this.currentSpeed = 0.0;
	this.maxStep = 7;
	this.animationStack = [];
	this.isMoving = false;
	for (var  i = 1; i < 21; i++)
		this.flipAngles[i] = SGL_PI / 2.0 * (Math.sin(i / 40.0 * SGL_PI));
}

MultiRes.prototype = {

	setMode: function(x)
	{
		this.mode = x;
	},			

	
	_setLightDir : function(x, y) 
	{
		var lx = ((x / this.ui.width) * 2.2 - 1.1);
		var ly = ((y / this.ui.height) * 2.2 - 1.1);
		lx = Math.min(1.0, Math.max(-1.0, lx));
		ly = Math.min(1.0, Math.max(-1.0, ly));
		var norm = Math.sqrt(lx*lx + ly*ly);
		if (norm > 1.0)
			norm = 1.0;
		var alpha = Math.PI / 2;
		if (lx != 0.0)
			alpha = Math.atan2(ly, lx);
		lx = norm * Math.cos(alpha);
		ly = norm * Math.sin(alpha);
		var lpos = []
		if (norm < 1.0)
			lpos = [lx, ly, Math.sqrt(1.0 - lx*lx - ly*ly)];
		else
			lpos = [lx, ly, 0.0];
		lpos = sglNormalizedV3(lpos);
		this.renderer.lightPos = lpos.slice(0, 3);
		this.renderer.lweights = this.renderer.computeLightingFunction(lpos);
	},

	resetViewpoint : function()
	{
		this.translation[0] = 0.0;
		this.translation[1] = 0.0;
		this.mat = sglIdentityM4();
		this.flipMatrix = sglIdentityM4();
	},
	
	
	setImageUrl: function(url)
	{
		this.imageUrl = url;
	},
	setImageFunction: function(imageFunction)
	{
		this.imageFunction = imageFunction;
	},
	
	setOnLoadImageCallback: function(callback)
	{
		this.OnLoadImageCallback = callback;
	},

	load : function(gl)
	{
		log("SpiderGL Version : " + SGL_VERSION_STRING + "\n");
		/*************************************************************/
		this.xform  = new SglTransformStack();
		/*************************************************************/

		this.renderer = new MultiResRenderer(gl, 256 * 1024 * 1024);
		
		this.translation = [ 0.0, 0.0 ];
		this.mat = sglIdentityM4();
		this.flipMatrix = sglIdentityM4();
		if (this.imageUrl != "")
			this.loadImage(this.imageUrl);
		else if(this.imageFunction !== null)
			this.loadImage(this.imageFunction);
	},
	
	
	loadImage : function(url)
	{
		this.renderer.loadImage(url);
		this.OnLoadImageCallback(this.renderer.type);
		this.xform  = new SglTransformStack();
		this.translation = [ 0.0, 0.0 ];
		this.mat = sglIdentityM4();
		this.rendering = true;
		
		this.leftBottom = [(this.renderer._tree.scale[0] - this.renderer.imgWidth) / 2.0, (this.renderer._tree.scale[1] - this.renderer.imgHeight) / 2.0];
		this.rightTop = [this.leftBottom[0] + this.renderer.imgWidth, this.leftBottom[1] + this.renderer.imgHeight];
		
		this.scale = 0;
		var w = this.ui.width;
		var h = this.ui.height;
		var scale1 = this.renderer._tree.scale[0] / this.renderer.imgWidth * w;
		var scale2 = this.renderer._tree.scale[1] / this.renderer.imgHeight * h;
		this.scale = Math.min(scale1, scale2);
		this.maxScale = Math.max(this.renderer.imgWidth / w, this.renderer.imgHeight / h) * 2.5;
		this.viewerdx = 100;
		this.viewerdy = 100;
	},			
	
	
	stopRendering: function()
	{
		this.rendering = false;
	},
	
	setOnDrawCallback: function(onDraw)
	{
		if (onDraw)
			this.onDrawCallback = onDraw;
	},

	keyDown : function(gl, keyCode, keyString)
	{
		if (keyString == "R") this.resetViewpoint();
		if (keyString == "1") this.renderer.renderData = !this.renderer.renderData;
		if (keyString == "2") this.renderer.renderBoxes = !this.renderer.renderBoxes;
	},
 
	mouseWheel : function(gl, delta, x, y)
	{
		if (this.isMoving)
			return false;
		var tx = x - this.translation[0];
		var ty = y - this.translation[1];
		var s = sglPow(0.98, 10 * delta);
		this._startZoom(s, tx, ty);
	},
	
	
	startZoomIn: function()
	{
		if (this.isMoving)
			return false;
		var tx = this.ui.width / 2.0 - this.translation[0];
		var ty = this.ui.height / 2.0 - this.translation[1];
		var s = 1.2;
		this._startZoom(s, tx, ty);
	},
	
	
	startZoomOut: function()
	{
		if (this.isMoving)
			return false;
		var tx = this.ui.width / 2.0 - this.translation[0];
		var ty = this.ui.height / 2.0 - this.translation[1];
		var s = 0.8;
		this._startZoom(s, tx, ty);
	},
	
	
	_startZoom: function(s, tx, ty)
	{
		var tmp = this.mat[0] * s;
		if (tmp  > this.maxScale)
			s = this.maxScale / this.mat[0];
		if (tmp < 1.0 )
			s = 1.0 / this.mat[0];
		if (tmp <= 1.00000001)
			this.moveToCenter = true;
		var deltaPos = s*this.mat[0] - this.mat[0];
		if (deltaPos > 0.0001 || deltaPos < -0.0001)
		{
			this.animationStack = [];
			var t = this.maxStep * 2; 
			var d = 3 * deltaPos /(t * t * t);
			var b = -2 * t * d;
			var speed = d * t * t;
			var tmpScale = this.mat[0];
			for (var i = 0; i < t; i++)
			{
				var delta = d * i + d / 3.0 + b / 2.0 + speed;
				speed += d * (2*i + 1) + b;
				var deltaScale = 1 + delta / tmpScale;
				tmpScale += delta;
				this.animationStack.push([deltaScale, tx, ty]);
			}
			this.animating = this.moveToCenter;
			var that = this;
			clearInterval(this.moveInterval);
			clearInterval(this.zoomInterval);
			this.zoomInterval = setInterval(function(){that._zoomAnimation();}, 35);
			this.renderer.updateData = false;
			this.isMoving = this.moveToCenter;
		}
		else if (this.moveToCenter)
		{
			this.animating = true;
			this.isMoving = true;
			var that = this;
			this.animationStack = [];
			clearInterval(this.moveInterval);
			clearInterval(this.zoomInterval);
			this.zoomInterval = setInterval(function(){that._zoomAnimation();}, 35);
			this.renderer.updateData = false;
		}
		return false;
	},
	
	_zoomAnimation: function()
	{
		if (this.animationStack.length == 0)
		{
			clearInterval(this.zoomInterval);
			if (this.moveToCenter)
			{
				var that = this;
				clearInterval(this.moveInterval);
				this.moveInterval = setInterval(function(){that._moveAnimation();}, 35);
				this.renderer.updateData = false;
				this.isMoving = true;
				
				this.computeModelMatrix();				
				var matrix = this.xform.model.top;
				var tempPos = [this.renderer._tree.scale[0] / 2.0, this.renderer._tree.scale[1] / 2.0 ];
				var endPoint = [this.ui.width/2.0, this.ui.height/2.0];
				var pos = sglMulM4V4(matrix, sglV4C(tempPos[0], tempPos[1], 0.0, 1.0)).slice(0,2);
				var deltaX = endPoint[0] - pos[0];
				var deltaY = endPoint[1] - pos[1];
				this.currentSpeed = 0;
				this.currentPoint = pos;				
				this._updateMoveStack(pos, endPoint, sglV2C(deltaX, deltaY));
			}
			else
				this.renderer.updateData = true;
			this.animating = false;
			return;
		}
		
		var step = this.animationStack.splice(0, 1)[0];
		var s = step[0];
		tx = step[1];
		ty = step[2];

		var tmpMat = sglMulM4(sglTranslationM4C(tx, ty, 0.0), sglMulM4(sglScalingM4C(s, s, 1.0), sglMulM4(sglTranslationM4C(-tx, -ty, 0.0), this.mat)));
		var m = this.returnModelMatrix(this.translation, tmpMat);
		var lb = sglMulM4V4(m, sglV4C(this.leftBottom[0], this.leftBottom[1], 0.0, 1.0)).slice(0,2);
		var rt = sglMulM4V4(m, sglV4C(this.rightTop[0], this.rightTop[1], 0.0, 1.0)).slice(0,2);
		lb[0] -= this.viewerdx;
		lb[1] -= this.viewerdy;
		rt[0] += this.viewerdx;
		rt[1] += this.viewerdy;
		var realwidth = rt[0] - lb[0];
		var realheight = rt[1] - lb[1];
		var deltaX = 0;
		var deltaY = 0;
		if (realwidth > this.ui.width && realheight > this.ui.height)
		{
			if (rt[0] < this.ui.width)
				deltaX += this.ui.width - rt[0];
			else if (lb[0] > 0)
				deltaX -= lb[0];
			if (rt[1] < this.ui.height)
				deltaY += this.ui.height - rt[1];
			else if (lb[1] > 0)
				deltaY -= lb[1];
		}
		else if (realwidth > this.ui.width)
		{
			ty = this.ui.height / 2.0 - this.translation[1];
			if (rt[0] < this.ui.width)
				deltaX += this.ui.width - rt[0];
			else if (lb[0] > 0)
				deltaX -= lb[0];
		}
		else if (realheight > this.ui.height)
		{
			tx = this.ui.width / 2.0 - this.translation[0];
			if (rt[1] < this.ui.height)
				deltaY += this.ui.height - rt[1];
			else if (lb[1] > 0)
				deltaY -= lb[1];
		}
		else
		{
			tx = this.ui.width / 2.0;
			ty = this.ui.height / 2.0;
			this.resetViewpoint();
			if (this.animationStack.lenght == 0)
			{
				clearInterval(this.zoomInterval);
				this.renderer.updateData = true;
			}
			return;
		}

		this.mat = sglMulM4(sglTranslationM4C(tx, ty, 0.0), sglMulM4(sglScalingM4C(s, s, 1.0), sglMulM4(sglTranslationM4C(-tx, -ty, 0.0), this.mat)));
		
		this.translation[0] += deltaX;
		this.translation[1] += deltaY;
		
		_SGL_RegisteredCanvas[this.canvas].requestDraw();
		return;	
	},
	
	sendMouseDown : function(e)
	{
		_SGL_RegisteredCanvas[this.canvas].mouseDown(e);
	},
 
	mouseDown : function(gl, button, x, y)
	{
		if (this.animating)
			return false;
		if (button == 0)
		{
			if (this.ui.keysDown[17])
			{
				this._setLightDir(x, y);
				return true;
			}
			if (this.mode == 0)
			{
				this.endAnimation = false;
				this.currentPoint = [x, y];
										
				this.animationStack = [];
				
				var that = this;
				clearInterval(this.zoomInterval);
				clearInterval(this.moveInterval);
				this.moveInterval = setInterval(function(){that._moveAnimation();}, 35);
				this.renderer.updateData = false;
				this.isMoving = true;
				return false;
			}
			else if (this.mode == 1) 
				this._setLightDir(x, y);
			return true;
		}
	},
	
	_moveAnimation: function()
	{
		if ((this.moveToCenter ||this.endAnimation) && this.animationStack.length == 0)
		{
			clearInterval(this.moveInterval);
			this.renderer.updateData = true;
			this.isMoving = false;
			this.animating = false;
			this.moveToCenter = false;
			return;
		}
		
		if (this.animationStack.length == 0)
		{
			this.currentSpeed = 0;
			return;
		}
		var step = this.animationStack.splice(0, 1)[0];
		
		var deltaX = step[0] - this.currentPoint[0];
		var deltaY = step[1] - this.currentPoint[1];
		this.currentSpeed = step[2];
		
		this.translation[0] += deltaX;
		this.translation[1] += deltaY;
		
		this.currentPoint[0] = step[0];
		this.currentPoint[1] = step[1];
		
		_SGL_RegisteredCanvas[this.canvas].requestDraw();
		return;
	},
	
	
	mouseUp : function(gl, button, x, y)
	{
		if (button == 0)
		{
			if (this.mode == 0) 
			{
				this.endAnimation = true;
				this.renderer.updateData = true;
				this.isMoving = false;
			}
		}
		return false;
	},
	
	mouseOut:function(gl, button, x, y)
	{
		this.endAnimation = true;
		this.isMoving = false;
	},

	mouseMove: function(gl, x, y)
	{
		if (this.animating)
			return false;
		if (this.ui.mouseButtonsDown[0])
		{
			if (this.mode == 0)
			{
				if (this.ui.keysDown[17])
				{
					this._setLightDir(x, y);
					return true;
				}
				var endPoint = [x, y];
				var deltaX = endPoint[0] - this.currentPoint[0];
				var deltaY = endPoint[1] - this.currentPoint[1];
				
				var tmpVector = this._adjustTranslation(deltaX, deltaY);
				endPoint = [this.currentPoint[0] + tmpVector[0], this.currentPoint[1] + tmpVector[1]];
				this._updateMoveStack(this.currentPoint, endPoint, tmpVector);
				return false;
			}
			else
				this._setLightDir(x, y);
			return true;
		}
		return false;
	},
	
	
	_updateMoveStack: function(startPoint, endPoint, deltaVect)
	{
		var deltaPos = sglLengthV2(deltaVect);
		if (deltaPos > 0.1)
		{
			this.animationStack.splice(0, this.animationStack.length);
			var movementVector = sglNormalizedV2(deltaVect);
			var acceleration = (deltaPos / 2.0 - this.currentSpeed * this.maxStep) * 2.0 / (this.maxStep*this.maxStep);
			var speed = this.currentSpeed;
			var tmpPoint = [startPoint[0], startPoint[1]];
			var totDelta = 0;
			var t = this.maxStep;
			var b = 0;
			var d = 0;
			if (acceleration > 0)
			{
				for (var i = 0 ; i < this.maxStep; i++)
				{
					var delta = acceleration / 2.0  + speed;
					speed += acceleration;
					totDelta += delta;
					var x = movementVector[0] * delta;
					var y = movementVector[1] * delta;
					tmpPoint[0] += x;
					tmpPoint[1] += y;
					this.animationStack.push([tmpPoint[0], tmpPoint[1], speed, acceleration]);
				}
				
				t = 3 * deltaPos / (2 * speed);
				d = 4 * speed * speed * speed / (9 * deltaPos * deltaPos);
				b = - 4 * speed * speed / (3 * deltaPos);
			}
			else
			{
				t = 6 * deltaPos / (2 * speed);
				d = speed * speed * speed / (9 * deltaPos * deltaPos);
				b = - 2 * speed * speed / (3 * deltaPos);
			}					
			
			for (var i = 0 ; i < t; i++)
			{
				var delta = d * i + d / 3.0 + b / 2.0 + speed;
				speed += d * (2*i + 1) + b;
				var acc = d * (i + 1) + b; 
				var x = movementVector[0] * delta;
				var y = movementVector[1] * delta;
				tmpPoint[0] += x;
				tmpPoint[1] += y;
				this.animationStack.push([tmpPoint[0], tmpPoint[1], speed, d]);
			}
			this.animationStack.push([endPoint[0], endPoint[1], 0, 0]);	
		}
	},
	
	_adjustTranslation: function(deltaX, deltaY)
	{
		var translVector = sglV4C(this.translation[0] + deltaX, this.translation[1] + deltaY, 0, 1.0);
		var m = this.returnModelMatrix(translVector, this.mat);
		
		var lb = sglMulM4V4(m, sglV4C(this.leftBottom[0], this.leftBottom[1], 0.0, 1.0)).slice(0,2);
		var rt = sglMulM4V4(m, sglV4C(this.rightTop[0], this.rightTop[1], 0.0, 1.0)).slice(0,2);
		lb[0] -= this.viewerdx;
		lb[1] -= this.viewerdy;
		rt[0] += this.viewerdx;
		rt[1] += this.viewerdy;
		var realwidth = rt[0] - lb[0];
		var realheight = rt[1] - lb[1];
		var realDeltaX = deltaX;
		var realDeltaY = deltaY;
		if (realwidth > this.ui.width && realheight > this.ui.height)
		{
			if (rt[0] < this.ui.width)
				realDeltaX += this.ui.width - rt[0];
			else if (lb[0] > 0)
				realDeltaX -= lb[0];
			if (rt[1] < this.ui.height)
				realDeltaY += this.ui.height - rt[1];
			else if (lb[1] > 0)
				realDeltaY -= lb[1];
		}
		else if (realwidth > this.ui.width)
		{
			if (rt[0] < this.ui.width)
				realDeltaX += this.ui.width - rt[0];
			else if (lb[0] > 0)
				realDeltaX -= lb[0];
			realDeltaY = 0;
		}
		else if (realheight > this.ui.height)
		{
			if (rt[1] < this.ui.height)
				realDeltaY += this.ui.height - rt[1];
			else if (lb[1] > 0)
				realDeltaY -= lb[1];
			realDeltaX = 0;
		}
		else
		{
			realDeltaX = 0;
			realDeltaY = 0;
		}
		return sglV2C(realDeltaX, realDeltaY);	
	},
	
	update : function(gl, dt)
	{
		;
	},
	
	
	returnModelMatrix: function(translVect, scaleMatrix)
	{
		var m = (sglMulM4(sglTranslationM4C(translVect[0], translVect[1], 1.0), scaleMatrix));
		m = sglMulM4(this.flipMatrix, m);
		this.xform.model.load(m);
	
		var t = sglTranslationM4C(this.ui.width / 2.0, this.ui.height / 2.0, 0.0);
		
		this.xform.model.multiply(t);
		this.xform.model.scale(this.scale, this.scale, 1.0);
		this.xform.model.multiply(this.renderer.normalizedTreeTransform);
		return this.xform.model.top;
	},
	
	
	computeModelMatrix: function()
	{
		var m = (sglMulM4(sglTranslationM4C(this.translation[0], this.translation[1], 1.0), this.mat));
		m = sglMulM4(this.flipMatrix, m);
		this.xform.model.load(m);
	
		var t = sglTranslationM4C(this.ui.width / 2.0, this.ui.height / 2.0, 0.0);
		
		this.xform.model.multiply(t);
		this.xform.model.scale(this.scale, this.scale, 1.0);
		this.xform.model.multiply(this.renderer.normalizedTreeTransform);
	},
	
	
	resize : function()
	{
		this.xform  = new SglTransformStack();
		this.translation = [ 0.0, 0.0 ];
		this.mat = sglIdentityM4();
		this.rendering = true;
		
		this.leftBottom = [(this.renderer._tree.scale[0] - this.renderer.imgWidth) / 2.0, (this.renderer._tree.scale[1] - this.renderer.imgHeight) / 2.0];
		this.rightTop = [this.leftBottom[0] + this.renderer.imgWidth, this.leftBottom[1] + this.renderer.imgHeight];
		
		this.scale = 0;
		var w = this.ui.width;
		this.offsetHeight = 0;
		var h = this.ui.height + this.offsetHeight;
		
		this.scale = Math.min(this.renderer._tree.scale[0] / this.renderer.imgWidth * w, this.renderer._tree.scale[1] / this.renderer.imgHeight * h);
		this.maxScale = Math.max(this.renderer.imgWidth / w, this.renderer.imgHeight / h) * 3;
		
		this.viewerdx = this.ui.width / 2.0 - 10;
		this.viewerdy = this.ui.height / 2.0 - 10;
	},
	

	draw : function(gl)
	{
		var error = gl.getError();
		if (error == gl.CONTEXT_LOST_WEBGL)
			console.log("WebGL Context lost");
		gl.clearColor(0.0, 0.0, 0.0, 1.0);
		gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT | gl.STENCIL_BUFFER_BIT);
		if (this.rendering == true)
		{
			var w = this.ui.width;
			var h = this.ui.height;

			gl.viewport(0, 0, w, h);

			this.xform.projection.loadIdentity();
			this.xform.projection.ortho(0.0, w, 0.0, h, -4096.0, 4096.0);

			this.xform.view.loadIdentity();
			this.xform.view.lookAt(0.0, 0.0, 2.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0);

			this.computeModelMatrix();
					
			var m = this.xform.model.top;
			if (this.onDrawCallback)
				this.onDrawCallback(m);
			
			this.renderer.render(this.xform.projectionMatrix, this.xform.modelViewMatrix, [ 0, 0, w, h ]);
		}
	},
};




function MultiResNode()
{
	this.id              = 0;
	this.parentIndex     = -1;
	this.childrenIndices = [ -1, -1, -1, -1 ];
	this.projectedSize   = 1.0;
	this.zScale          = 1.0;
	this.box             = new SglBox3();
	this.priority        = { timestamp: -1, error: Number.MAX_VALUE };
	this.req             = null;
	this.data            = null;
	this.isLeaf          = true;
}



function MultiResTree(url, onloadCallback)
{
	this.ready      = true;
	this.nodesCount = 0;
	this.rootIndex  = -1;
	this.nodes      = null;
	this.url        = null;
	this.tileSize   = 0;
	this.scale      = [ 1.0, 1.0, 1.0 ];
	this.offset     = [ 0.0, 0.0, 0.0 ];
	this.forma		= "";

	if (url)
		this.load(url, imgW, imgH, onloadCallback);
}

MultiResTree.prototype = {

	_load : function(text, imgW, imgH)
	{
		this.ready = true;

		var lines = sglGetLines(text);
		var n = lines.length;
		var tokens = null;
		var i = 0;

		if (n <= i) return;
		tokens = lines[i++].split(" ");
		if (tokens.length < 2) return;
		var nodesCount = parseInt(tokens[0]);
		if (nodesCount <= 0) return;
		var rootIndex = parseInt(tokens[1]);
		if ((rootIndex < 0) || (rootIndex >= nodesCount)) return;

		if (n <= i) return;
		tokens = lines[i++].split(" ");
		if (tokens.length < 1) return;
		var tileSize = parseInt(tokens[0]);
		if (tileSize <= 0) return;

		if (n <= i) return;
		tokens = lines[i++].split(" ");
		if (tokens.length < 3) return;
		var scale = [ 1.0, 1.0, 1.0 ];
		scale[0] = parseFloat(tokens[0]);
		scale[1] = parseFloat(tokens[1]);
		scale[2] = parseFloat(tokens[2]);

		if (n <= i) return;
		tokens = lines[i++].split(" ");
		if (tokens.length < 3) return;
		var offset = [ 0.0, 0.0, 0.0 ];
		offset[0] = parseFloat(tokens[0]);
		offset[1] = parseFloat(tokens[1]);
		offset[2] = parseFloat(tokens[2]);

		var nodes = new Array(nodesCount);
		if ( n > i )
		{
			for (var k=0; k<nodesCount; ++k)
			{
				var node = new MultiResNode();

				if (n <= i) return;
				tokens = lines[i++].split(" ");
				if (tokens.length < 14) return;

				node.id = parseInt(tokens[0]);
				if (node.id <= 0) return;

				node.parentIndex = parseInt(tokens[1]);
				if ((node.parentIndex < -1) || (node.parentIndex >= nodesCount)) return;

				for (var c=0; c<4; ++c) 
				{
					node.childrenIndices[c] = parseInt(tokens[c+2]);
					if ((node.childrenIndices[c] < -1) || (node.childrenIndices[c] >= nodesCount)) return;
				}

				node.projectedSize = parseFloat(tokens[6]);

				node.zScale = parseFloat(tokens[7]);

				node.box.min[0] = parseFloat(tokens[ 8]);
				node.box.min[1] = parseFloat(tokens[ 9]);
				node.box.min[2] = parseFloat(tokens[10]);
				node.box.max[0] = parseFloat(tokens[11]);
				node.box.max[1] = parseFloat(tokens[12]);
				node.box.max[2] = parseFloat(tokens[13]);

				node.isLeaf = true;
				for (var j=0; j<4; ++j) 
				{
					if (node.childrenIndices[j] >= 0) 
					{
						node.isLeaf = false;
						break;
					}
				}

				nodes[k] = node;
			}
		}
		else
		{
			var index = 0;
			var temp = scale[0];
			var nLevel = 1;
			while (temp > tileSize)
			{
				nLevel++;
				temp /= 2;
			}
			var woffset = ((scale[0] - imgW) / 2.0) / scale[0];
			var hoffset = ((scale[1] - imgH) / 2.0) / scale[1];
			var imgBox = new SglBox3([woffset, hoffset, 0], [woffset + imgW / scale[0] , hoffset + imgH / scale[1], 1.0]);
			var imgSize = imgBox.size;
			var imgCenter = imgBox.center;
			for (var i = 0; i < nLevel; i++)
			{
				var count = Math.pow(4, i);
				for (var j = 0; j < count; j++)
				{
					var node = new MultiResNode();
					node.id = index + 1;
					if (index > 0)
						node.parentIndex = Math.ceil(index / 4) - 1;
					else
						node.parentIndex = -1;
					if (i < nLevel - 1)
						node.isLeaf = false;
					for (var c=0; c<4; ++c) 
					{
						if (i == nLevel - 1)
							node.childrenIndices[c] = -1;
						else
							node.childrenIndices[c] = index * 4 + 1 + (c + 2) % 4;
					}					
					node.projectedSize = tileSize;
								
					if (index > 0)
					{
						var t = index % 4;
						var parentNode = nodes[node.parentIndex];
						var halfW = parentNode.box.min[0] + (parentNode.box.max[0] -  parentNode.box.min[0]) / 2.0;
						var halfH = parentNode.box.min[1] + (parentNode.box.max[1] -  parentNode.box.min[1]) / 2.0;
						if (t == 1)
							node.box = new SglBox3([parentNode.box.min[0], halfH, 0.0], [halfW, parentNode.box.max[1], 1.0]);
						else if (t == 2)
							node.box = new SglBox3([halfW, halfH, 0.0], parentNode.box.max);
						else if (t == 3)
							node.box = new SglBox3(parentNode.box.min, [halfW, halfH, 1.0]);
						else if (t == 0)
							node.box = new SglBox3([halfW, parentNode.box.min[1], 0.0], [parentNode.box.max[0], halfH, 1.0]);
						var center = node.box.center;
						var size = node.box.size;
						var halfSize = sglSelfMulV3S(sglAddV3(imgSize, size), 0.5);
						var diff = sglSubV3(imgCenter, center);
						if (Math.abs(diff[0]) <= halfSize[0] && Math.abs(diff[1]) <= halfSize[1] && Math.abs(diff[2]) <= halfSize[2])
							node.zScale = 1;
						else 
							node.zScale = 0;
					}
					else
					{
						node.box = new SglBox3([0.0, 0.0, 0.0], [1.0, 1.0, 1.0]);
						node.zScale = 1;
					}	
					nodes[index] = node;
					index++;
				}
			}
		}

		this.nodesCount = nodesCount;
		this.rootIndex  = rootIndex;
		this.tileSize   = tileSize;
		this.scale      = scale;
		this.offset     = offset;
		this.nodes      = nodes;
	},

	destroy : function(destroyData)
	{
		var n = null;
		for (var i in this.nodes) 
		{
			n = this.nodes[i];
			if (n.req) 
			{
				n.req.onLoad = null;
				n.req = null;
			}
			if (n.data) 
			{
				destroyData(n.data);
				n.data = null;
			}
		}

		this.ready      = true;
		this.nodesCount = 0;
		this.rootIndex  = -1;
		this.nodes      = null
		this.url        = null;
		this.tileSize   = 0;
		this.scale      = [ 1.0, 1.0, 1.0 ];
	},

	load : function(url, imgW, imgH, onloadCallback)
	{
		if (!url) return false;
		this._load(url, imgW, imgH);
	},

	get isEmpty() 
	{
		return (this.nodesCount <= 0);
	},

	get root() 
	{
		var idx = this.rootIndex;
		if (idx < 0) return null;
		return this.nodes[idx];
	},

	parent : function(n)
	{
		var idx = n.parentIndex;
		if (idx < 0) return null;
		return this.nodes[idx];
	},

	child: function(n, i)
	{
		var idx = n.childrenIndices[i];
		if (idx < 0) return null;
		return this.nodes[idx];
	}
};

function _MultiResAssert(cond) 
{
	if (cond) return;
	alert("MultiResAssert FAILED : " + cond);
}

function _MultiResCompareNodes(a, b)
{
	// higher timestamp first
	if (a.priority.timestamp != b.priority.timestamp)
		return (b.priority.timestamp - a.priority.timestamp);
	
	// higher error first
	if (a.priority.error != b.priority.error)
		return (b.priority.error - a.priority.error);

	// lower id first
	return (a.id - b.id);
};

function MultiResRenderer(gl, cacheSizeInBytes, onLoad)
 {
	this.lg = true;
	this.gl = gl;
	this._tree = new MultiResTree();
	this._timestamp = 0;
	this._frustum = new SglFrustum();
	this._maxError = 1.0;
	this._cache = [ ];
	this._cacheSizeInBytes = cacheSizeInBytes;
	this._maxCacheSize = 1;
	this._maxOngoingRequests = 2;
	this._currentOngoingRequests = 0;
	this._toRequest = [ ];
	this._toRenderFullRes = [ ];
	this._toRenderHalfRes = [ ];
	this._readyItems = [ ];

	this.renderData  = true;
	this.renderBoxes = false;
	//this.lightPos    = [ 0.0, 0.0, 1.0 ];
	//this.lweights 	 = this.computeLightingFunction(this.lightPos);

	// box mesh
	/******************************************************/
	var boxPositions = new Float32Array([
		-0.5, -0.5,  0.5,
		 0.5, -0.5,  0.5,
		-0.5,  0.5,  0.5,
		 0.5,  0.5,  0.5,
		-0.5, -0.5, -0.5,
		 0.5, -0.5, -0.5,
		-0.5,  0.5, -0.5,
		 0.5,  0.5, -0.5
	 ]);

	var trianglesIndices = new Uint16Array([
		0, 1, 2,  2, 1, 3,  // front
		5, 4, 7,  7, 4, 6,  // back
		4, 0, 6,  6, 0, 2,  // left
		1, 5, 3,  3, 5, 7,  // right
		2, 3, 6,  6, 3, 7,  // top
		4, 5, 0,  0, 5, 1   // bottom
	]);

	var edgesIndices = new Uint16Array([
		0, 1, 1, 3, 3, 2, 2, 0,  // front
		5, 4, 4, 6, 6, 7, 7, 5,  // back
		0, 4, 1, 5, 3, 7, 2, 6   // middle
	]);

	var box = new SglMeshGL(gl);

	box.addVertexAttribute("position", 3, boxPositions);
	box.addIndexedPrimitives("triangles", gl.TRIANGLES, trianglesIndices);
	box.addIndexedPrimitives("edges",     gl.LINES,     edgesIndices);

	this._boxMesh = box;
	/******************************************************/

	/******************************************************/
	this._tileFullMesh = null;
	this._tileHalfMesh = null;
	/******************************************************/

	// image rendering
	/******************************************************/
	this._program = null;
	/******************************************************/

	// box rendering
	/******************************************************/
	var bvs = sglLoadFile("/assets/webViewer/spidergl/box.v.glsl");
	var bfs = sglLoadFile("/assets/webViewer/spidergl/box.f.glsl");
	var bprg = new SglProgram(gl, [bvs], [bfs]);
	log(bprg.log);

	this._boxProgram  = bprg;
	this._boxRenderer = new SglMeshGLRenderer(this._boxProgram);
	/******************************************************/

	this._treeTransform = sglIdentityM4();
	this._normalizedTreeTransform = sglIdentityM4();

	this.updateData = true;

	var texVsrc = "#ifdef GL_ES\n" + "precision highp float;\n" + "#endif\n" + "uniform   mat4 u_mvp;\n" + "attribute vec2 a_position;\n" + "attribute vec2 a_texcoord;\n" + "varying   vec2 v_texcoord;\n" + "void main(void){\n" +"v_texcoord  = a_texcoord;\n" + "gl_Position = u_mvp * vec4(a_position, 0.0, 1.0);\n}";
	var texFsrc = "#ifdef GL_ES\n" + "precision highp float;\n" + "#endif\n" + "uniform sampler2D s_texture;\n" + "varying vec2     v_texcoord;\n" + "void main(void){" + "vec4 color = texture2D(s_texture, v_texcoord).xyzw;\n" + "if (color.w > 0.7)\n" + "gl_FragData[0] = vec4(color.rgb, 1.0);\n" + "else\n" +
	"gl_FragData[0] = vec4(0.0);}";	
	this.texProg = new SglProgram(this.gl, [texVsrc], [texFsrc]);
	log(this.texProg.log);
		
	var lgPositions = new Float32Array
	([
		 0, 0,
		 80,  0,
		 0,  50,
		 80,  50,
	]);

	var lgTexcoords = new Float32Array
	([
		0.0, 0.0,
		1.0, 0.0,
		0.0, 1.0,
		1.0, 1.0
	]);

	if (this.lg)
	{
		var tmp = new SglMeshGL(this.gl);
		tmp.addVertexAttribute("position", 2, lgPositions);
		tmp.addVertexAttribute("texcoord", 2, lgTexcoords);
		tmp.addArrayPrimitives("tristrip", this.gl.TRIANGLE_STRIP, 0, 4);
		this.lgMesh = tmp;
		this.lgtex = null;
		this._createLg();
	}
	
	this.enumType = new Object();
	this.enumType["HSH_RTI"] = 1;
	this.enumType["LRGB_PTM"] = 2;
	this.enumType["RGB_PTM"] = 3;
	this.enumType["IMAGE"] = 4;
}


MultiResRenderer.prototype = {

	computeLightingFunction: function(lpos)
	{
		switch (this.enumType[this.type])
		{
			case 1:
				var phi = Math.atan2(lpos[1], lpos[0]);
				if (phi < 0) 
					phi = 2 * Math.PI + phi;
				var theta = Math.min(Math.acos(lpos[2]), Math.PI / 2 - 0.15);
					
				var cosPhi = Math.cos(phi);
				var cosTheta = Math.cos(theta);
				var cosTheta2 = cosTheta * cosTheta;
				
				var lweights = new Array(9);
				lweights[0] = 1.0 / Math.sqrt(2.0 * Math.PI);
				lweights[1] = Math.sqrt(6.0 / Math.PI) * (cosPhi * Math.sqrt(cosTheta-cosTheta2));
				lweights[2] = Math.sqrt(3.0 / (2.0 * Math.PI)) * (-1.0 + 2.0*cosTheta);
				lweights[3] = Math.sqrt(6.0 / Math.PI) * (Math.sqrt(cosTheta - cosTheta2) * Math.sin(phi));
				
				lweights[4] = Math.sqrt(30.0 / Math.PI) * (Math.cos(2.0 * phi) * (-cosTheta + cosTheta2));
				lweights[5] = Math.sqrt(30.0 / Math.PI) * (cosPhi*(-1.0 + 2.0 * cosTheta) * Math.sqrt(cosTheta - cosTheta2));
				lweights[6] = Math.sqrt(5.0 / (2.0 * Math.PI)) * (1.0 - 6.0 * cosTheta + 6.0 * cosTheta2);
				lweights[7] = Math.sqrt(30.0 / Math.PI) * ((-1.0 + 2.0 * cosTheta) * Math.sqrt(cosTheta - cosTheta2) * Math.sin(phi));
				lweights[8] = Math.sqrt(30.0 / Math.PI) * ((-cosTheta + cosTheta2) * Math.sin(2.*phi));
				return lweights;
			case 2:
			case 3:
				var lweights = new Array(6);
				lweights[0] = lpos[0] * lpos[0];
				lweights[1] = lpos[1] * lpos[1];
				lweights[2] = lpos[0] * lpos[1];
				lweights[3] = lpos[0];
				lweights[4] = lpos[1];
				lweights[5] = 1.0;
				return lweights;
			default:
		}
		return [];
	},
	
	
	loadImage: function(url)
	{
		var xhttp=new XMLHttpRequest();
		var targetURL = null;
		if(typeof url === "string") {
			targetURL = url + "/info.xml";
		}
		else {
			targetURL = url("/info.xml");
		}
		xhttp.overrideMimeType('text/xml');
		xhttp.open("GET", targetURL, false);
		xhttp.send();

		var doc = xhttp.responseXML;
		this.format = "jpg";
		var val = parseInt(doc.getElementsByTagName("MultiRes")[0].getAttribute("format"));
		if (!isNaN(val))
			if (val == 1)
				this.format = "png";
		var content = doc.getElementsByTagName("Content")[0];
		this.type = content.getAttribute("type");
		if (!(this.type in this.enumType))
			return false;
		var tree = null;
		switch (this.enumType[this.type])
		{
			case 1:
			case 2:
			case 3:
				var size = doc.getElementsByTagName("Size")[0];
				var scale = doc.getElementsByTagName("Scale")[0];
				var bias = doc.getElementsByTagName("Bias")[0];
				tree = doc.getElementsByTagName("Tree")[0];
							
				this.imgWidth = parseInt(size.getAttribute("width"));
				this.imgHeight = parseInt(size.getAttribute("height"));
				this.ordlen = parseInt(size.getAttribute("coefficients"));
				this.numLayers = this.ordlen;
				if (this.enumType[this.type] == 2)
					this.numLayers = 3;
				tokens = scale.childNodes[0].nodeValue.split(" ");
				if (tokens.length < this.ordlen) return;
				this.gmax = [];
				for (var j = 0; j < this.ordlen; j++ )
					this.gmax[j] = parseFloat(tokens[j]);
						
				tokens = bias.childNodes[0].nodeValue.split(" ");
				if (tokens.length < this.ordlen) return;
				this.gmin = [];
				for (var j = 0; j < this.ordlen; j++ )
					this.gmin[j] = parseFloat(tokens[j]);
				break;
			case 4:
				var size = doc.getElementsByTagName("Size")[0];
				tree = doc.getElementsByTagName("Tree")[0];
							
				this.imgWidth = parseInt(size.getAttribute("width"));
				this.imgHeight = parseInt(size.getAttribute("height"));
				this.ordlen = parseInt(size.getAttribute("coefficients"));
				this.numLayers = this.ordlen;
				break;
			default:
				return;		
		}
		
		
		if (this._tree)
			this._tree.destroy(this._destroyData);
		
		this._tree.load(tree.textContent, this.imgWidth, this.imgHeight);
		
		this._url  = url;
		this._timestamp = 0;
		this._frustum = new SglFrustum();
		this._maxError = 1.0;
		this._cache = [ ];
		this._toRequest = [ ];
		this._toRenderFullRes = [ ];
		this._toRenderHalfRes = [ ];
		this._readyItems = [ ];

		this.renderData  = true;
		this.renderBoxes = false;
		
		//var lpos = [0.241844, 0.241844, 0.939692]
		//lpos = sglNormalizedV3(lpos);
		//this.lightPos = lpos.slice(0, 3);
		this.lightPos    = [ 0.0, 0.0, 1.0 ];
		this.lweights 	 = [];
		this.lweights 	 = this.computeLightingFunction(this.lightPos);
		if (this._program != null)
			this._program.destroy();
		switch (this.enumType[this.type])
		{
			case 1:
				var vs = sglLoadFile("/assets/webViewer/spidergl/multi.v.glsl");
				var fs = sglLoadFile("/assets/webViewer/spidergl/hsh.f.glsl");
				var prg = new SglProgram(this.gl, [vs], [fs]);
				log(prg.log);
				this._program  = prg;
				break;
			case 2:
				var vs = sglLoadFile("/assets/webViewer/spidergl/multi.v.glsl");
				var fs = sglLoadFile("/assets/webViewer/spidergl/lrgbptm.f.glsl");
				var prg = new SglProgram(this.gl, [vs], [fs]);
				log(prg.log);
				this._program  = prg;
				break;
			case 3:
				var vs = sglLoadFile("/assets/webViewer/spidergl/multi.v.glsl");
				var fs = sglLoadFile("/assets/webViewer/spidergl/rgbptm.f.glsl");
				var prg = new SglProgram(this.gl, [vs], [fs]);
				log(prg.log);
				this._program  = prg;
				break;
			case 4:
				var vs = sglLoadFile("/assets/webViewer/spidergl/multi.v.glsl");
				var fs = sglLoadFile("/assets/webViewer/spidergl/image.f.glsl");
				var prg = new SglProgram(this.gl, [vs], [fs]);
				log(prg.log);
				this._program  = prg;
				break;
			default:
				this._program = new SglProgram();
		}
		this._renderer = new SglMeshGLRenderer(this._program);
		this._treeTransform = sglIdentityM4();
		this._normalizedTreeTransform = sglIdentityM4();
		
		this.leftTex = (this._tree.scale[0] - this.imgWidth) / 2.0; 
		this.rightTex = this.leftTex + this.imgWidth; 
		
		this.bottomTex = (this._tree.scale[1] - this.imgHeight) / 2.0; 
		this.topTex = this.bottomTex + this.imgHeight;
		this.leftTex /= this._tree.scale[0];
		this.rightTex /= this._tree.scale[0];
		
		this.bottomTex /= this._tree.scale[1];
		this.topTex /= this._tree.scale[1];
		
		if (this._tree.ready) 
			this._setupTree();
	},


	_createLg: function()
	{
		var canvas=document.createElement("canvas");
		canvas.height = 50;
		canvas.width = 80;
		var ctx = canvas.getContext("2d");
		ctx.save();
		ctx.beginPath();
		ctx.moveTo(0,0);
		ctx.lineTo(80,0);
		ctx.lineTo(80,50);
		ctx.lineTo(0,50);
		ctx.closePath();
		ctx.clip();
		ctx.strokeStyle = 'rgba(0,0,0,0)';
		ctx.lineCap = 'butt';
		ctx.lineJoin = 'miter';
		ctx.miterLimit = 4;
		ctx.save();
		ctx.restore();
		ctx.save();
		ctx.restore();
		ctx.save();
		ctx.transform(0.21814175,0,0,0.21573678,-3.3721339,-3.3665838);
		ctx.save();
		ctx.translate(14.071762,20);
		ctx.save();
		g=ctx.createRadialGradient(239.50893,204.66128888494003,0,239.50893,204.66128888494003,31.820419);
		g.addColorStop(0,"rgba(255, 255, 255, 1)");
		g.addColorStop(1,"rgba(151, 151, 151, 1)");
		ctx.fillStyle = g;
		ctx.strokeStyle = "#000000";
		ctx.lineWidth = 2.4923694133758545;
		ctx.lineCap = "round";
		ctx.lineJoin = "round";
		ctx.miterLimit = 4;
		ctx.transform(2.1234583,0,0,1.6108995,-256.37574,-264.34719);
		ctx.beginPath();
		ctx.moveTo(271.42857,233.79076);
		ctx.translate(240,233.80553976769053);
		ctx.rotate(0);
		ctx.scale(0.7586207296076113,1);
		ctx.arc(0,0,41.42857,-0.00035675303306008355,3.1419494066230014,0);
		ctx.scale(1.318181748760332,1);
		ctx.rotate(0);
		ctx.translate(-240,-233.80553976769053);
		ctx.translate(240,233.77598023230948);
		ctx.rotate(0);
		ctx.scale(0.7586207296076113,1);
		ctx.arc(0,0,41.42857,3.141235900556733,6.2835420602127945,0);
		ctx.scale(1.318181748760332,1);
		ctx.rotate(0);
		ctx.translate(-240,-233.77598023230948);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		ctx.restore();
		ctx.save();
		ctx.fillStyle = "#ececec";
		ctx.strokeStyle = "#000000";
		ctx.lineWidth = 4.609655857086182;
		ctx.lineCap = "butt";
		ctx.lineJoin = "round";
		ctx.miterLimit = 4;
		ctx.beginPath();
		ctx.moveTo(49.495605,44.507569);
		ctx.lineTo(129.49561,79.507569);
		ctx.lineTo(209.49561,44.507569);
		ctx.lineTo(49.495605,44.507569);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		ctx.restore();
		ctx.save();
		ctx.fillStyle = "#999999";
		ctx.strokeStyle = "#000000";
		ctx.lineWidth = 4.609655857086182;
		ctx.lineCap = "butt";
		ctx.lineJoin = "round";
		ctx.miterLimit = 4;
		ctx.beginPath();
		ctx.moveTo(129.49561,174.50757);
		ctx.lineTo(129.49561,79.507572);
		ctx.lineTo(209.49561,44.507572);
		ctx.lineTo(129.49561,174.50757);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		ctx.restore();
		ctx.save();
		ctx.fillStyle = "#b3b3b3";
		ctx.strokeStyle = "#000000";
		ctx.lineWidth = 4.609655857086182;
		ctx.lineCap = "butt";
		ctx.lineJoin = "round";
		ctx.miterLimit = 4;
		ctx.beginPath();
		ctx.moveTo(129.49561,174.50757);
		ctx.lineTo(129.49561,79.507569);
		ctx.lineTo(49.495605,44.507569);
		ctx.lineTo(129.49561,174.50757);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		ctx.restore();
		ctx.save();
		g=ctx.createLinearGradient(236.58487,196.44904,257.4877,320.66739);
		g.addColorStop(0,"rgba(255, 255, 255, 1)");
		g.addColorStop(1,"rgba(151, 151, 151, 1)");
		ctx.fillStyle = g;
		ctx.strokeStyle = "#000000";
		ctx.lineWidth = 3.3579113483428955;
		ctx.lineCap = "round";
		ctx.lineJoin = "round";
		ctx.miterLimit = 4;
		ctx.transform(1.4965962,0,0,1.2591977,-86.218969,-182.0266);
		ctx.beginPath();
		ctx.moveTo(271.42857,233.79076);
		ctx.translate(240,233.80553976769053);
		ctx.rotate(0);
		ctx.scale(0.7586207296076113,1);
		ctx.arc(0,0,41.42857,-0.00035675303306008355,3.1419494066230014,0);
		ctx.scale(1.318181748760332,1);
		ctx.rotate(0);
		ctx.translate(-240,-233.80553976769053);
		ctx.translate(240,233.77598023230948);
		ctx.rotate(0);
		ctx.scale(0.7586207296076113,1);
		ctx.arc(0,0,41.42857,3.141235900556733,6.2835420602127945,0);
		ctx.scale(1.318181748760332,1);
		ctx.rotate(0);
		ctx.translate(-240,-233.77598023230948);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		ctx.restore();
		ctx.restore();
		ctx.restore();
		ctx.restore();
		var image = new Image();
		var dataURL = canvas.toDataURL("image/png");
		// image.crossOrigin = '';
		image.src = dataURL;

		var that = this;
		image.onload = function() 
						{ 
							var texOpt =
							{
								generateMipmap  : false,
								minFilter       : that.gl.NEAREST,
								magFilter 		: that.gl.NEAREST,
							};
							that.lgtex = new SglTexture2D(that.gl, image, texOpt);
						}
		
		
		var error = this.gl.getError();
		console.log(error);
	},


	_createData : function(colorImgs)
	{
		var colorTexOpts = {
			minFilter : this.gl.LINEAR,
			magFilter : this.gl.LINEAR,
			generateMipmap : false
		};

		var dataTextures = new Array(colorImgs.length);
		for (var i=0; i < colorImgs.length; ++i) 
			dataTextures[i] = new SglTexture2D(this.gl, colorImgs[i], colorTexOpts);

		var data = {
			textures : dataTextures
		};

		return data;
	},

	
	_destroyData : function(data) 
	{
		if (!data) return;
		for (var i = 0; i < data.textures.length; ++i) 
			data.textures[i].destroy();
		
		data.textures = null;
	},

	
	_requestNode : function(n)
	{
		if (n.req) return;

		var that  = this;
		if(typeof this._url === "string") {
			var url   = this._url + "/" + n.id;	
		}
		

		var requests = new Array();

		for (var i = 1; i <= this.numLayers; i++) {
			if(typeof this._url === "string") {
				var targetURL   = url + "_" + i + "." + this.format;	
			}
			else {
				var targetURL = this._url("/" + n.id + "_" + i + "." + this.format)
			}
		
			requests.push(new SglImageRequest(targetURL));
		}

		var watcher = new SglRequestWatcher(requests, function(w) {
			that._currentOngoingRequests--;
			that._readyItems.push(n);
		});
		n.req = watcher;
		watcher.send();
	},

	
	_collectNodesRec : function(n, parentFullyVisible)
	{
		var result = {
			needed : false,
			usable : ((n.data) ? (true) : (false))
		};

		if (n.zScale == 0)
			return result;
		
		// test visibility only if parent is not completely inside frustum
		if (!parentFullyVisible) 
		{
			var visStatus = this._frustum.boxVisibility(n.box.min, n.box.max);
			if (visStatus == SGL_OUTSIDE_FRUSTUM) return result;
			parentFullyVisible = (visStatus == SGL_INSIDE_FRUSTUM);
		}

		// node is visible and thus needed
		result.needed = true;

		// calculate error
		var prjSize = this._frustum.projectedSegmentSize(n.box.center, n.box.size[0]);
		var error   = prjSize / n.projectedSize;

		n.priority.timestamp = this._timestamp;
		n.priority.error     = error;

		// if node data is not available, request node and return
		if (!result.usable) 
		{
			if (!n.req)
			{
				this._toRequest.push(n);
				//this._toRequest[n.id] = n;
			}
			return result;
		}

		// if node is below error, render it in full resolution and return
		if ((error < this._maxError) || n.isLeaf)
		{
			this._toRenderFullRes.push(n);
			//this._toRenderFullRes[n.id] = n;
			return result;
		}

		// recurse down to children
		var c = null;
		var neededCount = 0;
		var usableCount = 0;
		var cr = null;
		var missingChildren = [ ];

		for (var i = 0; i < 4; ++i)
		{
			c = this._tree.child(n, i);
			if (!c) continue;
			cr = this._collectNodesRec(c, parentFullyVisible);
			if (cr.usable) usableCount++;
			if (cr.needed)
			{
				neededCount++;
				if (!cr.usable)
				{
					missingChildren.push(i);
				}
			}
		}

		//_MultiResAssert(neededCount > 0);

		if (neededCount > 0) 
		{
			if (usableCount <= 0)
			{
				// no needed child is available, render node in full resolution
				this._toRenderFullRes.push(n);
				//this._toRenderFullRes[n.id] = n;
			}
			else if (missingChildren.length > 0)
			{
				// some child is needed but not usable, render their sectors in half resolution (wrt this node)
				this._toRenderHalfRes.push({ n: n, children: missingChildren });
				//this._toRenderHalfRes[n.id] = { n: n, missingChildren: missingChildren };
			}
			// else all needed children can be rendered
		}

		// the node is needed and available
		return result;
	},

	
	_collectNodes : function() 
	{
		this._toRequest = [ ];
		this._toRenderFullRes = [ ];
		this._toRenderHalfRes = [ ];

		var root = this._tree.root;
		if (!root) return;

		this._collectNodesRec(root, false);
	},

	
	_renderNodeFullRes : function(n)
	{
		var uniforms = {
			u_world_box_min : n.box.min,
			u_world_box_max : n.box.max
		};

		var samplers = [];
		for (var i = 0; i < this.numLayers; i++)
			samplers['coeff' + i] = n.data.textures[i];
		
		this._renderer.setUniforms(uniforms);
		this._renderer.setSamplers(samplers);
		this._renderer.render();
	},

	_renderNodesFullRes : function(mvp)
	{
		var mesh = null;
		var primitives = null;

		mesh = this._tileFullMesh;
		primitives = "tristrip";

		var uniforms = {
			u_model_view_projection_matrix : mvp,
			u_scale_bias                   : [ 1.0, 1.0, 0.0, 0.0 ],
			u_texcoord_scale_bias          : [ (this._tree.tileSize / (this._tree.tileSize + 2.0)), (1.0 / (this._tree.tileSize + 2.0)) ],
			ordlen						   : this.ordlen,
			leftTex                        : this.leftTex,
			rightTex                       : this.rightTex,
			bottomTex                      : this.bottomTex,
			topTex                         : this.topTex
		}
		
		if (this.enumType[this.type] != 4)
		{
			for (var i = 0; i < this.ordlen; i++)
			{
				uniforms['gmax' + i] = this.gmax[i];
				uniforms['gmin' + i] = this.gmin[i];
				uniforms['lweight' + i] = this.lweights[i];
			}
		}
		
		this._renderer.begin();
			this._renderer.setUniforms(uniforms);
			this._renderer.beginMesh(mesh);
				this._renderer.beginPrimitives(primitives);
				var n = null;
				for (var i in this._toRenderFullRes)
				{
					n = this._toRenderFullRes[i];
					this._renderNodeFullRes(n);
				}
				this._renderer.endPrimitives();
			this._renderer.endMesh();
		this._renderer.end();
	},

	_renderNodeHalfRes : function(n, children)
	{
		var uniforms = {
			u_world_box_min : null,
			u_world_box_max : null,
			u_scale_bias    : null
		};

		var samplers = [];
		for (var i = 0; i < this.numLayers; i++)
			samplers['coeff' + i] = n.data.textures[i];
		
		this._renderer.setSamplers(samplers);

		var scaleBias = [
			[ 0.5, 0.5, 0.0, 0.0 ],
			[ 0.5, 0.5, 0.5, 0.0 ],
			[ 0.5, 0.5, 0.0, 0.5 ],
			[ 0.5, 0.5, 0.5, 0.5 ]
		];

		var c = null;
		for (var i in children)
		{
			c = this._tree.child(n, children[i]);
			uniforms.u_world_box_min = c.box.min;
			uniforms.u_world_box_max = c.box.max;
			uniforms.u_scale_bias    = scaleBias[children[i]];
			this._renderer.setUniforms(uniforms);
			this._renderer.render();
		}
	},

	_renderNodesHalfRes : function(mvp)
	{
		var mesh = null;
		var primitives = null;

		mesh = this._tileHalfMesh;
		primitives = "tristrip";

		var uniforms = {
			u_model_view_projection_matrix : mvp,
			u_texcoord_scale_bias          : [ (this._tree.tileSize / (this._tree.tileSize + 2.0)), (1.0 / (this._tree.tileSize + 2.0)) ],
			ordlen						   : this.ordlen,
			leftTex                        : this.leftTex,
			rightTex                       : this.rightTex,
			bottomTex                      : this.bottomTex,
			topTex                         : this.topTex
		}
		
		if (this.enumType[this.type] != 4)
		{
			for (var i = 0; i < this.ordlen; i++)
			{
				uniforms['gmax' + i] = this.gmax[i];
				uniforms['gmin' + i] = this.gmin[i];
				uniforms['lweight' + i] = this.lweights[i];
			}
		}
		
		this._renderer.begin();
			this._renderer.setUniforms(uniforms);
			this._renderer.beginMesh(mesh);
				this._renderer.beginPrimitives(primitives);
				var nc = null;
				for (var i in this._toRenderHalfRes)
				{
					nc = this._toRenderHalfRes[i];
					this._renderNodeHalfRes(nc.n, nc.children);
				}
				this._renderer.endPrimitives();
			this._renderer.endMesh();
		this._renderer.end();
	},

	_renderNodes : function(mvp)
	{
		this._renderNodesFullRes(mvp);
		this._renderNodesHalfRes(mvp);
	},

	_renderNodeFullResBox : function(n) 
	{
		var sz = n.box.size;

		var uniforms = {
			u_world_box_min : sglAddV3S(n.box.min, sz[0] * 0.05),
			u_world_box_max : sglSubV3S(n.box.max, sz[0] * 0.05),
			u_color         : ((n.isLeaf) ? ([1.0, 0.0, 1.0]) : ([0.0, 1.0, 0.0]))
		};

		this._boxRenderer.setUniforms(uniforms);
		this._boxRenderer.render();
	},

	_renderNodesFullResBoxes : function(mvp) 
	{
		var uniforms = {
			u_model_view_projection_matrix : mvp
		};

		this._boxRenderer.begin();
			this._boxRenderer.setUniforms(uniforms);
			this._boxRenderer.beginMesh(this._boxMesh);
				this._boxRenderer.beginPrimitives("edges");
				var n = null;
				for (var i in this._toRenderFullRes)
				{
					n = this._toRenderFullRes[i];
					this._renderNodeFullResBox(n);
				}
				this._boxRenderer.endPrimitives();
			this._boxRenderer.endMesh();
		this._boxRenderer.end();
	},

	_renderNodeHalfResBox : function(n, children) 
	{
		var uniforms = {
			u_world_box_min : null,
			u_world_box_max : null,
			u_color         : null
		};

		var c  = null;
		var sx = null;
		for (var i in children)
		{
			c = this._tree.child(n, children[i]);
			sz = c.box.size;
			uniforms.u_world_box_min = sglAddV3S(c.box.min, sz[0] * 0.05);
			uniforms.u_world_box_max = sglSubV3S(c.box.max, sz[0] * 0.05);
			uniforms.u_color         = ((c.isLeaf) ? ([1.0, 1.0, 0.0]) : ([1.0, 1.0, 1.0]));
			this._boxRenderer.setUniforms(uniforms);
			this._boxRenderer.render();
		}
	},

	_renderNodesHalfResBoxes : function(mvp)
	{
		var uniforms = {
			u_model_view_projection_matrix : mvp
		};

		this._boxRenderer.begin();
			this._boxRenderer.setUniforms(uniforms);
			this._boxRenderer.beginMesh(this._boxMesh);
				this._boxRenderer.beginPrimitives("edges");
				var nc = null;
				for (var i in this._toRenderHalfRes)
				{
					nc = this._toRenderHalfRes[i];
					this._renderNodeHalfResBox(nc.n, nc.children);
				}
				this._boxRenderer.endPrimitives();
			this._boxRenderer.endMesh();
		this._boxRenderer.end();
	},

	_renderNodesBoxes : function(mvp)
	{
		this.gl.lineWidth(2.0);
		this.gl.depthFunc(this.gl.LEQUAL);
		this._renderNodesFullResBoxes(mvp);
		this._renderNodesHalfResBoxes(mvp);
		this.gl.depthFunc(this.gl.LESS);
		this.gl.lineWidth(1.0);
	},

	_doRender : function(projectionMatrix, modelViewMatrix, viewport)
	{
		var mv = sglMulM4(modelViewMatrix, this._treeTransform);
		//var mv = modelViewMatrix;

		this._timestamp++;
		this._frustum.setup(projectionMatrix, mv, viewport);

		this._collectNodes();
		var mvp = sglMulM4(projectionMatrix, mv);

		if (this.renderData)
			this._renderNodes(mvp);

		if (this.renderBoxes) 
			this._renderNodesBoxes(mvp);
		
		if (this.lg && this.lgtex != null)
		{
			this.gl.enable(this.gl.BLEND);
			this.gl.blendFunc(this.gl.SRC_ALPHA, this.gl.ONE_MINUS_SRC_ALPHA);
			var mvp2 = sglMulM4(projectionMatrix, sglScalingM4C(1.0, 1.0, 1.0));
			var quadUniforms = { u_mvp : mvp2 };
			var quadSamplers = { s_texture : this.lgtex };
			sglRenderMeshGLPrimitives(this.lgMesh, "tristrip", this.texProg, null, quadUniforms, quadSamplers);
			this.gl.disable(this.gl.BLEND);
		}
		//this.gl.finish();
	},

	_updateCache : function()
	{
		var maxUpdatesPerFrame = 1;
		var readyItems = this._readyItems.slice(0, maxUpdatesPerFrame);
		this._readyItems.splice(0, maxUpdatesPerFrame);

		var combined = this._cache.concat(readyItems);
		combined.sort(_MultiResCompareNodes);

		var cl = combined.length;
		var k  = (this._maxCacheSize < cl) ? (this._maxCacheSize) : (cl);

		this._cache = combined.splice(0, k);
		var outOfCache = combined;

		var n = null;
		var r = null;
		var failed = [ ];

		for (var i=0; i<k; ++i) 
		{
			n = this._cache[i];
			r = n.req;
			n.req = null;

			if (!r) continue;
			if (!r.succeeded)
			{
				failed.push(i);
				continue;
			}

			var cimgs = new Array(r.requests.length);
			for (var i=0; i<cimgs.length; ++i)
				cimgs[i] = r.requests[i].image;
				
			if (n.data)
				this._destroyData(n.data);
			
			n.data = this._createData(cimgs);
		}

		var fc = failed.length;
		var reinsertedCount = 0;

		k = outOfCache.length;
		for (var i=0; i<k; ++i)
		{
			n = outOfCache[i];
			r = n.req;
			n.req = null;

			if (r) continue;

			if (reinsertedCount < fc) 
			{
				this._cache[failed[reinsertedCount]] = n;
				reinsertedCount++;
			}
			else
			{
				this._destroyData(n.data);
				n.data = null;
			}
		}

		if (reinsertedCount > 0) 
			this._cache.sort(_MultiResCompareNodes);
	},

	
	_requestNodes : function() 
	{
		var cs = this._cache.length;
		var lastItem = (cs > 0) ? (this._cache[cs-1]) : (null);

		this._toRequest.sort(_MultiResCompareNodes);
		var requestableCount = this._maxOngoingRequests - this._currentOngoingRequests;

		var requested = 0;
		var k = this._toRequest.length;
		var n = null;
		var canRequest = false;

		for (var i=0; ((i<k) && (requested<requestableCount)); ++i) 
		{
			n = this._toRequest[i];
			if (n.req) continue; // ongoing;

			canRequest = true;
			if ((lastItem != null) && (cs >= this._maxCacheSize)) 
				canRequest = (_MultiResCompareNodes(n, lastItem) < 0);
			
			if (canRequest)
			{
				this._requestNode(n);
				requested++
			}
		}
	},

	
	_calculateNormalizedTreeTransform : function() 
	{
		var root = this._tree.root;
		if (!root) return sglIdentityM4();

		var rbox = new SglBox3();
		rbox.min = sglAddV3(sglMulV3(root.box.min, this._tree.scale), this._tree.offset);
		rbox.max = sglAddV3(sglMulV3(root.box.max, this._tree.scale), this._tree.offset);
		//rbox = root.box;

		this._treeTransform = sglMulM4(sglTranslationM4V(this._tree.offset), sglScalingM4V(this._tree.scale));
		//this._treeTransform = sglIdentityM4();

		var bc = rbox.center;
		var bs = rbox.size;

		var maxDim = bs[0];
		if (maxDim < bs[1]) maxDim = bs[1];
		if (maxDim < bs[2]) maxDim = bs[2];

		var scale = (maxDim > 0.0) ? (1.0 / maxDim) : (1.0);

		var s = sglScalingM4C(scale, scale, scale);
		var t = sglTranslationM4C(-bc[0], -bc[1], -bc[2]);

		var xform = sglMulM4(s, t);
		this._normalizedTreeTransform = xform;
		/*
		//var r  = sglRotationAngleAxisM4C(sglDegToRad(-90.0), 1.0, 0.0, 0.0);
		var r  = sglIdentityM4();
		var t1 = sglTranslationM4C(0.0, 0.0, bs[2] / 2.0 * scale);
		var s  = sglScalingM4C(scale, scale, scale);
		var t2 = sglTranslationM4C(-bc[0], -bc[1], -bc[2]);
		this._normalizedTreeTransform = sglMulM4(r, sglMulM4(t1, sglMulM4(s, t2)));
		*/
	},

	_setupTree : function() 
	{
		var root = this._tree.root;
		if (!root) return;

		var gl = this.gl;

		var csize = ((this._tree.tileSize + 2) * (this._tree.tileSize + 2)) * this.ordlen * 3;

		this._maxCacheSize = sglFloor(this._cacheSizeInBytes / (csize));
		if (this._maxCacheSize <= 0) this._maxCacheSize = 1;

		var quadPositions = new Float32Array([
			-0.5,  0.5,
			-0.5, -0.5,
			 0.5,  0.5,
			 0.5, -0.5
		]);

		var quadMesh = new SglMeshGL(gl);
		quadMesh.addVertexAttribute("position", 2, quadPositions);
		quadMesh.addArrayPrimitives("tristrip", gl.TRIANGLE_STRIP, 0, 4);

		this._tileFullMesh = quadMesh;
		this._tileHalfMesh = quadMesh;

		this._calculateNormalizedTreeTransform();
	},

	
	render : function(projectionMatrix, modelViewMatrix, viewport)
	{
		if (!this._tree.root) return;

		this._doRender(projectionMatrix, modelViewMatrix, viewport);
		if (this.updateData)
		{
			this._updateCache();
			this._requestNodes();
		}
	},

	
	get normalizedTreeTransform() 
	{
		return this._normalizedTreeTransform;
	}
};
