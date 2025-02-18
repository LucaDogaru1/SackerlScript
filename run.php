<?php
require_once 'Interpreter.php';


$fileContent = file_get_contents('./index.oida');
$interpreter = new Interpreter($fileContent);

$interpreter->evaluate();



