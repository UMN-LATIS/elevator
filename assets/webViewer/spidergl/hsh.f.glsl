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

#ifdef GL_ES
precision highp float;
#endif

uniform int ordlen;

uniform sampler2D coeff0;
uniform sampler2D coeff1;
uniform sampler2D coeff2;
uniform sampler2D coeff3;
uniform sampler2D coeff4;
uniform sampler2D coeff5;
uniform sampler2D coeff6;
uniform sampler2D coeff7;
uniform sampler2D coeff8;

uniform float lweight0;
uniform float lweight1;
uniform float lweight2;
uniform float lweight3;
uniform float lweight4;
uniform float lweight5;
uniform float lweight6;
uniform float lweight7;
uniform float lweight8;

uniform float gmax0;
uniform float gmax1;
uniform float gmax2;
uniform float gmax3;
uniform float gmax4;
uniform float gmax5;
uniform float gmax6;
uniform float gmax7;
uniform float gmax8;

uniform float gmin0;
uniform float gmin1;
uniform float gmin2;
uniform float gmin3;
uniform float gmin4;
uniform float gmin5;
uniform float gmin6;
uniform float gmin7;
uniform float gmin8;

uniform float leftTex;
uniform float rightTex;
uniform float bottomTex;
uniform float topTex;


varying vec2 v_texcoord;
varying	vec2 v_world_texcoord;


void main(void)
{
	vec3 color = vec3(0.0);
	if (v_world_texcoord.x <= leftTex || v_world_texcoord.x >= rightTex || v_world_texcoord.y <= bottomTex || v_world_texcoord.y >= topTex)
	{
		gl_FragColor = vec4(0.0, 0.0, 0.0, 1.0);
		return;
	}
		
	vec3 rgbCoeff = texture2D(coeff0, v_texcoord).xyz;
	rgbCoeff = rgbCoeff * gmin0 + gmax0;
	color += (rgbCoeff * lweight0);
	
	rgbCoeff = texture2D(coeff1, v_texcoord).xyz;
	rgbCoeff = rgbCoeff * gmin1 + gmax1;
	color += (rgbCoeff * lweight1);
	
	rgbCoeff = texture2D(coeff2, v_texcoord).xyz;
	rgbCoeff = rgbCoeff * gmin2 + gmax2;
	color += (rgbCoeff * lweight2);
	
	rgbCoeff = texture2D(coeff3, v_texcoord).xyz;
	rgbCoeff = rgbCoeff * gmin3 + gmax3;
	color += (rgbCoeff * lweight3);
	
	if (ordlen > 4)
	{
		rgbCoeff = texture2D(coeff4, v_texcoord).xyz;
		rgbCoeff = rgbCoeff * gmin4 + gmax4;
		color += (rgbCoeff * lweight4);
		
		rgbCoeff = texture2D(coeff5, v_texcoord).xyz;
		rgbCoeff = rgbCoeff * gmin5 + gmax5;
		color += (rgbCoeff * lweight5);
		
		rgbCoeff = texture2D(coeff6, v_texcoord).xyz;
		rgbCoeff = rgbCoeff * gmin6 + gmax6;
		color += (rgbCoeff * lweight6);
		
		rgbCoeff = texture2D(coeff7, v_texcoord).xyz;
		rgbCoeff = rgbCoeff * gmin7 + gmax7;
		color += (rgbCoeff * lweight7);
		
		rgbCoeff = texture2D(coeff8, v_texcoord).xyz;
		rgbCoeff = rgbCoeff * gmin8 + gmax8;
		color += (rgbCoeff * lweight8);
	}
	gl_FragColor = vec4(color, 1.0);
}
