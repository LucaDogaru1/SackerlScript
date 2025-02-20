<?php

class Evaluator
{
    private array $node;
    private Environment $env;

    public function __construct(array $node, Environment $env)
    {
        $this->node = $node;
        $this->env = $env;
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
                $var = $this->env->getVariable($this->node['name']);
                if ($var !== null) {
                    return $var;
                }

                $func = $this->env->getFunction($this->node['name']);
                if ($func !== null) {
                    return $func;
                }

                throw new Exception($this->node["name"] . " is not defined => unknown identifier" . "\n");

            case 'assignment':
            case 'variable':
                $varName = $this->node['name'];
                $varValue = (new Evaluator($this->node['value'], $this->env))->evaluate();
                $this->env->defineVariable($varName, $varValue);
                return $varValue;

            case 'arithmeticOperation' :
                $left = $this->evaluateOperand($this->node['left']);
                $right = $this->evaluateOperand($this->node['right']);
                switch ($this->node['operator']) {
                    case 'plus' :
                        return $left + $right;
                    case 'minus' :
                        return $left - $right;
                    case 'mal' :
                        return $left * $right;
                    case 'dividier' :
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

            case 'function' :
                $functionName = $this->node['functionName'];
                $parameters = $this->node['parameters'];
                $body = $this->node['body'];
                $this->env->defineFunction($functionName, $body, $parameters);
                return null;

            case 'functionCall':
                $funcName = $this->node['functionName'];
                $func = $this->env->getFunction($funcName);
                if (!$func) throw new Exception("functionName '" . $funcName . "' undefined");
                $args = [];
                if (count($this->node['arguments']) > 0) {
                    foreach ($this->node['arguments'] as $arg) {
                        $args[] = (new Evaluator($arg[0], $this->env))->evaluate();
                    }
                }
                return $this->executeFunction($func, $args);

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
            if ($operand['type'] === 'identifier') {
                $value = $this->env->getVariable($operand['name']);
                if ($value === null) {
                    throw new Exception("Undefined variable: " . $operand['name']);
                }
                return $value;
            }

            if ($operand['type'] === 'literal') {
                return $operand['value'];
            }

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

    private function checkIfBoolean($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            return $value;
        }
    }

    /**
     * @throws Exception
     */
    private function executeFunction($func, array $args)
    {

        $body = $func['body'];
        $parameters = $func['parameters'] ?? [];

        if (!is_array($parameters)) {
            throw new Exception("Function parameters must be an array.");
        }

        if (count($args) !== count($parameters)) {
            return throw new Exception("Too few arguments parsed for '" . $this->node['functionName'] . "'.");
        }


        $funcEnv = new Environment();
        $funcEnv->setParent($this->env);


        if (count($parameters) > 0) {
            foreach ($parameters as $index => $param) {
                if (!isset($param['name'])) {
                    throw new Exception("Malformed parameter definition: " . json_encode($param));
                }
                $funcEnv->defineVariable($param['name'], $args[$index]);
            }
        }


        $result = null;
        foreach ($body as $statement) {
            $result = (new Evaluator($statement, $funcEnv))->evaluate();
        }

        return $result;
    }

}