<?php

declare(strict_types=1);

namespace SilverShop\SQLQueryList;

use SilverStripe\Model\List\Map;
use SilverStripe\Model\List\SS_List;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\ModelData;
use Traversable;
use ArrayIterator;
use Closure;
use InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\Queries\SQLSelect;

/**
 * The sweet spot between DataList and ArrayList
 */
class SQLQueryList extends ModelData implements SS_List
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
    public function toArray(): array
    {
        $rows = $this->query->execute();
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->createOutputObject($row);
        }

        return $results;
    }

    public function setOutputClosure(Closure $closure): void
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

    public function toNestedArray(): array
    {
        user_error("SQLQueryList doesn't implement toNestedArray");
    }

    public function add($item): void
    {
        user_error("SQLQueryList doesn't implement add");
    }

    public function remove($item): void
    {
        user_error("SQLQueryList doesn't implement remove");
    }

    public function first(): mixed
    {
        foreach ($this->query->firstRow()->execute() as $row) {
            return $this->createOutputObject($row);
        }
    }

    public function last(): mixed
    {
        user_error("SQLQueryList doesn't implement last");
    }

    public function map($keyField = 'ID', $titleField = 'Title'): Map
    {
        user_error("SQLQueryList doesn't implement map");
    }

    public function find($key, $value): mixed
    {
        $SQL_col = sprintf('"%s"', Convert::raw2sql($key));

        $query = clone $this->query;
        $query = $query->addWhere($SQL_col . " = '" . Convert::raw2sql($value) . "'");

        foreach ($query->firstRow()->execute() as $row) {
            return $this->createOutputObject($row);
        }
    }

    public function column($colName = "ID"): array
    {
        user_error("SQLQueryList doesn't implement column");
    }

    public function columnUnique(string $colName = 'ID'): array
    {
        user_error("SQLQueryList doesn't implement columnUnique");
    }


    public function each($callback): SS_List
    {
        user_error("SQLQueryList doesn't implement each");
    }

    public function canFilterBy(string $by): bool
    {
        user_error("SQLQueryList doesn't implement canFilterBy");
    }

    public function filter(...$args): SS_List
    {
        user_error("SQLQueryList doesn't implement filter");
    }

    public function filterAny(...$args): SS_List
    {
        user_error("SQLQueryList doesn't implement filterAny");
    }

    public function exclude(...$args): SS_List
    {
        user_error("SQLQueryList doesn't implement exclude");
    }

    public function excludeAny(...$args): SS_List
    {
        user_error("SQLQueryList doesn't implement excludeAny");
    }

    public function filterByCallback(callable $callback): SS_List
    {
        user_error("SQLQueryList doesn't implement filterByCallback");
    }

    public function byID(mixed $id): mixed
    {
        user_error("SQLQueryList doesn't implement byID");
    }


    public function byIDs(array $ids): SS_List
    {
        user_error("SQLQueryList doesn't implement byIDs");
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
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }

    //Sortable
    public function canSortBy($by): bool
    {
        return true;
    }

    public function sort(...$args): SS_List
    {
        $count = func_num_args();
        if ($count === 0) {
            return $this;
        }

        if ($count > 2) {
            throw new InvalidArgumentException('This method takes zero, one or two arguments');
        }

        $sort = null;
        $col = null;
        $dir = null;
        if ($count === 2) {
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
        } elseif (is_string($sort) && $sort) {
            // sort('Name ASC')
            if (stristr($sort, ' asc') || stristr($sort, ' desc')) {
                $this->query->setOrderBy($sort);
            } else {
                $this->query->setOrderBy($sort, 'ASC');
            }
        } elseif (is_array($sort)) {
            // sort(array('Name'=>'desc'));
            $this->query->setOrderBy();
            // wipe the sort
            foreach ($sort as $col => $dir) {
                // Convert column expressions to SQL fragment, while still allowing the passing of raw SQL
                // fragments.
                $relCol = $col;
                $this->query->addOrderBy($relCol, $dir);
            }
        }

        return $this;
    }

    public function reverse(): SS_List
    {
        user_error("SQLQueryList doesn't implement reverse");
    }

    public function limit(?int $limit, int $offset = 0): SS_List
    {
        $this->query->setLimit($limit, $offset);

        return $this;
    }

    public function where($filter): static
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
