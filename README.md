# SQLQueryList for SilverStripe

Create DB-driven SS_Lists based directly off a `SQLQuery`. Particularly useful for reporting.

## But why?

The SilverStripe 3 ORM doesn't allow you to work with advanced SQLQueries. You could pass the output of a SQLQuery to an ArrayList, but this approach will always retrieve all records, which can mean memory gets used up.

Records can only be displayed, ordered and filtered. Adding / removing and maipulating records does not work.