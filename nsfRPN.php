<?php

/** 
 * nsfRPN.php: This PHP class implements a Reverse Polish notation (RPN) statement that supports numeric,
 * strings and user defined functions as well as built-in PHP functions for numeric/string manipulation.
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

if (!function_exists('is_word')) {
	function is_word($str){
		if (($str[0] == '"') && ($str[strlen($str)-1]) == '"'){
			return true;
		}
		if (($str[0] == "'") && ($str[strlen($str)-1]) == "'"){
			return true;
		}
		return false;
	}
}

class nsfRPN {
	const RPN_UNLIMIT = 999; // When registering a function that accepts unlimited arguments
	
	public $input='';
	public $rpn='';
	public $res='';
	public $err=0;
	
	const RPN_ERR_PARAMS = 1;  // Wrong number of params for a function
	const RPN_ERR_DIVZERO = 2; // Division by Zero
	const RPN_ERR_NUMREQ = 3;  // Numeric is expected
	const RPN_ERR_OPERAND = 4; // Operand is expected, might be non existance function

	const RPN_OPERAND = 0;
	const RPN_OPERATOR = 1;
	const RPN_FUNCTION = 2;
	
	private $operators = array('^' => 1, '*' => 2, '/' => 2, '+' => 3, '-' => 3);
	private $functions;
	private $user_callback_obj;
	private $user_callback_func;	
	private $rpnar;
	
	function __construct() {
		$this->functions = array();
		$this->functions['upper'] 		  = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'strtoupper');
		$this->functions['lower'] 		  = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'strtolower');
		$this->functions['substr']		  = array('minparams' => 2, 'maxparams' => 3, 'callback' => 'substr');
		$this->functions['date']   		  = array('minparams' => 1, 'maxparams' => 2, 'callback' => 'date');
		$this->functions['time']   		  = array('minparams' => 0, 'maxparams' => 0, 'callback' => 'time');
		$this->functions['strtotime']     = array('minparams' => 1, 'maxparams' => 2, 'callback' => 'strtotime');
		$this->functions['number']        = array('minparams' => 1, 'maxparams' => 4, 'callback' => 'number_format');
		$this->functions['wrap']          = array('minparams' => 1, 'maxparams' => 4, 'callback' => 'wordwrap');
		
		$this->functions['max']    = array('minparams' => 2, 'maxparams' => self::RPN_UNLIMIT, 'callback' => 'max');
		$this->functions['min']    = array('minparams' => 2, 'maxparams' => self::RPN_UNLIMIT, 'callback' => 'min');
		
		$this->functions['sin']    = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'sin');
		$this->functions['cos']    = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'cos');
		$this->functions['tan']    = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'tan');
		$this->functions['atan']   = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'atan');
		$this->functions['md5']    = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'md5');

		// User defined functions not defined in PHP, provided by this class
		$this->functions['negate'] = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'negate');
		$this->functions['hash1']  = array('minparams' => 1, 'maxparams' => 1, 'callback' => 'hash1');
		$this->functions['hash256']= array('minparams' => 1, 'maxparams' => 1, 'callback' => 'hash256');
		$this->functions['word']   = array('minparams' => 2, 'maxparams' => 3, 'callback' => 'my_word');
		
		$this->input = '';
		$this->user_callback_obj  = null;
		$this->user_callback_func = '';	
		$this->rpnar = array();
	}

	/**
	 * Main RPN processing method
	 * $string - holds the statement to be calculated, an be a numeric of string
	 * returns the calculation result
	 */
	function rpn($string){
		$string = str_replace(array("\'", '\"'), array("'", '"'), $string);
		$this->input = $string;
		$this->res = '';
	
		if ($rpn = $this->str2rpn()){
			if (!($this->rpn2res() === false)){
				return $this->res;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * register a user defined function
	 * $function - function name used in the Reverse Polish Notation input
	 * $callback - name of the function to call for processing
	 * $minparams - minimum parameters the function recieves
	 * $maxparams - maxmimum parameters the function can recieve
	 * @return boolean
	 */
	function register($function, $callback, $minparams, $maxparams=null)
	{
		if (! function_exists ($callback))
			return false;
		
		if (!is_null($maxparams)  && ($maxparams < $minparams))
			return false;
		
		$this->functions[$function] = array('minparams' => $minparams, 'maxparams' => $maxparams, 'callback' => $callback);
		
		return true;
	}
	
	/**
	 * user defined function for vairable evaluation
	 * $function - function name used in the Reverse Polish Notation input
	 * $callback - name of the function to call for processing
	 * $minparams - minimum parameters the function recieves
	 * $maxparams - maxmimum parameters the function can recieve
	 * @return boolean
	 */
	function user_callback($func, $obj=null)
	{
		if ($obj){
			if (!method_exists($obj,$func)){
				return false;
			}
			$this->user_callback_obj  = $obj;
			$this->user_callback_func = $func;
		} else {
			if (!function_exists($func)){
				return false;
			}
			$this->user_callback_obj  = null;
			$this->user_callback_func = $func;
		}
		return true;
	}
	
		
	/**
	 * str2rpn - Method that converts the input string into Reverse Polish Notation statement
	 * $str - $this->input
	 * @return rtn - reverse polish notation
	 * 
	 * This function has been inspired by the function, convertToRPN($equation), written by RMcLeod.
	 * http://stackoverflow.com/users/200856/rmcleod
	 * 
	 * The initial function only dealt with numeric arguments.
	 * I have added support for strings and functions, amoung other things :)
	 * 
	 */
	private function str2rpn(){
		$tokens = token_get_all('<?php ' . $this->input);
		array_shift($tokens);

		$this->err = 0;
		$rpn = '';
		$unary='';
		$prev_op = false;
		$this->rpnar = array();
		$rpnaridx=0;
		$stack = array();
		$ident = 0;
		$fpcntar = array();
		$fpcnt = false;
		
		$last_dl = false;
		foreach($tokens as $idx => $token){
//			$tkn1 = $token;
//			$tkn2 = $token[1];
			if (is_array($token)){
				$token[1] = trim($token[1]);
				if ($token[1] == null){
					$fpcnt = true;
					continue;
				}
				if ($last_dl){
					$rpn = rtrim($rpn).$token[1] . ' ';
					$this->rpnar[key($this->rpnar)][1] .= $token[1];
					$last_dl = false;
					continue;
				}
				
				$token_func = array_key_exists($token[1], $this->functions);
				if (!$token_func && ($token[1][0] == '$')){
					$last_dl = true;
				}
			} else {
				$token = trim($token);
				if (($token == null) || ($token == ',')){
					$fpcnt = true;
					continue;
				}
				if ($last_dl){
					if ($token == '.'){
						$rpn = rtrim($rpn).'.' . ' ';
						$this->rpnar[key($this->rpnar)][1] .= '.';
						continue;
					} else {
						$last_dl = false;
					}
				}
				
				$token_func = array_key_exists($token, $this->functions);
			}
			if(is_array($token) && !$token_func) {
				if ($unary != ''){
					$rpn .= $unary.$token[1] . ' ';
					$this->rpnar[] = array(self::RPN_OPERAND, trim($unary.$token[1], '"\''));
				} else {
					if (is_numeric($token[1]) || is_word($token[1]) || ($token[1][0] == '$')){
						$rpn .= $token[1] . ' ';
						$this->rpnar[] = array(self::RPN_OPERAND, trim($token[1], '"\''));
					} else {
						$this->err = self::RPN_ERR_OPERAND;
						$this->rpn = trim($rpn);
						$this->res = null;
						return false;
					}
				}
				if ($fpcnt){
					if (isset($fpcntar[$ident])){
						$fpcntar[$ident]++;
					} else {
						$fpcntar[$ident]=1;
					}
				}
				$unary = '';
				$prev_op = false;
			} else {
				if(is_array($token)){
					if (array_key_exists($token[1], $this->operators) && ($prev_op || ($idx==0)))
					{	$unary = $token;
						continue;
					}
				} else {
					if (array_key_exists($token, $this->operators) && ($prev_op || ($idx==0)))
					{	$unary = $token;
						continue;
					}
				}
								
				if(empty($stack) || ($token == '(') || $token_func){
					if (is_array($token)){
						$stack[] = $token[1];
					} else {
						$stack[] = $token;
					}
					$prev_op = true;
					
					if ($token_func && $fpcnt){
						if (!array_key_exists($ident, $fpcntar)){
							$fpcntar[$ident] = 1;
						} else {
							$fpcntar[$ident]++;
						}
					}
					if ($token == '('){
						$ident++;
						$fpcnt = true;
					}
				} else {
					$fpcnt = false;
					if($token == ')') {
						while(end($stack) != '(') {
							$stk = array_pop($stack);
							if (array_key_exists($stk, $this->functions)){
								$extraparams = $fpcntar[$ident] > $this->functions[$stk]['minparams'];
								$this->rpnar[] = array(self::RPN_FUNCTION, array($stk,$fpcntar[$ident]));

								unset($fpcntar[$ident]);
								$ident--;

								if ($extraparams){
									$stk .= '_'.$fpcntar[$ident];
								}
							} else {
								$this->rpnar[] = array(self::RPN_OPERATOR, $stk);
							}
							$rpn .= $stk . ' ';
						}
	
						array_pop($stack);
						
						while(!empty($stack) && (end($stack) != '(')) {
							$stk = array_pop($stack);
							$token_func = array_key_exists($stk, $this->functions);
							if ($token_func){
								if (array_key_exists($stk, $this->functions)){
									if (($fpcntar[$ident] < $this->functions[$stk]['minparams']) || ($fpcntar[$ident] > $this->functions[$stk]['maxparams'])){
										$this->err = self::RPN_ERR_PARAMS;
										$this->rpn = trim($rpn);
										return false;
									} 									
									
									$extraparams = $fpcntar[$ident] > $this->functions[$stk]['minparams'];
									$this->rpnar[] = array(self::RPN_FUNCTION, array($stk,$fpcntar[$ident]));
									
									if ($extraparams){
										$stk .= '_'.$fpcntar[$ident];
									}
									unset($fpcntar[$ident]);$ident--;
								}
							} else {
								$this->rpnar[] = array(self::RPN_OPERATOR, $stk);
							}
							$rpn .= $stk . ' ';
						}
					} else {
						while(!empty($stack) && (end($stack) != '(') && ($this->operators[$token] >= $this->operators[end($stack)])) {
							$stk = array_pop($stack);
							if (array_key_exists($stk, $this->functions)){
								$extraparams = $fpcntar[$ident] > $this->functions[$stk]['minparams'];
								$this->rpnar[] = array(self::RPN_FUNCTION, array($stk,$fpcntar[$ident]));
								
								unset($fpcntar[$ident]);
								$ident--;

								if ($extraparams){
									$stk .= '_'.$fpcntar[$ident];
								}
							} else {
								$this->rpnar[] = array(self::RPN_OPERATOR, $stk);
							}
							$rpn .= $stk . ' ';
						}
						$stack[] = $token;
						$prev_op = true;
					}
				}
			}
		}
	
		while(!empty($stack)) {
			$fpcnt = false;
			$stk = array_pop($stack);
			if (array_key_exists($stk, $this->functions)){
				$extraparams = $fpcntar[$ident] > $this->functions[$stk]['minparams'];
				$this->rpnar[] = array(self::RPN_FUNCTION, array($stk,$fpcntar[$ident]));
				
				unset($fpcntar[$ident]);
				$ident--;

				if ($extraparams > 0){
					$stk .= '_'.$fpcntar[$ident];
				}
			} else {
				$this->rpnar[] = array(self::RPN_OPERATOR, $stk);
			}
			$rpn .= $stk . ' ';
		}
	
		$this->rpn = trim($rpn);
		return $this->rpn;
	}
	

	/**
	 * 
	 * rpn2res - calculates the RPN - Reverse Polish Notation
	 * @return calculation result
	 */
	private function rpn2res(){
		$calc = array();
		$params = array();
		while(current($this->rpnar)){
			$curr = current($this->rpnar);
			switch($curr[0]){
				case self::RPN_OPERAND:{
					if (($curr[1][0] == '$') && ($this->user_callback_func != '')){
						if (is_null($this->user_callback_obj)){
							$calc[] = call_user_func_array($this->user_callback_func, array(substr($curr[1],1)));
						} else {
							$calc[] = call_user_func_array(array($this->user_callback_obj, $this->user_callback_func), array(substr($curr[1],1)));
						}				
					} else {
						$calc[] = $curr[1];
					}
					next($this->rpnar);
				} break;
				case self::RPN_OPERATOR:{
					switch($curr[1]){
						case '+':{
							$op2 = array_pop($calc);
							$op1 = array_pop($calc);
							if (is_numeric($op1) && is_numeric($op2)){
									$this->res = $op1+$op2;
								} else {
									$this->res = $op1.$op2;
								}
						} break;
						case '-':{
							$op2 = array_pop($calc);
							$op1 = array_pop($calc);
							if (is_numeric($op1) && is_numeric($op2)){
									$this->res = $op1-$op2;
								} else {
									$this->err = self::RPN_ERR_NUMREQ;
									$this->rpn = trim($rpn);
									$this->res = null;
									return false;
								}
						} break;
						case '*':{
							$op2 = array_pop($calc);
							$op1 = array_pop($calc);
							if (is_numeric($op1) && is_numeric($op2)){
								$this->res = $op1*$op2;
							} else {
								$this->err = self::RPN_ERR_NUMREQ;
								$this->rpn = trim($rpn);
								$this->res = null;
								return false;
							}
						} break;
						case '/':{
							$op2 = array_pop($calc);
							$op1 = array_pop($calc);
							if (is_numeric($op1) && is_numeric($op2)){
								if ($op2 != 0){
									$this->res = $op1/$op2;
								} else {
									$this->err = self::RPN_ERR_DIVZERO;
									$this->rpn = trim($rpn);
									$this->res = null;
									return false;									
								}
							} else {
								$this->err = self::RPN_ERR_NUMREQ;
								$this->rpn = trim($rpn);
								$this->res = null;
								return false;									
							}
						} break;
						case '^':{
							$op2 = array_pop($calc);
							$op1 = array_pop($calc);
							if (is_numeric($op1) && is_numeric($op2)){
								$this->res = pow($op1,$op2);
							} else {
								$this->err = self::RPN_ERR_NUMREQ;
								$this->rpn = trim($rpn);
								$this->res = null;
								return false;									
							}
						} break;
					}
					
					$calc[] = $this->res;
					next($this->rpnar);
				} break;
				case self::RPN_FUNCTION:{
					$funcn = $curr[1][0];
					$funcp = $curr[1][1];
					
					$params = array_slice($calc, count($calc)-$funcp, $funcp);
					$calc = array_slice($calc, 0, count($calc)-$funcp);
					
					$res = call_user_func_array($this->functions[$funcn]['callback'], $params);
					$calc[] = $res;
					
					$params = array();
					next($this->rpnar);
				} break;
			}
		}
		$this->res = $calc[0];
		return $this->res;
	}
}

// Here are few functions not implemented in PHP

// Calculates the hash-1 of a given string
function hash1($string){
	return hash('sha1', $string);
}

// Calculates the hash-256 of a given string
function hash256($string){
	return hash('sha256', $string);
}

// Retruns the N's word of a given string 
// $string - the string
// $word - The N's word required
// $charlist - A list of additional characters which will be considered as 'word'
function my_word($string, $word, $charlist=null){
	$words = str_word_count($string,1, $charlist);
	return $words[$word-1];
}

// Returns the negative of the input number.
function negate($number){
	return -$number;
}

?>
