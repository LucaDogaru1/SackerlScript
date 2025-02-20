<?php
require_once 'Interpreter.php';
require_once 'Environment.php';


$fileContent = file_get_contents('./index.oida');
$env = new Environment();
$interpreter = new Interpreter($fileContent, $env);

$interpreter->evaluate();



