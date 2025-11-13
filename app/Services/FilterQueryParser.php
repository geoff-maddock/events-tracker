<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Parses advanced filter query strings into a structured format.
 * 
 * Supports operators: EQ, NEQ, GT, GTE, LT, LTE, LIKE, IN, NOT IN
 * Supports logical operators: AND, OR
 * Supports grouped conditions with parentheses
 */
class FilterQueryParser
{
    private const OPERATORS = ['EQ', 'NEQ', 'GT', 'GTE', 'LT', 'LTE', 'LIKE', 'IN', 'NOT IN'];
    private const LOGICAL_OPERATORS = ['AND', 'OR'];
    
    /**
     * Parse a filter query string into a structured array.
     *
     * @param string $query The filter query string
     * @return array The parsed filter structure
     * @throws InvalidArgumentException If the query is malformed
     */
    public function parse(string $query): array
    {
        $query = trim($query);
        
        if (empty($query)) {
            return [];
        }
        
        return $this->parseExpression($query);
    }
    
    /**
     * Parse an expression which may contain logical operators and groups.
     */
    private function parseExpression(string $expression): array
    {
        $expression = trim($expression);
        
        // Handle grouped expressions
        if ($this->startsWithParenthesis($expression)) {
            return $this->parseGroupedExpression($expression);
        }
        
        // Try to split by logical operators (AND, OR)
        $result = $this->splitByLogicalOperator($expression);
        
        if ($result) {
            return $result;
        }
        
        // Single condition
        return $this->parseCondition($expression);
    }
    
    /**
     * Check if expression starts with opening parenthesis.
     */
    private function startsWithParenthesis(string $expression): bool
    {
        return str_starts_with(trim($expression), '(');
    }
    
    /**
     * Parse expression with parentheses and logical operators.
     */
    private function parseGroupedExpression(string $expression): array
    {
        $tokens = $this->tokenize($expression);
        return $this->parseTokens($tokens);
    }
    
    /**
     * Tokenize the expression into manageable parts.
     */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $current = '';
        $depth = 0;
        $inParens = false;
        $len = strlen($expression);
        
        for ($i = 0; $i < $len; $i++) {
            $char = $expression[$i];
            
            if ($char === '(') {
                if ($depth === 0 && $current !== '') {
                    $tokens[] = trim($current);
                    $current = '';
                }
                $depth++;
                $inParens = true;
                $current .= $char;
            } elseif ($char === ')') {
                $depth--;
                $current .= $char;
                if ($depth === 0) {
                    $tokens[] = trim($current);
                    $current = '';
                    $inParens = false;
                }
            } else {
                $current .= $char;
            }
        }
        
        if ($current !== '') {
            $tokens[] = trim($current);
        }
        
        return $tokens;
    }
    
    /**
     * Parse tokens into a structured array.
     */
    private function parseTokens(array $tokens): array
    {
        $result = [];
        $i = 0;
        
        while ($i < count($tokens)) {
            $token = $tokens[$i];
            
            // Check if it's a logical operator
            if ($this->isLogicalOperator($token)) {
                $operator = strtoupper(trim($token));
                $i++;
                continue;
            }
            
            // Check if it's a grouped expression
            if ($this->startsWithParenthesis($token)) {
                $content = $this->extractParenthesesContent($token);
                $parsed = $this->parseExpression($content);
                
                // Look ahead for logical operator
                if ($i + 1 < count($tokens) && $this->isLogicalOperator($tokens[$i + 1])) {
                    $operator = strtoupper(trim($tokens[$i + 1]));
                    
                    if (!isset($result['type'])) {
                        $result = [
                            'type' => $operator,
                            'conditions' => [$parsed]
                        ];
                    } else {
                        $result['conditions'][] = $parsed;
                    }
                    $i += 2;
                } else {
                    return $parsed;
                }
            } else {
                // It's a condition
                $parsed = $this->parseCondition($token);
                
                // Look ahead for logical operator
                if ($i + 1 < count($tokens) && $this->isLogicalOperator($tokens[$i + 1])) {
                    $operator = strtoupper(trim($tokens[$i + 1]));
                    
                    if (!isset($result['type'])) {
                        $result = [
                            'type' => $operator,
                            'conditions' => [$parsed]
                        ];
                    } else {
                        $result['conditions'][] = $parsed;
                    }
                    $i += 2;
                } else {
                    return $parsed;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Extract content from within parentheses.
     */
    private function extractParenthesesContent(string $expression): string
    {
        $expression = trim($expression);
        if (str_starts_with($expression, '(') && str_ends_with($expression, ')')) {
            return substr($expression, 1, -1);
        }
        return $expression;
    }
    
    /**
     * Check if token is a logical operator.
     */
    private function isLogicalOperator(string $token): bool
    {
        return in_array(strtoupper(trim($token)), self::LOGICAL_OPERATORS, true);
    }
    
    /**
     * Split expression by logical operator if present at the top level.
     */
    private function splitByLogicalOperator(string $expression): ?array
    {
        // Find logical operators not inside parentheses
        $depth = 0;
        $len = strlen($expression);
        
        foreach (self::LOGICAL_OPERATORS as $operator) {
            $operatorLen = strlen($operator);
            
            for ($i = 0; $i < $len; $i++) {
                if ($expression[$i] === '(') {
                    $depth++;
                } elseif ($expression[$i] === ')') {
                    $depth--;
                }
                
                if ($depth === 0 && $i + $operatorLen <= $len) {
                    $substring = substr($expression, $i, $operatorLen + 2); // +2 for spaces
                    if (preg_match('/\s+' . preg_quote($operator, '/') . '\s+/i', $substring)) {
                        // Found operator at top level
                        $parts = preg_split('/\s+' . preg_quote($operator, '/') . '\s+/i', $expression);
                        
                        $conditions = [];
                        foreach ($parts as $part) {
                            $conditions[] = $this->parseExpression(trim($part));
                        }
                        
                        return [
                            'type' => strtoupper($operator),
                            'conditions' => $conditions
                        ];
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Parse a single condition (e.g., "column EQ value" or "column IN (val1, val2)").
     */
    private function parseCondition(string $condition): array
    {
        $condition = trim($condition);
        
        // Try to match each operator
        foreach (self::OPERATORS as $operator) {
            // Special handling for "NOT IN" operator
            if ($operator === 'NOT IN') {
                if (preg_match('/^(.+?)\s+NOT\s+IN\s+\((.+?)\)$/i', $condition, $matches)) {
                    $column = trim($matches[1]);
                    $values = $this->parseInValues($matches[2]);
                    
                    return [
                        'type' => 'condition',
                        'column' => $column,
                        'operator' => 'NOT IN',
                        'value' => $values
                    ];
                }
            } elseif ($operator === 'IN') {
                if (preg_match('/^(.+?)\s+IN\s+\((.+?)\)$/i', $condition, $matches)) {
                    $column = trim($matches[1]);
                    $values = $this->parseInValues($matches[2]);
                    
                    return [
                        'type' => 'condition',
                        'column' => $column,
                        'operator' => 'IN',
                        'value' => $values
                    ];
                }
            } else {
                // Standard operators
                if (preg_match('/^(.+?)\s+' . preg_quote($operator, '/') . '\s+(.+)$/i', $condition, $matches)) {
                    $column = trim($matches[1]);
                    $value = trim($matches[2]);
                    
                    return [
                        'type' => 'condition',
                        'column' => $column,
                        'operator' => strtoupper($operator),
                        'value' => $this->parseValue($value)
                    ];
                }
            }
        }
        
        throw new InvalidArgumentException("Invalid condition: {$condition}");
    }
    
    /**
     * Parse values from IN clause.
     */
    private function parseInValues(string $valueString): array
    {
        $values = array_map('trim', explode(',', $valueString));
        return array_map([$this, 'parseValue'], $values);
    }
    
    /**
     * Parse a value, handling quotes and special types.
     */
    private function parseValue(string $value): mixed
    {
        $value = trim($value);
        
        // Remove quotes if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }
        
        // Handle null
        if (strtolower($value) === 'null') {
            return null;
        }
        
        // Handle booleans
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        
        // Handle numbers
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        
        return $value;
    }
}
