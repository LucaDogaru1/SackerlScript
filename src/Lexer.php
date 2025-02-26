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
            'T_FALSE' => '/\bsichaned\b/',
            'T_TRUE' => '/\bbasst\b/',
            'T_FUNCTION' => '/\bhawara\b/',
            'T_LOGICAL_AND' => '/\bund\b/',
            'T_LOGICAL_OR' => '/\boda\b/',
            'T_COMPARISON_OPERATOR' => '/\bglei\b|\bnedglei\b|\bklanaglei\b|\b(gößerglei|größerglei)\b|\bklana\b|\bgrößer\b/',
            'T_ARITHMETIC_OPERATOR' => '/(plus|minus|mal|dividier)/',
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
            'T_SEMICOLON' => '/\b;\b/',
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
                    $this->tokens[] = [$type, $match[0]];
                    break;
                }
            }
        }
    }
}