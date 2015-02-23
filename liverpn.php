<?php

/**
 * This liverpn.php script is used to simulate the RPN,
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

require_once('nsfRPN.php');
$dologging=true;
$rpn = new nsfRPN();
$qs = $_SERVER["QUERY_STRING"];
$qs = urldecode($qs);
$p = strpos($qs, "exp=");
if (!($p === false)){
	$exp = substr($qs, $p+4);
} else {
	$res = Array();
	$res["res"]="err";
	$res["err"]="EXP ERR";
	generate_response($res);
}

if (!preg_match("/[ -~]/", $exp)){
	$res = Array();
	$res["res"]="err";
	$res["err"]="EXP ERR";
	generate_response($res);

}

$result = $rpn->rpn($exp);

$res = Array();
$res["res"]="ok";
$res["rpn"]=$rpn->rpn;
$res["result"]=$rpn->res;
generate_response($res);

function generate_response($response)
{
	if(ob_get_length()) {
		ob_clean();
	}
	header('Content-Type: application/json');
	echo json_encode($response);
	ob_end_flush();
	exit();
}

?>
