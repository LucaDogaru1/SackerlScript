<?php
require_once 'Lexer.php';
require_once 'Parser.php';
require 'Evaluator.php';

class Interpreter
{
    private array $ast;
    private array $env = [];
    private string $fileContent;


    public function __construct(string $fileContent)
    {
        $this->fileContent = $fileContent;
    }

    public function evaluate(): void
    {
        $this->prepareForEvaluator();
        foreach ($this->ast as $node) {
            $evaluator = new Evaluator($node, $this->env);
            try {
                $evaluator->evaluate();
            } catch (Exception $e) {
                echo "Unknown node type: " . $e->getMessage() . "\n";
            }
        }
    }

    private function prepareForEvaluator(): void
    {
        $lexer = new Lexer($this->fileContent);
        $tokens = $lexer->tokenize();

        $parser = new Parser($tokens);
        $this->ast = $parser->parseCodeBlock(0);
    }

}