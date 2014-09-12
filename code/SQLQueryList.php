<?php

/**
 * The sweet spot between DataList and ArrayList
 */
class SQLQueryList extends ViewableData implements SS_List, SS_Sortable, SS_Limitable{

	protected $query;

	protected $outputclosure;

	function __construct(SQLQuery $query) {
		$this->query = $query;
	}
	
	//List
	public function toArray() {
		$rows = $this->query->execute();
		$results = array();
		foreach($rows as $row) {
			$results[] = $this->createOutputObject($row);
		}
		
		return $results;
	}

	public function setOutputClosure(Closure $closure){
		$this->outputclosure = $closure;
	}

	protected function createOutputObject($row) {
		if($closure = $this->outputclosure){
			return $closure($row);
		}
		return new ArrayData($row);
	}

	public function toNestedArray(){
		user_error("SQLQueryList doesn't implement toNestedArray");
	}

	public function add($item){
		user_error("SQLQueryList doesn't implement add");
	}

	public function remove($item){
		user_error("SQLQueryList doesn't implement remove");
	}

	public function first(){
		foreach($this->query->firstRow()->execute() as $row) {
			return $this->createOutputObject($row);
		}
	}

	public function last(){
		user_error("SQLQueryList doesn't implement last");
	}

	public function map($keyfield = 'ID', $titlefield = 'Title'){
		user_error("SQLQueryList doesn't implement map");
	}

	public function find($key, $value) {
		$SQL_col = sprintf('"%s"', Convert::raw2sql($key));

		$query = clone $this->query;
		$query = $query->addWhere("$SQL_col = '" . Convert::raw2sql($value) . "'");
		
		foreach($query->firstRow()->execute() as $row) {
			return $this->createOutputObject($row);
		}
	}

	public function column($colName = "ID"){
		user_error("SQLQueryList doesn't implement column");
	}
	
	public function each($callback){
		user_error("SQLQueryList doesn't implement each");
	}

	//ArrayAccess
	public function offsetExists($offset){
		user_error("SQLQueryList doesn't implement offsetExists");
	}
	public function offsetGet($offset){
		user_error("SQLQueryList doesn't implement offsetGet");
	}
	public function offsetSet($offset, $value){
		user_error("SQLQueryList doesn't implement offsetSet");
	}
	public function offsetUnset($offset){
		user_error("SQLQueryList doesn't implement offsetUnset");
	}

	//Countable
	public function count() { 
        return $this->query->count();
    }

    //IteratorAggregate
	public function getIterator() {
		return new ArrayIterator($this->toArray());
	}

	//Sortable
	public function canSortBy($by){
		return true;
	}
	public function sort() {
		$count = func_num_args();
		if($count == 0) {
			return $this;
		}
		if($count > 2) {
			throw new InvalidArgumentException('This method takes zero, one or two arguments');
		}
		$sort = $col = $dir = null;
		if ($count == 2) {
			list($col, $dir) = func_get_args();
		}
		else {
			$sort = func_get_arg(0);
		}
		if ($col) {
			// sort('Name','Desc')
			if(!in_array(strtolower($dir),array('desc','asc'))){
				user_error('Second argument to sort must be either ASC or DESC');
			}
			$this->query->setOrderBy($col, $dir);
		}
		else if(is_string($sort) && $sort){
			// sort('Name ASC')
			if(stristr($sort, ' asc') || stristr($sort, ' desc')) {
				$this->query->setOrderBy($sort);
			} else {
				$this->query->setOrderBy($sort, 'ASC');
			}
		}
		else if(is_array($sort)) {
			// sort(array('Name'=>'desc'));
			$this->query->setOrderBy(null, null); // wipe the sort

			foreach($sort as $col => $dir) {
				// Convert column expressions to SQL fragment, while still allowing the passing of raw SQL
				// fragments.
				try {
					$relCol = $list->getRelationName($col);
				} catch(InvalidArgumentException $e) {
					$relCol = $col;
				}
				$this->query->addOrderBy($relCol, $dir, false);
			}
		}

		return $this;
	}

	public function reverse(){
		user_error("SQLQueryList doesn't implement reverse");
	}

	//Limitable
	public function limit($limit, $offset = 0){
		$this->query->setLimit($limit, $offset);

		return $this;
	}

	public function where($filter) {
		$this->query->addWhere($filter);

		return $this;
	}

	public function sql(){
		return $this->query->sql();
	}

	public function __clone() {
		$this->query = clone $this->query;
	}

}