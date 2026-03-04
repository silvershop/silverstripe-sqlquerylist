<?php

declare(strict_types=1);

namespace SilverShop\SQLQueryList\Tests;

use PHPUnit\Framework\TestCase;
use SilverShop\SQLQueryList\SQLQueryList;
use SilverStripe\Model\List\Map;
use SilverStripe\ORM\Queries\SQLSelect;

class SQLQueryListTest extends TestCase
{
    public function testSqlReturnsQuerySql(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->method('sql')->willReturn('SELECT * FROM "Test"');
        $list = new SQLQueryList($query);
        $this->assertSame('SELECT * FROM "Test"', $list->sql());
    }

    public function testToArrayReturnsArrayOfOutputObjects(): void
    {
        $rows = [
            ['ID' => 1, 'Title' => 'First'],
            ['ID' => 2, 'Title' => 'Second'],
        ];
        $query = $this->createMock(SQLSelect::class);
        $query->method('execute')->willReturn(new \ArrayIterator($rows));

        $list = new SQLQueryList($query);
        $list->setOutputClosure(function ($row) {
            return (object) ['ID' => $row['ID'], 'Title' => $row['Title']];
        });
        $result = $list->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->ID);
        $this->assertEquals('First', $result[0]->Title);
        $this->assertEquals(2, $result[1]->ID);
        $this->assertEquals('Second', $result[1]->Title);
    }

    public function testToArrayUsesOutputClosureWhenSet(): void
    {
        $rows = [['ID' => 1, 'Name' => 'Test']];
        $query = $this->createMock(SQLSelect::class);
        $query->method('execute')->willReturn(new \ArrayIterator($rows));

        $list = new SQLQueryList($query);
        $list->setOutputClosure(function ($row) {
            return (object) ['id' => $row['ID'], 'name' => $row['Name']];
        });
        $result = $list->toArray();

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Test', $result[0]->name);
    }

    public function testCountDelegatesToQuery(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->method('count')->willReturn(42);

        $list = new SQLQueryList($query);
        $this->assertSame(42, $list->count());
    }

    public function testFirstReturnsFirstRowAsOutputObject(): void
    {
        $row = ['ID' => 1, 'Title' => 'Only'];
        $firstRowQuery = $this->createMock(SQLSelect::class);
        $firstRowQuery->method('execute')->willReturn(new \ArrayIterator([$row]));

        $query = $this->createMock(SQLSelect::class);
        $query->method('firstRow')->willReturn($firstRowQuery);

        $list = new SQLQueryList($query);
        $list->setOutputClosure(fn ($r) => (object) $r);
        $first = $list->first();

        $this->assertNotNull($first);
        $this->assertEquals(1, $first->ID);
        $this->assertEquals('Only', $first->Title);
    }

    public function testFirstReturnsNullWhenNoRows(): void
    {
        $firstRowQuery = $this->createMock(SQLSelect::class);
        $firstRowQuery->method('execute')->willReturn(new \ArrayIterator([]));

        $query = $this->createMock(SQLSelect::class);
        $query->method('firstRow')->willReturn($firstRowQuery);

        $list = new SQLQueryList($query);
        $this->assertNull($list->first());
    }

    public function testLastReturnsLastRowAsOutputObject(): void
    {
        $row = ['ID' => 99, 'Title' => 'Last'];
        $lastRowQuery = $this->createMock(SQLSelect::class);
        $lastRowQuery->method('execute')->willReturn(new \ArrayIterator([$row]));

        $query = $this->createMock(SQLSelect::class);
        $query->method('lastRow')->willReturn($lastRowQuery);

        $list = new SQLQueryList($query);
        $list->setOutputClosure(fn ($r) => (object) $r);
        $last = $list->last();

        $this->assertNotNull($last);
        $this->assertEquals(99, $last->ID);
        $this->assertEquals('Last', $last->Title);
    }

    public function testLastReturnsNullWhenNoRows(): void
    {
        $lastRowQuery = $this->createMock(SQLSelect::class);
        $lastRowQuery->method('execute')->willReturn(new \ArrayIterator([]));

        $query = $this->createMock(SQLSelect::class);
        $query->method('lastRow')->willReturn($lastRowQuery);

        $list = new SQLQueryList($query);
        $this->assertNull($list->last());
    }

    public function testMapReturnsMapInstance(): void
    {
        $rows = [
            ['ID' => 1, 'Title' => 'Alpha'],
            ['ID' => 2, 'Title' => 'Beta'],
        ];
        $query = $this->createMock(SQLSelect::class);
        $query->method('execute')->willReturn(new \ArrayIterator($rows));

        $list = new SQLQueryList($query);
        $list->setOutputClosure(fn ($r) => (object) $r);
        $map = $list->map('ID', 'Title');

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame([1 => 'Alpha', 2 => 'Beta'], $map->toArray());
    }

    public function testColumnReturnsArrayOfFieldValues(): void
    {
        $rows = [
            ['ID' => 1, 'Title' => 'First'],
            ['ID' => 2, 'Title' => 'Second'],
        ];
        $query = $this->createMock(SQLSelect::class);
        $query->method('execute')->willReturn(new \ArrayIterator($rows));

        $list = new SQLQueryList($query);
        $list->setOutputClosure(fn ($r) => (object) $r);
        $this->assertSame([1, 2], $list->column('ID'));
        $this->assertSame(['First', 'Second'], $list->column('Title'));
    }

    public function testColumnUniqueReturnsUniqueValues(): void
    {
        $rows = [
            ['ID' => 1, 'Status' => 'Active'],
            ['ID' => 2, 'Status' => 'Pending'],
            ['ID' => 3, 'Status' => 'Active'],
        ];
        $query = $this->createMock(SQLSelect::class);
        $query->method('execute')->willReturn(new \ArrayIterator($rows));

        $list = new SQLQueryList($query);
        $list->setOutputClosure(fn ($r) => (object) $r);
        $unique = $list->columnUnique('Status');
        $this->assertSame(['Active', 'Pending'], array_values($unique));
    }

    public function testGetIteratorReturnsIteratorOverToArray(): void
    {
        $rows = [['ID' => 1], ['ID' => 2]];
        $query = $this->createMock(SQLSelect::class);
        $query->method('execute')->willReturn(new \ArrayIterator($rows));

        $list = new SQLQueryList($query);
        $list->setOutputClosure(fn ($r) => (object) $r);
        $items = iterator_to_array($list);

        $this->assertCount(2, $items);
        $this->assertEquals(1, $items[0]->ID);
        $this->assertEquals(2, $items[1]->ID);
    }

    public function testSortWithTwoArgumentsCallsSetOrderBy(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->expects($this->once())->method('setOrderBy')->with('Name', 'DESC');

        $list = new SQLQueryList($query);
        $list->sort('Name', 'DESC');
    }

    public function testSortWithSingleStringArgumentCallsSetOrderBy(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->expects($this->once())->method('setOrderBy')->with('Name', 'ASC');

        $list = new SQLQueryList($query);
        $list->sort('Name');
    }

    public function testSortWithZeroArgumentsReturnsSelf(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->expects($this->never())->method('setOrderBy');

        $list = new SQLQueryList($query);
        $result = $list->sort();
        $this->assertSame($list, $result);
    }

    public function testSortWithMoreThanTwoArgumentsThrows(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $list = new SQLQueryList($query);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This method takes zero, one or two arguments');
        $list->sort('A', 'B', 'C');
    }

    public function testLimitCallsSetLimitOnQuery(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->expects($this->once())->method('setLimit')->with(10, 5);

        $list = new SQLQueryList($query);
        $result = $list->limit(10, 5);
        $this->assertSame($list, $result);
    }

    public function testWhereCallsAddWhereOnQuery(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->expects($this->once())->method('addWhere')->with('"Status" = \'Active\'');

        $list = new SQLQueryList($query);
        $result = $list->where('"Status" = \'Active\'');
        $this->assertSame($list, $result);
    }

    public function testCanSortByReturnsTrue(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $list = new SQLQueryList($query);
        $this->assertTrue($list->canSortBy('Anything'));
    }

    public function testCloneReturnsNewInstance(): void
    {
        $query = $this->createMock(SQLSelect::class);
        $query->method('sql')->willReturn('SELECT * FROM "Test"');
        $list = new SQLQueryList($query);
        $cloned = clone $list;
        $this->assertNotSame($list, $cloned);
        $this->assertSame('SELECT * FROM "Test"', $list->sql());
        $this->assertSame('SELECT * FROM "Test"', $cloned->sql());
    }
}
