/** 
 * This liverpn.js is a jQuery that is used to simulate the RPN, 
 * Reverse Polish notation (RPN) statement calculation as an example for usage.
 * 
 * Written by Ze'ev Cohen (zeevc AT egpo DOT net)
 * http://dev.egpo.net
 * https://github.com/egpo
 * 
 * 
 * License: The MIT License (MIT)
 * 
 * Copyright (c) 2014 Ze'ev Cohen (zeevc@egpo.net)
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * http://opensource.org/licenses/MIT
 */

$( document ).ready(function() {

	$("#evaluate").click( function() {
	    if ($("#expression").val() != "") {
	    	var v = encodeURIComponent($("#expression").val());
	    	$.getJSON( "liverpn.php", { exp: $("#expression").val() })
				.done(function( json ) {
					switch(json.res) {
						case "err": {
							
						} break;
				    	case "ok": {
				    		$("#rpn").html(json.rpn);
				    		$("#res").html(json.result);
				    	} break;
				    }
				});
			};
		});
	});
