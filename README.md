# SQLQueryList for SilverStripe 4

Create DB-driven SS_Lists based directly off a `SQLQuery`. Particularly useful for reporting.

For the SilverStripe 3.x compatible version of this module, use the 1.x release.

## But why?

The SilverStripe ORM doesn't allow you to work with advanced SQLQueries. You could pass the output of a SQLQuery to an ArrayList, but this approach will always retrieve all records, which can mean memory gets used up.

Records can only be displayed, ordered and filtered. Adding / removing and manipulating records does not work.