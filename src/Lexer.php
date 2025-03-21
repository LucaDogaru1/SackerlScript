<?php

class Lexer
{
    private string $input;
    private array $tokens = [];

    public function __construct($input)
    {
        $this->input = $input;
    }

    public function tokenize(): array
    {
        $patterns = [
            'T_PRINT' => '/\boida\.sag\b/',
            'T_LET' => '/\bheast\b/',
            'T_IF'=> '/\bwenn\b/',
            'T_ELSE' => '/\bsonst\b/',
            'T_FALSE' => '/\bsichaned\b/',
            'T_COLON' => '/\:/',
            'T_TRUE' => '/\bbasst\b/',
            'T_FUNCTION' => '/\bhawara\b/',
            'T_LOGICAL_AND' => '/\bund\b/',
            'T_LOGICAL_OR' => '/\boda\b/',
            'T_RETURN' => '/\bspeicher\b/',
            'T_COMPARISON_OPERATOR' => '/\bgleich\b|\bisned\b|\bklanaglei\b|\b(gößerglei|größerglei)\b|\bklana\b|\bgrößer\b/',
            'T_ARITHMETIC_OPERATOR' => '/(plusplus|minusminus|mal|dividier|plus|minus)/',
            'T_FILTER_ARROW' => '/=>/',
            'T_ASSIGN' => '/\+=|-=|\*=|\/=|=/',
            'T_NUMBER' => '/\d+/',
            'T_STRING' => '/"(?:.*?)"/',
            'T_OPENING_BRACKET' => '/\[/',
            'T_CLOSING_BRACKET' => '/\]/',
            'T_OPENING_BRACE' => '/\{/',
            'T_CLOSING_BRACE' => '/\}/',
            'T_OPENING_PARENTHESIS' => '/\(/',
            'T_CLOSING_PARENTHESIS' => '/\)/',
            'T_SEPARATOR' => '/\,/',
            'T_SEMICOLON' => '/;/',
            'T_FOR' => '\baufi\b',
            'T_WHILE' => '/\bgeh weida\b/',
            'T_FOREACH' => '/\bfiaOis\b/',
            'T_AS' => '/\bals\b/',
            'T_DOT' => '/\./',
            'T_COMMENT' => '/\bkommentar\b/',
            'T_FETCH' => '/\bholma\b/',
            'T_IDENTIFIER' => '/[a-zA-Z_]\w*/',

        ];

        $this->lookForTokens($patterns);
        return $this->tokens;
    }

    private function lookForTokens(array $patterns): void
    {
        $regex = '/' . implode('|', array_map(fn($p) => '(' . trim($p, '/') . ')', $patterns)) . '/';

        preg_match_all($regex, $this->input, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            foreach ($patterns as $type => $pattern) {
                if (preg_match('/^' . trim($pattern, '/') . '$/', $match[0])) {
                    $value = $match[0];
                    if ($type === 'T_STRING') {
                        $value = trim($value, '"');
                    }
                    $this->tokens[] = [$type, $value];
                    break;
                }
            }
        }
    }
}