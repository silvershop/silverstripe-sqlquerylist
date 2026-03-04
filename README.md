# SilverShop SQLQueryList

Create DB-driven `SS_List` instances from a SilverStripe `SQLSelect` query. Use them in templates and code like a `DataList` for **display, iteration, counting, sorting, and limiting**—without loading all rows into memory. Ideal for reporting and custom SQL-backed lists.

## Installation

```sh
composer require silvershop/silverstripe-sqlquerylist
```

## Why use it?

The SilverStripe ORM doesn’t let you treat arbitrary SQL as a list. Turning a `SQLQuery` result into an `ArrayList` forces loading every row into memory, which can be expensive or impossible for large result sets.

`SQLQueryList` wraps a `SQLSelect` and implements `SS_List`, so you get:

- **Lazy execution** – rows are only fetched when you iterate or call `toArray()`
- **Efficient `count()`** – uses a `COUNT` query instead of loading all rows
- **Efficient `first()` and `find()`** – use `LIMIT 1` under the hood
- **Sorting and limiting** – `sort()`, `limit()`, and `where()` modify the underlying query
- **Template-friendly** – pass the list to templates and loop with `$List`, paginate, etc.

**Limitations:** The list is **read-only**. You cannot add, remove, or mutate records. Methods like `filter()`, `exclude()`, `map()`, `column()`, and array access are not supported; build those constraints into your `SQLSelect` or use `where()` / `sort()` / `limit()` where available.

---

## Basic usage

Build a `SQLSelect` and wrap it in `SQLQueryList`:

```php
use SilverShop\SQLQueryList\SQLQueryList;
use SilverStripe\ORM\Queries\SQLSelect;

$query = SQLSelect::create(
    ['"Title"', '"ID"', '"Created"'],
    '"SiteTree"',
    ['"ParentID" = 0']
);

$list = SQLQueryList::create($query);
```

Use it like any `SS_List`:

```php
// Count without loading all rows
$total = $list->count();

// Get first record only (single row query)
$first = $list->first();

// Iterate (executes query and yields each row as ArrayData)
foreach ($list as $row) {
    echo $row->Title;
}

// Or get a plain array of ArrayData
$rows = $list->toArray();
```

In a controller, pass it to the template:

```php
public function getReportList()
{
    $query = SQLSelect::create(
        ['"Name"', '"Total"', '"OrderCount"'],
        '"CustomerReport"',  // e.g. a view or custom table
        ['"Total" > 100']
    );
    return new SQLQueryList($query);
}
```

Template:

```ss
<% loop $ReportList %>
    <p>$Name — $Total (orders: $OrderCount)</p>
<% end_loop %>
```

---

## Custom row objects

By default each row is an `ArrayData`. To use your own objects (e.g. DTOs or custom models), set an output closure:

```php
$list = new SQLQueryList($query);
$list->setOutputClosure(function ($row) {
    return MyReportRow::create($row);
});
```

Now `first()`, `find()`, `toArray()`, and iteration return instances of `MyReportRow` instead of `ArrayData`.

---

## Sorting and limiting

`sort()`, `limit()`, and `where()` modify the underlying query and return the same list instance (fluent style):

```php
$query = SQLSelect::create('*', '"Orders"');
$list = new SQLQueryList($query);

// Sort by a column (ASC by default)
$list->sort('"Created"');
// Or specify direction
$list->sort('"Total"', 'DESC');
// Or multiple columns
$list->sort(['"Status"' => 'ASC', '"Created"' => 'DESC']);

// Limit and offset (e.g. pagination)
$list->limit(10, 20);  // 10 rows, skip first 20

// Add extra WHERE conditions
$list->where('"Status" = \'Paid\'');
```

Then iterate or pass to templates as usual.

---

## Finding a row by column value

Use `find()` to get one row by a column value (uses a single-row query):

```php
$list = new SQLQueryList($query);
$row = $list->find('ID', 123);
if ($row) {
    return $row->Title;
}
```

---

## Debugging the query

To see the underlying SQL:

```php
$sql = $list->sql();
```

---

## API summary

| Method / behaviour        | Supported |
|---------------------------|-----------|
| `count()`                 | Yes – uses SQL `COUNT` |
| `first()`                 | Yes – uses `LIMIT 1` |
| `last()`                  | Yes – uses `lastRow()` query |
| `find($key, $value)`      | Yes |
| `map($keyField, $titleField)` | Yes – returns a `Map` |
| `column($colName)`        | Yes |
| `columnUnique($colName)`   | Yes |
| `toArray()`               | Yes |
| `getIterator()` / foreach | Yes |
| `sort(...)`               | Yes |
| `limit($limit, $offset)`  | Yes |
| `where($filter)`          | Yes |
| `sql()`                   | Yes – returns SQL string |
| `setOutputClosure(Closure)` | Yes – custom row objects |
| `add()`, `remove()`       | No – read-only list |
| `filter()`, `exclude()`   | No – use `where()` or build into `SQLSelect` |
| `offsetGet` / `[]`        | No |

---

## Requirements

- PHP ^8.1
- SilverStripe Framework ^6

## License

BSD-3-Clause
