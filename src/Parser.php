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

        $whileLoop = $this->parseWhileLoop($tokenIndex);
        if ($whileLoop) return $whileLoop;

        $function = $this->parsFunction($tokenIndex);
        if ($function) return $function;

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

    private function parseExpression(int $tokenIndex): ?array
    {
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
                    $expressionResult = $this->parseExpression($tokenIndex);

                    if (!$expressionResult) return null;

                    [$valueNode, $tokenIndex] = $expressionResult;

                    return [['type' => 'variable', 'name' => $variableName, 'value' => $valueNode], $tokenIndex];

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
                        $closingBrace = $this->currentToken($tokenIndex);


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

        $right = $this->parseExpression($tokenIndex);
        if (!$right) throw new Exception('Expected right-hand side expression in loop condition');

        [$rightNode, $tokenIndex] = $right;

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

}

