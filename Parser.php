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

    private function parseStatement(int $tokenIndex): ?array
    {
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
        $arithmeticOperation = $this->parseArithmeticOperation($tokenIndex);
        if ($arithmeticOperation) return $arithmeticOperation;

        $primitiveValue = $this->parseLiteralValue($tokenIndex);
        if ($primitiveValue) return $primitiveValue;

        $identifier = $this->parseIdentifier($tokenIndex);
        if ($identifier) return $identifier;

        return null;
    }

    public function parseCodeBlock(int $tokenIndex): ?array
    {
        $statements = [];
        while (true) {
            $statement = $this->parseStatement($tokenIndex);
            if (!$statement) break;
            $statements[] = $statement[0];
            $tokenIndex = $statement[1];
        }
        return $statements;
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
        $left = $this->parseNumber($tokenIndex);
        if (!$left) return null;
        $tokenIndex = $left[1];

        $operator = $this->parseArithmeticOperator($tokenIndex);
        if (!$operator) return null;
        $tokenIndex = $operator[1];

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
                $tokenIndex++;
                $values = [];

                while (true) {
                    $expressionResult = $this->parseExpression($tokenIndex);

                    if (!$expressionResult) break;

                    [$valueNode, $tokenIndex] = $expressionResult;
                    $values[] = $valueNode;

                    $peek = $this->currentToken($tokenIndex);
                    if ($peek && $peek[0] == 'T_SEPARATOR') {
                        $tokenIndex++;
                        continue;
                    }
                    break;
                }

                $closingToken = $this->currentToken($tokenIndex);
                if ($closingToken && $closingToken[0] == 'T_CLOSING_PARENTHESIS') {
                    return [['type' => 'print', 'value' => $values], $tokenIndex + 1];
                }
            }
        }
        return null;
    }


    public function parseIfStatement()
    {
        // Noch nicht implementiert
    }

}
