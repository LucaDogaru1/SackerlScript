<?php

class Parser
{
    private array $tokens;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    private function currentToken(int $currentTokenIndex): array|null
    {
        return $this->tokens[$currentTokenIndex] ?? null;
    }

    /**
     * @throws Exception
     */
    private function parseStatement(int $tokenIndex): ?array
    {
        $if = $this->parseIfStatement($tokenIndex);
        if ($if) return $if;

        $forLoop = $this->parseForLoop($tokenIndex);
        if ($forLoop) return $forLoop;

        $forEachLoop = $this->parseForEach($tokenIndex);
        if($forEachLoop) return $forEachLoop;

        $whileLoop = $this->parseWhileLoop($tokenIndex);
        if ($whileLoop) return $whileLoop;

        $function = $this->parsFunction($tokenIndex);
        if ($function) return $function;

        $arrayAssign = $this->parseArrayAssignment($tokenIndex);
        if ($arrayAssign) return $arrayAssign;

        $variable = $this->parseVariable($tokenIndex);
        if ($variable) return $variable;

        $assignment = $this->parseAssignment($tokenIndex);
        if ($assignment) return $assignment;

        $print = $this->parsePrinter($tokenIndex);
        if ($print) return $print;

        $expression = $this->parseExpression($tokenIndex);
        if ($expression) return $expression;

        return null;
    }

    /**
     * @throws Exception
     */
    private function parseExpression(int $tokenIndex): ?array
    {

        $array = $this->parseArray($tokenIndex);
        if ($array) return $array;

        $arrayAccess = $this->parseArrayAccess($tokenIndex);
        if ($arrayAccess) return $arrayAccess;

        $property = $this->parsePropertyAccess($tokenIndex);
        if($property) return $property;

        $functionCall = $this->parseFunctionCall($tokenIndex);
        if ($functionCall) return $functionCall;

        $arithmeticOperation = $this->parseArithmeticOperation($tokenIndex);
        if ($arithmeticOperation) return $arithmeticOperation;

        $primitiveValue = $this->parseLiteralValue($tokenIndex);
        if ($primitiveValue) return $primitiveValue;

        $identifier = $this->parseIdentifier($tokenIndex);
        if ($identifier) return $identifier;

        $logical = $this->parseLogicOperation($tokenIndex);
        if ($logical) return $logical;

        $comparison = $this->parseComparisonOperator($tokenIndex);
        if ($comparison) return $comparison;

        return null;
    }

    /**
     * @throws Exception
     */
    public function parseCodeBlock(int $tokenIndex): ?array
    {
        $statements = [];
        while (true) {
            $statement = $this->parseStatement($tokenIndex);
            if (!$statement) break;
            $statements[] = $statement[0];
            $tokenIndex = $statement[1];
        }
        return [$statements, $tokenIndex];
    }

    private function parseLiteralValue(int $tokenIndex): ?array
    {
        $string = $this->parseString($tokenIndex);
        if ($string) {
            return [['type' => 'literal', 'value' => $string[0]], $string[1]];
        }

        $number = $this->parseNumber($tokenIndex);
        if ($number) {
            return [['type' => 'literal', 'value' => $number[0]], $number[1]];
        }

        $boolean = $this->parseBoolean($tokenIndex);
        if ($boolean) {
            return [['type' => 'literal', 'value' => $boolean[0]], $boolean[1]];
        }


        return null;
    }

    private function parseString(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] === 'T_STRING') {
            return [$token[1], $tokenIndex + 1];
        }
        return null;
    }

    private function parseNumber(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_NUMBER') {
            return [(int)$token[1], $tokenIndex + 1];
        }
        return null;
    }

    private function parseOperand(int $tokenIndex): ?array
    {
        $number = $this->parseNumber($tokenIndex);
        if ($number) return $number;

        $identifier = $this->parseIdentifier($tokenIndex);
        if ($identifier) return $identifier;

        return null;
    }

    private function parseBoolean(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_TRUE') {
            return [true, $tokenIndex + 1];
        }
        if ($token && $token[0] == 'T_FALSE') {
            return [false, $tokenIndex + 1];
        }
        return null;
    }

    public function parseArithmeticOperation(int $tokenIndex): ?array
    {
        $left = $this->parseOperand($tokenIndex);
        if (!$left) return null;
        $tokenIndex = $left[1];

        $operator = $this->parseArithmeticOperator($tokenIndex);
        if (!$operator) return null;
        $tokenIndex = $operator[1];

        if ($operator[0] == 'plusplus' || $operator[0] == 'minusminus') {
            return [['type' => 'arithmeticOperation', 'operator' => $operator[0], 'operand' => $left[0]], $tokenIndex];
        }

        $right = $this->parseExpression($tokenIndex);
        if (!$right) return null;
        $tokenIndex = $right[1];


        return [['type' => 'arithmeticOperation', 'left' => $left[0], 'operator' => $operator[0], 'right' => $right[0]], $tokenIndex];
    }

    private function parseArithmeticOperator(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_ARITHMETIC_OPERATOR') {
            return [$token[1], $tokenIndex + 1];
        }
        return null;
    }

    private function parseLogicOperator(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_LOGICAL_AND') {
            return [$token[1], $tokenIndex + 1];
        }

        if ($token && $token[0] == 'T_LOGICAL_OR') {
            return [$token[1], $tokenIndex + 1];
        }

        return null;
    }

    private function parseBracket(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_OPENING_BRACKET') {
            return [$token[1], $tokenIndex + 1];
        }
        if ($token && $token[0] == 'T_CLOSING_BRACKET') {
            return [$token[1], $tokenIndex + 1];
        }
        return null;
    }

    private function parseBrace(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_OPENING_BRACE') {
            return [$token[1], $tokenIndex + 1];
        }
        if ($token && $token[0] == 'T_CLOSING_BRACE') {
            return [$token[1], $tokenIndex + 1];
        }
        return null;
    }

    private function parseSemicolon(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_SEMICOLON') {
            return [$token[1], $tokenIndex + 1];
        }
        return null;
    }


    private function parseIdentifier(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_IDENTIFIER') {
            return [["type" => "identifier", "name" => $token[1]], $tokenIndex + 1];
        }
        return null;
    }

    private function parseVariable(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_LET') {
            $tokenIndex++;
            $nameToken = $this->currentToken($tokenIndex);

            if ($nameToken && $nameToken[0] == 'T_IDENTIFIER') {
                $variableName = $nameToken[1];
                $tokenIndex++;
                $equalToken = $this->currentToken($tokenIndex);

                if ($equalToken && $equalToken[0] == 'T_ASSIGN') {
                    $tokenIndex++;

                    $arrayResult = $this->parseArray($tokenIndex);
                    if ($arrayResult) {
                        [$valueNode, $tokenIndex] = $arrayResult;

                        return [['type' => 'variable', 'name' => $variableName, 'array' => $valueNode], $tokenIndex];
                    }

                    $expressionResult = $this->parseExpression($tokenIndex);
                    if ($expressionResult) {
                        [$valueNode, $tokenIndex] = $expressionResult;
                        return [['type' => 'variable', 'name' => $variableName, 'value' => $valueNode], $tokenIndex];
                    }
                }
            }
        }
        return null;
    }

    private function parseAssignment(int $tokenIndex): ?array
    {
        $nameToken = $this->currentToken($tokenIndex);
        if ($nameToken && $nameToken[0] == 'T_IDENTIFIER') {
            $variableName = $nameToken[1];
            $tokenIndex++;

            $equalToken = $this->currentToken($tokenIndex);
            if ($equalToken && $equalToken[0] == 'T_ASSIGN') {
                $tokenIndex++;
                $expression = $this->parseExpression($tokenIndex);

                if (!$expression) return null;

                [$valueNode, $tokenIndex] = $expression;

                return [['type' => 'assignment', 'name' => $variableName, 'value' => $valueNode], $tokenIndex];

            }
        }
        return null;
    }

    private function parsePrinter(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_PRINT') {
            $tokenIndex++;
            $parenthesisToken = $this->currentToken($tokenIndex);

            if ($parenthesisToken && $parenthesisToken[0] == 'T_OPENING_PARENTHESIS') {

                [$values, $tokenIndex] = $this->checkForMultipleExpressionsInParenthesis($tokenIndex);
                $closingToken = $this->currentToken($tokenIndex);

                if ($closingToken && $closingToken[0] == 'T_CLOSING_PARENTHESIS') {
                    return [['type' => 'print', 'value' => $values], $tokenIndex + 1];
                }
            }
        }
        return null;
    }

    private function parsFunction(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_FUNCTION') {
            $tokenIndex++;
            $peek = $this->currentToken($tokenIndex);

            if ($peek && $peek[0] == 'T_IDENTIFIER') {
                $functionName = $peek[1];
                $tokenIndex++;
                $openParentheses = $this->currentToken($tokenIndex);

                if ($openParentheses && $openParentheses[0] == 'T_OPENING_PARENTHESIS') {
                    [$parameters, $tokenIndex] = $this->checkForMultipleExpressionsInParenthesis($tokenIndex);
                    $closingParenthesis = $this->currentToken($tokenIndex);

                    if ($closingParenthesis && $closingParenthesis[0] == 'T_CLOSING_PARENTHESIS') {
                        $tokenIndex++;
                        $openBrace = $this->currentToken($tokenIndex);

                        if ($openBrace && $openBrace[0] == 'T_OPENING_BRACE') {
                            $tokenIndex++;
                            [$body, $tokenIndex] = $this->parseCodeBlock($tokenIndex);
                            $closingBrace = $this->currentToken($tokenIndex);

                            if ($closingBrace && $closingBrace[0] == 'T_CLOSING_BRACE') {
                                return [['type' => 'function', 'functionName' => $functionName, 'parameters' => $parameters, 'body' => $body], $tokenIndex + 1];
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    private function parseFunctionCall(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_IDENTIFIER') {
            $functionName = $token[1];
            $tokenIndex++;
            $openingParenthesis = $this->currentToken($tokenIndex);

            if ($openingParenthesis && $openingParenthesis[0] == 'T_OPENING_PARENTHESIS') {
                $tokenIndex++;
                [$args, $tokenIndex] = $this->handlesParametersInFunction($tokenIndex);
                $closingParenthesis = $this->currentToken($tokenIndex);

                if ($closingParenthesis && $closingParenthesis[0] == 'T_CLOSING_PARENTHESIS') {
                    return [['type' => 'functionCall', 'functionName' => $functionName, 'arguments' => $args], $tokenIndex + 1];

                }
            }
        }
        return null;
    }


    /**
     * @throws Exception
     */
    private function parseIfStatement(int $tokenIndex): ?array
    {

        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_IF') {
            $tokenIndex++;
            $openingParenthesis = $this->currentToken($tokenIndex);

            if ($openingParenthesis && $openingParenthesis[0] == 'T_OPENING_PARENTHESIS') {
                [$condition, $tokenIndex] = $this->checkForCondition($tokenIndex);
                $closingParenthesis = $this->currentToken($tokenIndex);

                if ($closingParenthesis && $closingParenthesis[0] == 'T_CLOSING_PARENTHESIS') {
                    $tokenIndex++;
                    $openingBrace = $this->currentToken($tokenIndex);

                    if ($openingBrace && $openingBrace[0] == 'T_OPENING_BRACE') {
                        $tokenIndex++;
                        [$body, $tokenIndex] = $this->parseCodeBlock($tokenIndex);
                        $closingBrace = $this->currentToken($tokenIndex);

                        if ($closingBrace && $closingBrace[0] == 'T_CLOSING_BRACE') {
                            if (!$body) {
                                throw new Exception("Body of If_statement is empty");
                            }

                            if (count($condition) === 1) {
                                $ifNode = ['type' => 'if', 'left' => $condition[0], 'body' => $body];
                                $this->parseElse($ifNode, $tokenIndex);
                                return [$ifNode, $tokenIndex + 1];
                            }
                            if (count($condition) > 3) {
                                $ifNode = ['type' => 'if', 'condition' => $condition, 'body' => $body];
                                $this->parseElse($ifNode, $tokenIndex);
                                return [$ifNode, $tokenIndex + 1];
                            }
                            $ifNode = ['type' => 'if', 'left' => $condition[0], 'operator' => $condition[1][0], 'right' => $condition[2], 'body' => $body];
                            $this->parseElse($ifNode, $tokenIndex);
                            return [$ifNode, $tokenIndex + 1];
                        }
                    }
                }
            }
        }
        return null;
    }


    /**
     * @throws Exception
     */
    private function parseElse(array &$ifNode, int $tokenIndex): void
    {
        $tokenIndex++;
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_ELSE') {
            $tokenIndex++;
            $openingBrace = $this->currentToken($tokenIndex);

            if ($openingBrace && $openingBrace[0] == 'T_OPENING_BRACE') {
                $tokenIndex++;
                [$body, $tokenIndex] = $this->parseCodeBlock($tokenIndex);
                $closingBrace = $this->currentToken($tokenIndex);

                if ($closingBrace && $closingBrace[0] == 'T_CLOSING_BRACE') {
                    $ifNode['else'] = $body;
                }
            }
        }
    }


    private function checkForMultipleExpressionsInParenthesis(int $tokenIndex): array
    {
        $values = [];
        $tokenIndex++;

        while (true) {
            $expressionResult = $this->parseExpression($tokenIndex);
            if (!$expressionResult) break;

            [$valueNode, $tokenIndex] = $expressionResult;
            $values[] = $valueNode;
            $nextToken = $this->currentToken($tokenIndex);

            if ($nextToken && $nextToken[0] == 'T_SEPARATOR') {
                $tokenIndex++;
                continue;
            }
            break;
        }

        return [$values, $tokenIndex];
    }

    private function handlesParametersInFunction(int $tokenIndex): array
    {
        $args = [];

        $arg = $this->parseExpression($tokenIndex);
        if ($arg) {
            $args[] = $arg;
            $tokenIndex++;
        }

        while ($this->currentToken($tokenIndex) && $this->currentToken($tokenIndex)[0] == 'T_SEPARATOR') {
            $tokenIndex++;

            $arg = $this->parseExpression($tokenIndex);
            if ($arg) {
                $args[] = $arg;
                $tokenIndex++;
            }
        }

        return [$args, $tokenIndex];
    }

    private function checkForCondition(int $tokenIndex): array
    {
        $condition = [];
        $tokenIndex++;

        while (true) {

            $conditionResult = $this->parseExpression($tokenIndex);
            if (!$conditionResult) break;

            [$conditionNode, $tokenIndex] = $conditionResult;
            $condition[] = $conditionNode;


            $logicalOperator = $this->parseLogicOperation($tokenIndex);
            if ($logicalOperator) {
                [$operator, $tokenIndex] = $logicalOperator;

                $condition[] = [$operator, $tokenIndex];
                continue;
            }

            $comparisonOperator = $this->parseComparisonOperator($tokenIndex);
            if ($comparisonOperator) {
                [$operator, $tokenIndex] = $comparisonOperator;
                $condition[] = [$operator, $tokenIndex];
                continue;
            }

            break;
        }


        return [$condition, $tokenIndex];
    }

    private function parseLogicOperation(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_LOGICAL_AND' || $token && $token[0] == 'T_LOGICAL_OR') {
            return [$token[1], $tokenIndex + 1];
        }
        return null;
    }

    private function parseComparisonOperator(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_COMPARISON_OPERATOR') {
            return [$token[1], $tokenIndex + 1];
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function parseForLoop(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);

        if ($token && $token[0] == 'T_FOR') {
            $tokenIndex++;
            $openParenthesis = $this->currentToken($tokenIndex);

            if ($openParenthesis && $openParenthesis[0] == 'T_OPENING_PARENTHESIS') {
                $tokenIndex++;
                [$initialization, $tokenIndex] = $this->parseAssignment($tokenIndex);
                $semicolon = $this->currentToken($tokenIndex);

                if ($semicolon && $semicolon[0] == 'T_SEMICOLON') {
                    $tokenIndex++;
                    $conditionResult = $this->parseConditionInLoop($tokenIndex);
                    [$conditionNode, $tokenIndex] = $conditionResult;
                    $semicolon = $this->currentToken($tokenIndex);

                    if ($semicolon && $semicolon[0] == 'T_SEMICOLON') {
                        $tokenIndex++;
                        [$iteration, $tokenIndex] = $this->parseExpression($tokenIndex);
                        $closingParenthesis = $this->currentToken($tokenIndex);

                        if ($closingParenthesis && $closingParenthesis[0] == 'T_CLOSING_PARENTHESIS') {
                            $tokenIndex++;
                            $openingBrace = $this->currentToken($tokenIndex);

                            if ($openingBrace && $openingBrace[0] == 'T_OPENING_BRACE') {
                                $tokenIndex++;
                                [$body, $tokenIndex] = $this->parseCodeBlock($tokenIndex);
                                $closingBrace = $this->currentToken($tokenIndex);

                                if ($closingBrace && $closingBrace[0] == 'T_CLOSING_BRACE') {
                                    if (!$body) {
                                        throw new Exception("Body of Loop  is empty");
                                    }
                                    return [['type' => 'forLoop', 'initialization' => $initialization, 'condition' => $conditionNode, 'iteration' => $iteration, 'body' => $body], $tokenIndex + 1];
                                }
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function parseWhileLoop($tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        if ($token && $token[0] == 'T_WHILE') {
            $tokenIndex++;
            $openParenthesis = $this->currentToken($tokenIndex);

            if ($openParenthesis && $openParenthesis[0] == 'T_OPENING_PARENTHESIS') {
                [$condition, $tokenIndex] = $this->checkForCondition($tokenIndex);
                $closingParenthesis = $this->currentToken($tokenIndex);

                if ($closingParenthesis && $closingParenthesis[0] == 'T_CLOSING_PARENTHESIS') {
                    $tokenIndex++;
                    $openingBrace = $this->currentToken($tokenIndex);

                    if ($openingBrace && $openingBrace[0] == 'T_OPENING_BRACE') {
                        $tokenIndex++;
                        [$body, $tokenIndex] = $this->parseCodeBlock($tokenIndex);
                        $closingBrace = $this->currentToken($tokenIndex);;

                        if ($closingBrace && $closingBrace[0] == 'T_CLOSING_BRACE') {
                            if (!$body) {
                                throw new Exception("Body of If_statement is empty");
                            }
                            if (count($condition) === 1) {
                                return [['type' => 'whileLoop', 'left' => $condition[0], 'body' => $body], $tokenIndex + 1];
                            }
                            if (count($condition) > 3) {
                                return [['type' => 'whileLoop', 'condition' => $condition, 'body' => $body], $tokenIndex + 1];
                            }
                            return [['type' => 'whileLoop', 'left' => $condition[0], 'operator' => $condition[1][0], 'right' => $condition[2], 'body' => $body], $tokenIndex + 1];
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function parseConditionInLoop(int $tokenIndex): ?array
    {
        $left = $this->parseExpression($tokenIndex);
        if (!$left) throw new Exception('Expected expression in loop condition');

        [$leftNode, $tokenIndex] = $left;

        $comparisonOperator = $this->parseComparisonOperator($tokenIndex);
        if (!$comparisonOperator) throw new Exception('Expected comparison operator in loop condition');

        [$operator, $tokenIndex] = $comparisonOperator;

        $propertyAccess = $this->parsePropertyAccess($tokenIndex);
        if ($propertyAccess) {
            [$rightNode, $tokenIndex] = $propertyAccess;
        } else {
            $right = $this->parseExpression($tokenIndex);
            if (!$right) throw new Exception('Expected right-hand side expression in loop condition');
            [$rightNode, $tokenIndex] = $right;
        }


        return [
            [
                'type' => 'comparison',
                'left' => $leftNode,
                'operator' => $operator,
                'right' => $rightNode
            ],
            $tokenIndex
        ];
    }


    private function parseArray(int $tokenIndex): ?array
    {
        $token = $this->currentToken($tokenIndex);
        $values = [];

        if ($token && $token[0] == 'T_OPENING_BRACKET') {
            $tokenIndex++;
            while (true) {
                $literal = $this->parseLiteralValue($tokenIndex);

                if (!$literal) break;
                [$value, $tokenIndex] = $literal;
                $values[] = $value;
                $nextToken = $this->currentToken($tokenIndex);

                if ($nextToken && $nextToken[0] == 'T_SEPARATOR') {
                    $tokenIndex++;
                    continue;
                }
                break;
            }

            $closingBracket = $this->currentToken($tokenIndex);
            if ($closingBracket && $closingBracket[0] === 'T_CLOSING_BRACKET') {

                return [$values, $tokenIndex + 1];
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function parsePropertyAccess(int $tokenIndex): ?array
    {
        $identifier = $this->parseIdentifier($tokenIndex);
        if (!$identifier) return null;

        [$objectNode, $tokenIndex] = $identifier;

        $dotToken = $this->currentToken($tokenIndex);
        if (!$dotToken || $dotToken[0] !== 'T_DOT') return null;
        $tokenIndex++;

        $property = $this->parseIdentifier($tokenIndex);
        if (!$property) throw new Exception("Expected property name after '.'");

        [$propertyNode, $tokenIndex] = $property;

        if($propertyNode['name'] == 'nimmAusse') return $this->parseFilter($tokenIndex, $objectNode);

        $openParenthesis = $this->currentToken($tokenIndex);

        if($openParenthesis && $openParenthesis[0] == 'T_OPENING_PARENTHESIS') {
            $tokenIndex++;
            [$valueNode, $tokenIndex] = $this->parseExpression($tokenIndex);
            if (!$valueNode) return null;

            $closingParenthesis = $this->currentToken($tokenIndex);
            if (!$closingParenthesis || $closingParenthesis[0] !== 'T_CLOSING_PARENTHESIS') {
                throw new Exception("Expected closing parenthesis for method call.");
            }
            $tokenIndex++;

            return [
                [
                    'type' => 'property_access',
                    'object' => $objectNode,
                    'property' => $propertyNode['name'],
                    'value' => $valueNode
                ],
                $tokenIndex
            ];
        }

        return [
            [
                'type' => 'property_access',
                'object' => $objectNode,
                'property' => $propertyNode['name']
            ],
            $tokenIndex
        ];
    }

    private function parseArrayAccess(int $tokenIndex): ?array
    {
        $array = $this->parseIdentifier($tokenIndex);
        if (!$array) return null;
        [$arrayName, $tokenIndex] = $array;

        $openBracket = $this->currentToken($tokenIndex);
        if ($openBracket && $openBracket[0] == 'T_OPENING_BRACKET') {
            $tokenIndex++;

            [$index, $tokenIndex] = $this->parseExpression($tokenIndex);
            if (!$index) return null;

            $closeBracket = $this->currentToken($tokenIndex);
            if ($closeBracket && $closeBracket[0] == 'T_CLOSING_BRACKET') {
                return [
                    [
                        'type' => 'array_access',
                        'array' => $arrayName['name'],
                        'index' => $index
                    ], $tokenIndex + 1
                ];
            }
        }
        return null;
    }

    private function parseArrayAssignment(int $tokenIndex): ?array
    {
        $arrayAccess = $this->parseArrayAccess($tokenIndex);
        if (!$arrayAccess) return null;

        [$arrayNode, $tokenIndex] = $arrayAccess;

        $assignToken = $this->currentToken($tokenIndex);
        if ($assignToken && $assignToken[0] == 'T_ASSIGN') {
            $tokenIndex++;

            [$valueNode, $tokenIndex] = $this->parseExpression($tokenIndex);
            if (!$valueNode) return null;

            return [
                [
                    'type' => 'array_assignment',
                    'array' => $arrayNode['array'],
                    'index' => $arrayNode['index'],
                    'value' => $valueNode
                ],
                $tokenIndex
            ];
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function parseForEach(int $tokenIndex) :?array
    {
        $token = $this->currentToken($tokenIndex);
        if($token && $token[0] == 'T_FOREACH') {
            $tokenIndex++;
            $openParenthesis = $this->currentToken($tokenIndex);

            if($openParenthesis && $openParenthesis[0] == 'T_OPENING_PARENTHESIS') {
                $tokenIndex++;
                $array = $this->parseIdentifier($tokenIndex);
                if (!$array) return null;
                [$arrayName, $tokenIndex] = $array;
                $as = $this->currentToken($tokenIndex);

                if($as && $as[0] == 'T_AS') {
                    $tokenIndex++;
                    $itemName = $this->parseIdentifier($tokenIndex);
                    if (!$itemName) return null;
                    [$name, $tokenIndex] = $itemName;
                    $closingParenthesis = $this->currentToken($tokenIndex);

                    if($closingParenthesis && $closingParenthesis[0] == 'T_CLOSING_PARENTHESIS') {
                        $tokenIndex++;
                        $openingBrace = $this->currentToken($tokenIndex);

                        if($openingBrace && $openingBrace[0] == 'T_OPENING_BRACE') {
                            $tokenIndex++;
                            [$body, $tokenIndex] = $this->parseCodeBlock($tokenIndex);
                            $closingBrace = $this->currentToken($tokenIndex);

                            if($closingBrace && $closingBrace[0] == 'T_CLOSING_BRACE') {
                                return [
                                    [
                                        'type' => 'forEach',
                                        'arrayName'=> $arrayName,
                                        'itemName' => $name['name'],
                                        'body' => $body
                                    ],
                                    $tokenIndex + 1
                                ];
                            }
                        }
                    }
                }

            }
        }
        return null;
    }


    /**
     * @throws Exception
     */
    private function parseFilter(int $tokenIndex, array $arrayName): ?array
    {
        $openingParenthesis = $this->currentToken($tokenIndex);
        if($openingParenthesis && $openingParenthesis[0] == 'T_OPENING_PARENTHESIS') {
            $tokenIndex++;
            $identifier = $this->parseIdentifier($tokenIndex);
            if(!$identifier) throw new Exception("Missing name in filter function");
            [$name, $tokenIndex] = $identifier;
            $filterToken = $this->currentToken($tokenIndex);


            if($filterToken && $filterToken[0] == 'T_FILTER_ARROW') {
                $tokenIndex++;
                $openBrace = $this->currentToken($tokenIndex);

                if($openBrace && $openBrace[0] == 'T_OPENING_BRACE') {
                    [$condition, $tokenIndex] = $this->checkForCondition($tokenIndex);
                    if(!$condition) throw new Exception('body of filter is empty');
                    $closingBrace = $this->currentToken($tokenIndex);

                    if($closingBrace && $closingBrace[0] == 'T_CLOSING_BRACE' ) {
                        $tokenIndex++;
                        $closingParenthesis = $this->currentToken($tokenIndex);

                        if($closingParenthesis && $closingParenthesis[0] == 'T_CLOSING_PARENTHESIS') {
                            if (count($condition) > 3) {

                                return [[ 'type' => 'filter',
                                    'arrayName' => $arrayName,
                                    'itemName' => $name,
                                    'condition' => $condition,
                                    'body' => $condition
                                ], $tokenIndex + 1];
                            }
                            return [
                                [
                                    'type' => 'filter',
                                    'arrayName' => $arrayName,
                                    'itemName' => $name,
                                    'body' => $condition
                                ],
                                $tokenIndex + 1
                            ];
                        }
                    }
                }
            }
        }
        return null;
    }

}
