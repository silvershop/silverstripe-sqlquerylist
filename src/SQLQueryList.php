<?php

namespace SilverShop\SQLQueryList;

use ArrayIterator;
use Closure;
use InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\Limitable;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Sortable;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

/**
 * The sweet spot between DataList and ArrayList
 */
class SQLQueryList extends ViewableData implements SS_List, Sortable, Limitable
{
    /**
     * @var SQLSelect
     */
    protected $query;

    protected $outputClosure;

    public function __construct(SQLSelect $query)
    {
        $this->query = $query;
    }

    //List
    public function toArray()
    {
        $rows = $this->query->execute();
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->createOutputObject($row);
        }

        return $results;
    }

    public function setOutputClosure(Closure $closure)
    {
        $this->outputClosure = $closure;
    }

    protected function createOutputObject($row)
    {
        if ($closure = $this->outputClosure) {
            return $closure($row);
        }
        return ArrayData::create($row);
    }

    public function toNestedArray()
    {
        user_error("SQLQueryList doesn't implement toNestedArray");
    }

    public function add($item)
    {
        user_error("SQLQueryList doesn't implement add");
    }

    public function remove($item)
    {
        user_error("SQLQueryList doesn't implement remove");
    }

    public function first()
    {
        foreach ($this->query->firstRow()->execute() as $row) {
            return $this->createOutputObject($row);
        }
    }

    public function last()
    {
        user_error("SQLQueryList doesn't implement last");
    }

    public function map($keyField = 'ID', $titleField = 'Title')
    {
        user_error("SQLQueryList doesn't implement map");
    }

    public function find($key, $value)
    {
        $SQL_col = sprintf('"%s"', Convert::raw2sql($key));

        $query = clone $this->query;
        $query = $query->addWhere("$SQL_col = '" . Convert::raw2sql($value) . "'");

        foreach ($query->firstRow()->execute() as $row) {
            return $this->createOutputObject($row);
        }
    }

    public function column($colName = "ID")
    {
        user_error("SQLQueryList doesn't implement column");
    }

    public function each($callback)
    {
        user_error("SQLQueryList doesn't implement each");
    }

    //ArrayAccess
    public function offsetExists($offset): bool
    {
        user_error("SQLQueryList doesn't implement offsetExists");
    }

    public function offsetGet($offset): mixed
    {
        user_error("SQLQueryList doesn't implement offsetGet");
    }

    public function offsetSet($offset, $value): void
    {
        user_error("SQLQueryList doesn't implement offsetSet");
    }

    public function offsetUnset($offset): void
    {
        user_error("SQLQueryList doesn't implement offsetUnset");
    }

    //Countable
    public function count(): int
    {
        return $this->query->count();
    }

    //IteratorAggregate
    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    //Sortable
    public function canSortBy($by)
    {
        return true;
    }

    public function sort()
    {
        $count = func_num_args();
        if ($count == 0) {
            return $this;
        }
        if ($count > 2) {
            throw new InvalidArgumentException('This method takes zero, one or two arguments');
        }
        $sort = $col = $dir = null;
        if ($count == 2) {
            list($col, $dir) = func_get_args();
        } else {
            $sort = func_get_arg(0);
        }
        if ($col) {
            // sort('Name','Desc')
            if (!in_array(strtolower($dir), ['desc', 'asc'])) {
                user_error('Second argument to sort must be either ASC or DESC');
            }
            $this->query->setOrderBy($col, $dir);
        } else {
            if (is_string($sort) && $sort) {
                // sort('Name ASC')
                if (stristr($sort, ' asc') || stristr($sort, ' desc')) {
                    $this->query->setOrderBy($sort);
                } else {
                    $this->query->setOrderBy($sort, 'ASC');
                }
            } else {
                if (is_array($sort)) {
                    // sort(array('Name'=>'desc'));
                    $this->query->setOrderBy(null, null); // wipe the sort

                    foreach ($sort as $col => $dir) {
                        // Convert column expressions to SQL fragment, while still allowing the passing of raw SQL
                        // fragments.
                        $relCol = $col;
                        $this->query->addOrderBy($relCol, $dir);
                    }
                }
            }
        }

        return $this;
    }

    public function reverse()
    {
        user_error("SQLQueryList doesn't implement reverse");
    }

    //Limitable
    public function limit(?int $limit, int $offset = 0): Limitable
    {
        $this->query->setLimit($limit, $offset);

        return $this;
    }

    public function where($filter)
    {
        $this->query->addWhere($filter);

        return $this;
    }

    public function sql()
    {
        return $this->query->sql();
    }

    public function __clone()
    {
        $this->query = clone $this->query;
    }
}
