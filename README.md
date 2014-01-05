rpn - Reverse Polish notation PHP class
===
This PHP class implements a Reverse Polish notation (RPN) evaluation that supports numeric, strings and user defined functions as well as built-in PHP functions for numeric/string manipulation.

Definition - what is RPN?
===
(http://en.wikipedia.org/wiki/Reverse_Polish_notation)
Reverse Polish Notation (RPN) is a mathematical notation in which every operator follows all of its operands. It is also known as postfix notation and is parenthesis-free as long as operator arities are fixed. The description "Polish" refers to the nationality of logician Jan Łukasiewicz, who invented (prefix) Polish notation in the 1920s. For more info, please visit WikipediA.

Class Variables
===
Holds a copy of the input string when the class has been called:
```
public $input='';
```
Holds the Reverse Polish notation conversion:
```
public $rpn='';
```
Holds the result of the Polish Notation evaluation:
```
public $res='';
```
Holds the operators available to calculation with their weight
private:
```
$operators = array('^' => 1, '*' => 2, '/' => 2, '+' => 3, '-' => 3);
```
Array with all the available functions, including the user defined functions:
```
private $functions;
```
Internal working array to have the calculation done fast:
```
private $rpnar;
```

Class Methods
===


In this README
function rpn($string)


