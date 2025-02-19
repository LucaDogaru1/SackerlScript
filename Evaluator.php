<?php

class Evaluator
{
    private array $node;
    private array $env;

    public function __construct(array $node, array &$env)
    {
        $this->node = $node;
        $this->env = &$env;
    }

    /**
     * @throws Exception
     */
    public function evaluate()
    {

        switch ($this->node['type']) {

            case 'literal':
                return $this->checkIfBoolean($this->node['value']);

            case 'identifier' :
                if (isset($this->env[$this->node['name']])) {
                    return $this->env[$this->node['name']];
                } else {
                    throw new Exception( $this->node["name"] . ": is not defined => unknown identifier". "\n");
                }

            case 'assignment':
            case 'variable' :
                $varName = $this->node['name'];
                $varValue = (new Evaluator($this->node['value'], $this->env))->evaluate();
                $this->env[$varName] = $varValue;
                return $varValue;

            case 'arithmeticOperation' :
                $left = $this->evaluateOperand($this->node['left']);
                $right = $this->evaluateOperand($this->node['right']);
                switch ($this->node['operator']) {
                    case '+' :
                        return $left + $right;
                    case '-' :
                        return $left - $right;
                    case '*' :
                        return $left * $right;
                    case '/' :
                        if ($right === 0) {
                            throw new Exception("Division by zero");
                        }
                        return $left / $right;
                    default:
                        throw new Exception("Unknown operator: " . $this->node['operator']);
                }

            case 'print':
                $this->evaluateMultipleExpressionsInPrint($this->node['value']);
                return null;

            default:
                throw new Exception("Unknown node type: " . $this->node['type'] . "\n");
        }
    }


    /**
     * @throws Exception
     */
    private function evaluateOperand($operand): float|int
    {
        if (is_array($operand) && isset($operand['type'])) {
            return (new Evaluator($operand, $this->env))->evaluate();
        }

        return $operand;
    }

    /**
     * @throws Exception
     */
    private function evaluateMultipleExpressionsInPrint($values): void
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        if (is_array($values)) {
            foreach ($values as $value) {
                $evaluatedValue = (new Evaluator($value, $this->env))->evaluate();
                echo $evaluatedValue . " ";
            }
            echo "\n";
        }
    }

    private function checkIfBoolean($value) {
        if(is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            return $value;
        }
    }

}