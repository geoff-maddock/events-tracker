<?php

namespace Tests\Unit\Services;

use App\Services\FilterQueryParser;
use InvalidArgumentException;
use Tests\TestCase;

class FilterQueryParserTest extends TestCase
{
    private FilterQueryParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FilterQueryParser();
    }

    public function testParseSimpleEquality()
    {
        $result = $this->parser->parse('name EQ test');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'name',
            'operator' => 'EQ',
            'value' => 'test'
        ], $result);
    }

    public function testParseNotEqual()
    {
        $result = $this->parser->parse('status NEQ active');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'status',
            'operator' => 'NEQ',
            'value' => 'active'
        ], $result);
    }

    public function testParseGreaterThan()
    {
        $result = $this->parser->parse('age GT 18');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'age',
            'operator' => 'GT',
            'value' => 18
        ], $result);
    }

    public function testParseGreaterThanOrEqual()
    {
        $result = $this->parser->parse('price GTE 100.50');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'price',
            'operator' => 'GTE',
            'value' => 100.50
        ], $result);
    }

    public function testParseLessThan()
    {
        $result = $this->parser->parse('count LT 5');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'count',
            'operator' => 'LT',
            'value' => 5
        ], $result);
    }

    public function testParseLessThanOrEqual()
    {
        $result = $this->parser->parse('level LTE 10');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'level',
            'operator' => 'LTE',
            'value' => 10
        ], $result);
    }

    public function testParseLike()
    {
        $result = $this->parser->parse('description LIKE %test%');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'description',
            'operator' => 'LIKE',
            'value' => '%test%'
        ], $result);
    }

    public function testParseIn()
    {
        $result = $this->parser->parse('status IN (active, pending, approved)');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'status',
            'operator' => 'IN',
            'value' => ['active', 'pending', 'approved']
        ], $result);
    }

    public function testParseNotIn()
    {
        $result = $this->parser->parse('category NOT IN (archived, deleted)');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'category',
            'operator' => 'NOT IN',
            'value' => ['archived', 'deleted']
        ], $result);
    }

    public function testParseQuotedString()
    {
        $result = $this->parser->parse('name EQ "test value"');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'name',
            'operator' => 'EQ',
            'value' => 'test value'
        ], $result);
    }

    public function testParseSingleQuotedString()
    {
        $result = $this->parser->parse("name EQ 'test value'");
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'name',
            'operator' => 'EQ',
            'value' => 'test value'
        ], $result);
    }

    public function testParseAndConditions()
    {
        $result = $this->parser->parse('name EQ test AND age GT 18');
        
        $this->assertEquals([
            'type' => 'AND',
            'conditions' => [
                [
                    'type' => 'condition',
                    'column' => 'name',
                    'operator' => 'EQ',
                    'value' => 'test'
                ],
                [
                    'type' => 'condition',
                    'column' => 'age',
                    'operator' => 'GT',
                    'value' => 18
                ]
            ]
        ], $result);
    }

    public function testParseOrConditions()
    {
        $result = $this->parser->parse('status EQ active OR status EQ pending');
        
        $this->assertEquals([
            'type' => 'OR',
            'conditions' => [
                [
                    'type' => 'condition',
                    'column' => 'status',
                    'operator' => 'EQ',
                    'value' => 'active'
                ],
                [
                    'type' => 'condition',
                    'column' => 'status',
                    'operator' => 'EQ',
                    'value' => 'pending'
                ]
            ]
        ], $result);
    }

    public function testParseGroupedConditions()
    {
        $result = $this->parser->parse('(name EQ test OR name EQ demo) AND status EQ active');
        
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('AND', $result['type']);
        $this->assertArrayHasKey('conditions', $result);
        $this->assertCount(2, $result['conditions']);
        
        // First condition should be an OR group
        $this->assertEquals('OR', $result['conditions'][0]['type']);
    }

    public function testParseComplexGroupedConditions()
    {
        $result = $this->parser->parse('(column1 EQ value1 OR column2 EQ value2) AND (column3 EQ value3)');
        
        $this->assertEquals('AND', $result['type']);
        $this->assertCount(2, $result['conditions']);
        
        // First group should be OR
        $this->assertEquals('OR', $result['conditions'][0]['type']);
        
        // Second group should be a single condition
        $this->assertEquals('condition', $result['conditions'][1]['type']);
    }

    public function testParseEmptyString()
    {
        $result = $this->parser->parse('');
        
        $this->assertEquals([], $result);
    }

    public function testParseNull()
    {
        $result = $this->parser->parse('value EQ null');
        
        $this->assertNull($result['value']);
    }

    public function testParseBoolean()
    {
        $result = $this->parser->parse('active EQ true');
        $this->assertTrue($result['value']);
        
        $result = $this->parser->parse('disabled EQ false');
        $this->assertFalse($result['value']);
    }

    public function testParseInvalidCondition()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->parser->parse('invalid condition without operator');
    }

    public function testParseColumnWithDots()
    {
        $result = $this->parser->parse('events.name EQ test');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'events.name',
            'operator' => 'EQ',
            'value' => 'test'
        ], $result);
    }

    public function testParseInWithNumbers()
    {
        $result = $this->parser->parse('id IN (1, 2, 3, 4, 5)');
        
        $this->assertEquals([
            'type' => 'condition',
            'column' => 'id',
            'operator' => 'IN',
            'value' => [1, 2, 3, 4, 5]
        ], $result);
    }
}
