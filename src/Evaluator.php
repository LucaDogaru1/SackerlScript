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

            case 'return':
                if(!isset($this->node['value'])) return null;
                return (new Evaluator($this->node['value'], $this->env))->evaluate();

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
                if($this->isVariableArray($varName)) return $this->isVariableArray($varName);
                if($this->isVariableAssoArray($varName)) return $this->isVariableAssoArray($varName);
                if(!empty($this->node['value'])) {
                    $varValue = (new Evaluator($this->node['value'], $this->env))->evaluate();
                    $this->env->defineVariable($varName, $varValue);
                    return $varValue;
                } else {
                    $this->env->defineVariable($varName, []); // for now hared coded array if value after assign is empty
                    return null;
                }


            case 'array_assignment':
                $arrayName = $this->node['array'];
                $index = (new Evaluator($this->node['index'], $this->env))->evaluate();
                $arrayValue = (new Evaluator($this->node['value'], $this->env))->evaluate();
                $array = $this->env->getVariable($arrayName);
                if (!is_array($array)) {
                    throw new Exception("Variable '$arrayName' is not an array.");
                }
                $array[$index] = $arrayValue;
                $this->env->defineVariable($arrayName, $array);
                return $arrayValue;

            case 'array_access':
                $arrayName = $this->node['array'];
                $index = (new Evaluator($this->node['index'], $this->env))->evaluate();
                $array = $this->env->getVariable($arrayName);
                return $array[$index];

            case 'property_access':
                $object = (new Evaluator($this->node['object'], $this->env))->evaluate();
                $property = $this->node['property'];
                if ($property === 'umfang' && is_array($object)) return count($object);
                if ($property === 'ane' && is_array($object)) return $this->handlePushArray();
                if ($property === 'ausse' && is_array($object)) return $this->handleSliceArray();
                if ($property === 'isNumma' && !is_array($object)) return is_int($object);
                if ($property === 'zuNumma' && !is_array($object)) return (int)$object;
                if ($property === 'gibts' && is_array($object)) return $this->existInArray();
                if ($property === 'nimmIrgendwas' && is_array($object)) return $this->takeRandomValue();
                if ($property === 'zuText') return $this->convertToString($object);
                if ($property === 'isText' && !is_array($object)) return is_string($object);
                if ($property === 'isArray') return is_array($object);
                if ($property === 'keinArray') return !is_array($object);
                if ($property === 'ordne' && is_array($object)) return $this->handleSort();
                throw new Exception("Unknown property '$property' for object");


            case 'arithmeticOperation' :
                if (isset($this->node['operand'])) {
                    $left = $this->evaluateOperand($this->node['operand']);
                    switch ($this->node['operator']) {
                        case 'plusplus':
                            $updatedValue = $left + 1;
                            $this->env->defineVariable($this->node['operand']['name'], $updatedValue);
                            return $updatedValue;
                        case 'minusminus':
                            $updatedValue = $left - 1;
                            $this->env->defineVariable($this->node['operand']['name'], $updatedValue);
                            return $updatedValue;
                        default :
                            throw new Exception("Unknown unary operator: " . $this->node['operator']);
                    }
                }
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

            case 'if':
                $body = $this->node['body'];
                $shouldExecute = $this->evaluateConditionOperator($this->node);
                return $this->executeIfBody($shouldExecute, $body);

            case 'forLoop':
                $this->evaluateForLoop();
                return null;

            case 'whileLoop':
                $this->evaluateWhileLoop($this->node);
                return null;

            case 'forEach' :
                $this->evaluateForEachLoop($this->node);
                return null;

            case 'filter':
                return $this->evaluateFilter($this->node);


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
            return $value ? 'basst' : 'sichaned';
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

    /**
     * @throws Exception
     */
    private function executeIfBody(bool $shouldExecute, $body)
    {
        if (!$shouldExecute && isset($this->node['else'])) {
            $body = $this->node['else'];
            $result = null;
            foreach ($body as $statement) {
                $result = (new Evaluator($statement, $this->env))->evaluate();
            }
            return $result;
        } else if (!$shouldExecute) {
            return null;
        }

        $result = null;
        foreach ($body as $statement) {
            $result = (new Evaluator($statement, $this->env))->evaluate();
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    private function evaluateConditionOperator(array $node): bool
    {

        if (empty($node['condition'])) {
            return $this->handleSingleCondition($node);
        }

        $conditions = $node['condition'];

        $result = $this->evaluateComparison(
            $conditions[0],
            $conditions[1][0],
            $conditions[2]
        );


        for ($i = 3; $i < count($conditions); $i += 2) {
            if (!isset($conditions[$i + 1])) {
                throw new Exception("Invalid condition structure");
            }


            $logicalOperator = $conditions[$i][0];


            $nextResult = $this->evaluateComparison(
                $conditions[$i + 1],
                $conditions[$i + 2][0],
                $conditions[$i + 3]
            );


            $result = match ($logicalOperator) {
                'und' => $result && $nextResult,
                'oda' => $result || $nextResult,
                default => throw new Exception("Unknown logical operator: " . $logicalOperator),
            };

            $i += 2;
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function handleSingleCondition(array $condition): bool
    {
        if (isset($condition['left'], $condition['operator'], $condition['right'])) {
            $left = (new Evaluator($condition['left'], $this->env))->evaluate();
            $operator = $condition['operator'];
            $right = (new Evaluator($condition['right'], $this->env))->evaluate();

            return match ($operator) {
                'gleich' => $left == $right,
                'isned' => $left != $right,
                'größer' => $left > $right,
                'klana' => $left < $right,
                'größerglei' => $left >= $right,
                'klanaglei' => $left <= $right,
                default => throw new Exception("Unknown comparison operator: " . $operator),
            };
        }

        if(isset($condition['left']['property'])) {
            return (new Evaluator($condition['left'], $this->env))->evaluate();
        }

        return (new Evaluator($condition['left'], $this->env))->evaluate();
    }

    /**
     * @throws Exception
     */
    private function evaluateComparison(array $left, string $operator, array $right): bool
    {
        $leftValue = (new Evaluator($left, $this->env))->evaluate();

        $rightValue = (new Evaluator($right, $this->env))->evaluate();

        return match ($operator) {
            'klana' => $leftValue < $rightValue,
            'größer' => $leftValue > $rightValue,
            'gleich' => $leftValue === $rightValue,
            'isned' => $leftValue != $rightValue,
            'größerglei' => $leftValue >= $rightValue,
            'klanaglei' => $leftValue <= $rightValue,
            default => throw new Exception("Unknown comparison operator: " . $operator),
        };
    }

    /**
     * @throws Exception
     */
    private function evaluateForLoop(): void
    {
        (new Evaluator($this->node['initialization'], $this->env))->evaluate();

        while ($this->evaluateComparison($this->node['condition']['left'], $this->node['condition']['operator'], $this->node['condition']['right'])) {
            foreach ($this->node['body'] as $body) {
                (new Evaluator($body, $this->env))->evaluate();
            }
            (new Evaluator($this->node['iteration'], $this->env))->evaluate();
        }
    }

    /**
     * @throws Exception
     */
    private function evaluateWhileLoop(array $node): void
    {
        while ($this->evaluateConditionOperator($node)) {
            foreach ($this->node['body'] as $body) {
                (new Evaluator($body, $this->env))->evaluate();
            }
        }
    }

    /**
     * @throws Exception
     */
    private function isVariableArray(string $varName): ?array
    {

        if (isset($this->node['array'])) {
            $varValue = [];
            if(isset($this->node['array']['type']) && $this->node['array']['type'] === 'fetch') {
                $varValue[] = $this->handleFetch();
                $this->env->defineVariable($varName,$varValue);
            } else if (empty($this->node['array'])) {
                $this->env->defineVariable($varName, $varValue);
            } else if (!isset($this->node['array']['type'])) {
                foreach ($this->node['array'] as $value) {
                    $varValue[] = $this->evaluateNestedValue($value);
                }
                $this->env->defineVariable($varName, $varValue);
            }
            return $varValue;
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function isVariableAssoArray(string $varName): ?array
    {
        if(isset($this->node['assoArray'])){
            $arrayKey = [];
            $arrayValue = [];

            foreach ($this->node['assoArray']['key'] as $value) {
                $arrayKey[] = (new Evaluator($value, $this->env))->evaluate();
            }

            foreach ($this->node['assoArray']['value'] as $value) {
                $arrayValue[] = $this->evaluateNestedValue($value);
            }

            if (count($arrayKey) !== count($arrayValue)) {
                return null;
            }

            $assoArray = array_combine($arrayKey, $arrayValue);
            $this->env->defineVariable($varName, $assoArray);

            return $assoArray;
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function evaluateNestedValue($value)
    {

        if (is_array($value) && isset($value['type']) && $value['type'] === 'literal' && isset($value['value'])) {
            return $value['value'];
        }
        if(isset($value["type"]) && $value["type"] == "assoArray" && is_array($value["value"]))
        {
            $evaluatedArray = [];
            foreach ($value["value"] as $subValue) {
                $evaluatedArray[] = $this->evaluateNestedValue($subValue);
            }
            return $evaluatedArray;
        }

        if (is_array($value) ) {
            $evaluatedArray = [];
            foreach ($value as $subValue) {
                $evaluatedArray[] = $this->evaluateNestedValue($subValue);
            }
            return $evaluatedArray;
        }
        return (new Evaluator($value, $this->env))->evaluate();
    }

    /**
     * @throws Exception
     */
    private function handlePushArray()
    {
        $arrayName = $this->node['object']['name'];
        $array = $this->env->getVariable($arrayName);
        $value = (new Evaluator($this->node['value'], $this->env))->evaluate();
        $array[] = $value;
        $this->env->defineVariable($arrayName, $array);
        return $array;

    }

    /**
     * @throws Exception
     */
    private function handleSliceArray(): array
    {
        $arrayName = $this->node['object']['name'];
        $array = $this->env->getVariable($arrayName);
        $value = (new Evaluator($this->node['value'], $this->env))->evaluate();
        if (!is_numeric($value)) throw new Exception("index needs to be a number .ausse()");
        $index = (int)$value;
        return array_slice($array, $index);
    }

    /**
     * @throws Exception
     */
    private function evaluateForEachLoop(array $node): void
    {
        $array = (new Evaluator($node['arrayName'], $this->env))->evaluate();
        if (is_array($array)) {
            foreach ($array as $item) {
                $this->env->defineVariable($node['itemName'], $item);
                foreach ($node['body'] as $body) {
                    (new Evaluator($body, $this->env))->evaluate();
                }
            }
        } else {
            throw new Exception("The variable '{$node['arrayName']}' is not an array.");
        }
    }

    /**
     * @throws Exception
     */
    private function existInArray(): bool
    {
        $arrayName = $this->node['object']['name'];
        $array = $this->env->getVariable($arrayName);
        $value = (new Evaluator($this->node['value'], $this->env))->evaluate();
        return in_array($value, $array, true);
    }

    /**
     * @throws Exception
     */
    private function takeRandomValue()
    {
        $arrayName = $this->node['object']['name'];
        $array = $this->env->getVariable($arrayName);
        $randomKey = array_rand($array);
        return $array[$randomKey];
    }

    /**
     * @throws Exception
     */
    private function evaluateFilter(array $node): array
    {
        $array = (new Evaluator($node['arrayName'], $this->env))->evaluate();
        if (!is_array($array)) throw new Exception("Filter can only be applied to arrays.");
        $itemName = $this->node['itemName']['name'];
        $body = $node['body'];


        return array_values((array)array_filter($array, function ($item) use ($itemName, $node, $body) {
            $this->env->defineVariable($itemName, $item);

            if (isset($node['condition'])) {
                $conditionResult = $this->evaluateConditionOperator($node);
            } else {
                $conditionResult = $this->evaluateComparison($body[0], $body[1][0], $body[2]);
            }
            return $conditionResult;
        }));
    }

    /**
     * @throws Exception
     */
    private function convertToString($object): string
    {

        if(is_array($object)) {
            return implode(" ", $object);
        }

        return (string)$object;
    }

    /**
     * @throws Exception
     */
    private function handleSort()
    {
        $arrayName = $this->node['object']['name'];
        $array = $this->env->getVariable($arrayName);

        sort($array);

        $this->env->defineVariable($arrayName, $array);

        return null;
    }

    /**
     * @throws Exception
     */
    private function handleFetch()
    {
        if(!filter_var($this->node['array']['url'], FILTER_VALIDATE_URL)) return throw new Exception("Invalid Url: " . $this->node['url']);

        $ch = curl_init($this->node['array']['url']);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new Exception("Failed to fetch data. HTTP Code: $httpCode");
        }

        $decoded = json_decode($response, true);

        return $decoded ?? $response;
    }
}