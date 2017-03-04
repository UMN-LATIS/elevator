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

uniform   mat4 u_model_view_projection_matrix;
uniform   vec3 u_world_box_min;
uniform   vec3 u_world_box_max;
uniform   vec4 u_scale_bias;
uniform   vec2 u_texcoord_scale_bias;

attribute vec2 a_position;

varying   vec2 v_texcoord;
varying	  vec2 v_world_texcoord;


void main(void)
{
	vec3 world_box_size  = u_world_box_max - u_world_box_min;
	vec3 vert            = vec3(a_position + vec2(0.5), 1.0);

	vec2 texcoord  = vert.xy * u_scale_bias.xy + u_scale_bias.zw;
	texcoord = texcoord * u_texcoord_scale_bias.x + u_texcoord_scale_bias.y;

	vec3 world_pos = vert * world_box_size + u_world_box_min;

	v_texcoord  = texcoord;
	v_world_texcoord = world_pos.xy;
	gl_Position = u_model_view_projection_matrix * vec4(world_pos, 1.0);
}
