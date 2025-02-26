<?php
require_once 'Lexer.php';
require_once 'Parser.php';
require 'Evaluator.php';

class Interpreter
{
    private array $ast;
    private Environment $env;
    private string $fileContent;


    public function __construct(string $fileContent, Environment $env)
    {
        $this->fileContent = $fileContent;
        $this->env = $env;
    }

    /**
     * @throws Exception
     */
    public function evaluate(): void
    {
        $this->prepareForEvaluation();
        foreach ($this->ast as $node) {
            $evaluator = new Evaluator($node, $this->env);
            try {
                $evaluator->evaluate();
            } catch (Exception $e) {
                echo "Unknown node type: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * @throws Exception
     */
    private function prepareForEvaluation(): void
    {
        $lexer = new Lexer($this->fileContent);
        $tokens = $lexer->tokenize();

        $parser = new Parser($tokens);
        $this->ast = $parser->parseCodeBlock(0)[0];
    }

}