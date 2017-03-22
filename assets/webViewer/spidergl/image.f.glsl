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

uniform sampler2D coeff0;

uniform float leftTex;
uniform float rightTex;
uniform float bottomTex;
uniform float topTex;


varying vec2 v_texcoord;
varying	vec2 v_world_texcoord;


void main(void)
{
	if (v_world_texcoord.x <= leftTex || v_world_texcoord.x >= rightTex || v_world_texcoord.y <= bottomTex || v_world_texcoord.y >= topTex)
	{
		gl_FragColor = vec4(0.0, 0.0, 0.0, 1.0);
		return;
	}
		
	vec3 color = texture2D(coeff0, v_texcoord).xyz;
	gl_FragColor = vec4(color, 1.0);
}
