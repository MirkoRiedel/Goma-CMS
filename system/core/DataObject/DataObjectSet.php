<?php
/**
  * DataSet: for multiple viewaccessable-data records or DataObject records with all in one set without lazy loading, so it's poor performance for much data
  * DataObjectSet: lazy-loading DataSet, so loads Data from DB on demand, so better Performance
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2013  Goma-Team
  *********
  * last modified: 11.01.2013
  * $Version: 1.4.8
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class DataSet extends ViewAccessAbleData implements CountAble {
	/**
	 * pagination-attributes
	*/
	
	/**
	 * if to use pages in this dataset
	 *@name pages
	 *@param bool
	*/
	protected $pagination = false;
	
	/**
	 * how many items per page
	 *
	 *@name perPage
	 *@access public
	*/
	protected $perPage = 10;
	
	/**
	 * the current page of this dataset
	 *
	 *@name page
	 *@access public
	*/
	public $page = null;
	
	/**
	 * data cache, we will store all information here, too
	 *
	 *@name dataCache
	 *@access protected
	*/
	protected $dataCache = array();
	
	/**
	 * protected customised data
	 *
	 *@name protected_customised
	*/
	protected $protected_customised = array();
	
	/**
	 * construction
	 *
	 *@name __construct
	 *@access public
	*/
	public function __construct($set = array()) {
		parent::__construct();
		
		/* --- */
		
		if(isset($set)) {
			$this->dataCache = array_values((array)$set);
			$this->reRenderSet();
		}
	}
	
	/**
	 * groups dataset
	 *
	 *@name groupby
	 *@access public
	*/
	public function groupBy($field) {
		$set = array();
		foreach($this->data as $dataobject) {
			$key = $dataobject[$field];
			if($key !== null) {
				if(!isset($set[$key]))
					$set[$key] = new DataSet();
				
				$set[$key]->push($dataobject);
			}
		}
		return $set;
	}
	/**
	 * getGroupedSet
	 *
	 *@name getGroupedSet
	 *@access public 
	*/
	public function getGroupedSet($field) {
		return new DataSet($this->groupBy($field));
	}
	
	/**
	 * returns the number of records in this set
	 *
	 *@name Count
	 *@access public
	*/
	public function Count() {
		return count($this->dataCache);
	}
	
	/**
	 * deprecated method
	 *
	 *@name _count
	 *@access public
	*/
	public function _count() {
		Core::Deprecate(2.0, "".$this->class."::Count");
		return $this->Count();
	}
	
	
	/**
	 * current sort field
	 *
	 *@access protected
	*/
	protected $sortField;
	
	/**
	 * resorts the data
	 *
	 *@name sort
	 *@access public
	 *@param string - column
	 *@param string - optional - type
	*/
	public function sort($column, $type = "ASC") {
		if(!isset($column))
			return $this;
		
		if(!$this->canSortBy($column))
			return $this;
		
		switch($type) {
			case "DESC":
				$type = "DESC";
			break;
			default:
				$type = "ASC";
			break;
		}
		$this->sortField = $column;
		if($type == "DESC")
			uasort($this->dataCache, array($this, "sortDESCHelper"));
		else
			uasort($this->dataCache, array($this, "sortASCHelper"));
		
		$this->dataCache = array_values($this->dataCache);
		$this->reRenderSet();
		
		return $this;
	}
	
	/**
	 * checks if we can sort by a specefied field
	 *
	 *@name canSortBy
	*/
	public function canSortBy($field) {
		return true; //! TODO: find a method to get this information
	}
	
	/**
	 * checks if we can sort by a specefied field
	 *
	 *@name canSortBy
	*/
	public function canFilterBy($field) {
		return false; //! TODO: Implement Filter in DataSet
	}
	
	/**
	 * helper for Desc-sort
	 *
	 *@name sortDESCHelper
	 *@access public - I think it need to be public
	*/
	public function sortDESCHelper($a, $b) {
		if(isset($b[$this->sortField], $a[$this->sortField]))
   	 		return strcmp($b[$this->sortField], $a[$this->sortField]);
   	 	
   	 	return 0;
	}
	/**
	 * helper for ASC-sort
	 *
	 *@name sortASCHelper
	 *@access public - I think it need to be public
	*/
	public function sortASCHelper($a, $b) {
		if(isset($b[$this->sortField], $a[$this->sortField]))
			return strcmp($a[$this->sortField], $b[$this->sortField]);
		
		return 0;
	}
	
		
	/**
	 * generates an array, where the value is a given field
	 *
	 *@name fieldToArray
	 *@access public
	 *@param string - field
	*/
	public function fieldToArray($field) {
		
		$arr = array();
		foreach((array)$this->data as $record) {
			$arr[] = $record[$field];
		}
		unset($record);
		return $arr;
	}
	
	/**
	 * adds a item to this set
	 *
	 *@name push
	 *@access public
	*/
	public function push($item) {
		if(is_array($this->dataCache))
			array_push($this->dataCache, $item);
		else
			$this->dataCache = array($item);
			
		$this->reRenderSet();
		return true;
	}
	
	/**
	 * alias for push
	*/
	public function add($item, $write = false) {
		return $this->push($item, $write);
	}
	
	/**
	 * removes the last item of the set and returns it
	 *
	 *@name pop
	 *@access public
	*/
	public function pop() {
		$data = array_pop($this->dataCache);
		$this->reRenderSet();
		return $data;
	}
	
	/**
	 * removes the first item of the set and returns it
	 *
	 *@name shift
	 *@access public
	*/
	public function shift() {
		$data = array_shift($this->dataCache);
		$this->reRenderSet();
		return $data;
	}
	
	/**
	 * this returns whether this rentry is the last or not
	 *@name last
	 *@access public
	*/
	public function last()
	{	
		$position = $this->getPosition();
		$content = $this->setPosition($this->Count() - 1);
		$this->position = $position;
		return $content;
	}
	
	
	
	/**
	 * returns the first item
	 *@name first
	 *@access public
	*/
	public function first()
	{	
		if(isset($this->data[key($this->data)])) {
			if(!$this->data[key($this->data)]) {
				$pos = key($this->data);
				while(isset($this->data[$pos]) && !$this->data[$pos]) {
					$pos;
				}
				
				if(!isset($this->data[$pos])) {
					return null;
				}
				
				$d = $this->data[$pos];
			} else {
				$d = $this->data[key($this->data)];
			}
			$data = $this->getConverted($d);
			$this->data[key($this->data)] = $data;
			return $data;
		} else
			return null;
	}
	/**
	 * returns current position
	*/
	public function position() {
		return $this->position;
	}
	/**
	 * returns if this is a highlighted one
	 *
	 *@name highlight
	 *@access public
	*/
	public function highlight() {
		$r = ($this->position + 1) % 2;
		return ($r == 0);
	}
	/**
	 * returns if this is a white one
	 *
	 *@name white
	 *@access public
	*/
	public function white() {
		return (!$this->highlight());
	}
	/**
	 * make the functions on top to variables, for example $this.white
	*/
	public function getWhite() {
		return $this->white();
	}
	public function getHighlight() {
		return $this->highlight();
	}
	public function getFirst() {
		return $this->first();
	}
	
	/**
	 * iterator
	 * this extends this dataobject to use foreach on it
	 * @link http://php.net/manual/en/class.iterator.php
	*/
	/**
	 * this var is the current position
	 *@name position
	 *@access protected
	*/
	protected $position = 0;
	
	/**
	 * rewind $position to 0
	 *
	 *@name rewind
	*/
	public function rewind() {
		if(!is_array($this->data) && !is_object($this->data)) {
			return;
		}
		reset($this->data);
		$this->position = key($this->data);
		
		
		if($this->pagination) {
			while(isset($this->dataSet[$this->position]) && !$this->dataSet[$this->position]) {
				$this->position++;
			}
		} else {
			while(isset($this->data[$this->position]) && !$this->data[$this->position]) {
				$this->position++;
			}
		}
	}
	
	/**
	 * check if data exists
	 *
	 *@name valid
	*/
	public function valid()
	{	
		if(!is_array($this->data) && !is_object($this->data)) {
			return false;
		}
		
		return ($this->position >= key($this->data) && $this->position < count($this->data));
	}
	
	/**
	 * gets the key
	 *
	 *@name key
	*/
	public function key()
	{
		return $this->position;
	}
	
	/**
	 * gets the next one
	 *
	 *@name next
	*/
	public function next()
	{
		$this->position++;
		if($this->pagination) {
			while(isset($this->dataSet[$this->position]) && !$this->dataSet[$this->position]) {
				$this->position++;
			}
		} else {
			while(isset($this->data[$this->position]) && !$this->data[$this->position]) {
				$this->position++;
			}
		}
	}
	
	/**
	 * gets the current value
	 *@name current
	*/
	public function current()
	{
		$data = $this->getConverted($this->data[$this->position]);
		
		if(is_object($data) && is_a($data, "viewaccessabledata"))
			$data->dataSetPosition = $this->position;
		
		$data->queryVersion = $this->version;
		
		$this->data[$this->position] = $data;
		return $data;
	}
	
	/**
	 * sets the position of the array
	 *
	 *@name setPosition
	 *@access public
	*/
	public function setPosition($pos) {
		if($pos < count($this->data) && $pos > -1) {
			$this->position = $pos;
		}
		return $this->current();
	}
	
	/**
	 * gets the position
	 *
	 *@name getPosition
	 *@access public
	*/
	public function getPosition() {
		return $this->position;
	}
	
	/**
	 * gets a Range of items in a DataSet of this DataSet
	 * pagination is always ignored
	 *
	 *@name getRange
	 *@access public
	 *@return DataSet
	*/
	public function getRange($start, $length) {
		$set = new DataSet();
		for($i = $start; $i < ($start + $length); $i++) {
			if(isset($this->dataCache[$i])) 
				$set->push($this->dataCache[$i]);
		}
		return $set;
	}
	
	/**
	 * gets a Range of items as array of this DataSet
	 * pagination is always ignored
	 *
	 *@name getArrayRange
	 *@access public
	 *@return array
	*/
	public function getArrayRange($start, $length) {
		$set = array();
		for($i = $start; $i < ($start + $length); $i++) {
			if(isset($this->dataCache[$i])) 
				$set[] =& $this->dataCache[$i];
		}
		return $set;
	}
	
	/**
	 * activates pagination
	 *
	 *@name activatePagination
	 *@access public
	*/
	public function activatePagination($page = null, $perPage = null) {
		if(isset($perPage) && $perPage > 0)
			$this->perPage = $perPage;
		
		if(isset($page)) {
			
			// first validate the data
			$pages = ceil($this->Count() / $this->perPage);
			if($pages < $page) {
				$page = $pages;
			}
			
			$this->page = $page;
		}
		if(!isset($this->page)) {
			$this->page = 1;
		}
		
		$this->pagination = true;
		$this->reRenderSet();
	}
	
	/**
	 * alias for activatePagination
	 *
	 *@name activatePagination
	 *@access public
	*/
	public function activatePages($page = null, $perPage = null) {
		$this->activatePagination($page, $perPage);
	}
	
	/**
	 * disables pagination
	 *
	 *@name disablePagination
	 *@access public
	*/
	public function disablePagination() {
		$this->pagination = false;
		$this->reRenderSet();
	}
	
	/**
	 * returns if pagination is activated
	 *
	 *@name isPagination
	 *@access public
	*/
	public function isPagination() {
		return $this->pagination;
	}
	
	/**
	 * remakes the variable currentSet for pagination
	 *
	 *@name reRenderSet
	 *@access public
	*/	
	public function reRenderSet() {
		if($this->pagination) {
			$this->dataCache = (array) $this->dataCache + (array) $this->data;
			$start = $this->page * $this->perPage - $this->perPage;
			$count = $this->perPage;
			if($this->Count() < $start) {
				if($this->Count() < $this->perPage) {
					$start = 0;
					$count = $this->perPage;
				} else {
					$pages = ceil($this->Count() / $this->perPage);
					if($this->page < $pages) {
						$this->page = $pages;
					}
					$start = $this->page * $this->perPage - $this->perPage;
				}
			}
			if($start + $count > $this->Count()) {
				$count = $this->Count() - $start;
			}
			$this->data = array_values($this->getArrayRange($start, $count));
			reset($this->data);
		} else {
			$this->data =& $this->dataCache;
		}
	}
	/**
	 * sets the Page
	 *
	 *@name setPage
	 *@access public
	 *@param int - page
	 *@param int - per page
	*/
	public function setPage($page = null, $perPage = null) {
		if(isset($page)) $this->page = $page;
		if(isset($perPage)) $this->perPage = $perPage;
		$this->reRenderSet();
	}
	
	/**
	 * gets available pages
	 *
	 *@name getPages
	 *@access public
	*/
	public function getPages() {
		$pages = ceil($this->Count() / $this->perPage);
		return $this->renderPages($pages, $this->page);
	}
	
	/**
	 * sets pointer to last page
	 *
	 *@name goToLastPage
	 *@access public
	*/
	public function goToLastPage() {
		$pages = ceil($this->Count() / $this->perPage);
		$this->setPage($pages);
	}
	
	/**
	 * returns if it has a page before
	 *
	 *@name isPageBefore
	 *@access public
	*/
	public function isPageBefore() {
		return ($this->page > 1);
	}
	
	/**
	 * checks if there is a next page
	 *
	 *@name isPageNext
	 *@access public
	*/
	public function isNextPage() {
		$pages = ceil($this->Count() / $this->perPage);
		return ($this->page < $pages);
	}
	
	/**
	 * returns the page-number of the next page
	 *
	 *@name nextPage
	 *@access public
	*/
	public function nextPage() {
		$pages = ceil($this->Count() / $this->perPage);
		if($this->page < $pages) {
			return $this->page + 1;
		} else {
			return $pages;
		}
	}
	
	/**
	 * returns the page before
	 *
	 *@name pageBefore
	 *@access public
	*/
	public function pageBefore() {
		if($this->page > 1) {
			return $this->page - 1;
		} else {
			return 1;
		}
	}
	
	/**
	 * get an array of pages by given pagecount
	 *
	 @name renderPages
	 *@access public
	 *@param int - pagecount
	 *@param int - current page
	*/
	public function renderPages($pagecount, $currentpage = 1) {
		if($pagecount < 2) {
			return array(1 => array(
				"page" 	=> 1,
				"black"	=> true
			));
		} else {
			$data = array();
			if($pagecount < 8) {
				for($i = 1; $i <= $pagecount; $i++) {
					$data[$i] = array(
						"page" 	=> ($i),
						"black"	=> ($i == $currentpage)
					);
				}
			} else {
				for($i = 1; $i <= $pagecount; $i++) {
					if($i < 3 || ($i > $currentpage - 3 && $i < $currentpage + 3) || $i > $pagecount - 3) {
						$data[$i] = array(
							"page" 	=> ($i),
							"black"	=> ($i == $currentpage)
						);
						$lastDots = false;
					} else if(!$lastDots && (($i > 2 && $i < ($currentpage - 2)) || ($i < ($pagecount - 2) && $i > ($currentpage + 2)))) {
						$data[$i] = array(
							"page" 	=> "...",
							"black" => true
						);
						$lastDots = true;
					}
				}
			}
			return $data;
		}
	}
	
	
	/**
	 * returns the offset of the first record or the current model
	 *
	 *@name getOffset
	 *@access public
	 *@param string - offset
	 *@param arrray - args
	*/
	public function getOffset($offset, $args = array()) {
		
		if(strtolower($offset) == "count") {
			return $this->Count();
		} else 
		if(Object::method_exists($this->class, $offset) || parent::__canCall($offset, $args)) {
			return parent::getOffset($offset, $args);
		} else {
			if(is_object($this->first())) {
				return $this->first()->getOffset($offset, $args);
			}
		}
	}
	
	/**
	 * returns if a offset exists
	 *
	 *@name __cancall
	 *@access public
	 *@param string - offset
	*/
	public function __cancall($offset) {
		if($offset == "current")
			return true;

		if(strtolower($offset) == "count")
			return true;
		
		return ((Object::method_exists($this->class, $offset) || parent::__cancall($offset)) || (is_object($this->first()) && Object::method_exists($this->first(), $offset)));
	}
	
	/**
	 * sets an offset
	 *
	 *@name __set
	 *@access public
	 *@param string - offset
	 *@param mixed - new value
	*/
	public function __set($key, $value) {
		$name = strtolower(trim($key));
			
		if(Object::method_exists($this->class, "set" . $key)) {
			return call_user_func_array(array($this, "set" . $key), array($value));
		}
		
		if(is_object($this->first())) {
			return $this->first()->__set($key, $value);
		}
		return false;
	}
	
	/**
	 * converts the item to the right format
	 *
	 *@name getConverted
	 *@access protected
	 *@param various - data
	*/
	public function getConverted($item) {
		if(is_array($item)) {
			if(isset($item["class_name"]) && ClassInfo::exists($item["class_name"]))
				$object = new $item["class_name"]($item);
			else
				$object = new ViewAccessableData($item);
		} else {
			$object = $item;
		}
		
		$object->original = $object->data;
		
		$object->dataset =& $this;
		
		if(is_object($object)) {
			$object->customise($this->protected_customised);
			return $object;
		} else {
			return $object;
		}
	}
	
	/**
	 * generates an object from the offset
	 *
	 *@name makeObject
	 *@access public
	 *@param string - offset
	 *@param mixed - data of the offset
	*/
	public function makeObject($offset, $data, $cachename = null) {
		if(parent::__cancall($offset)) {
			return parent::makeObject($offset, $data, $cachename);
		} else {
			if(is_object($this->first())) {
				return $this->first()->makeObject($offset, $data, $cachename);
			}
		}
	}
	
	/**
	 * removes a specific record from the set
	 *
	 *@name removeRecord
	 *@access public
	 *@return record
	*/
	public function removeRecord($record) {
		if(is_object($record)) {
			foreach($this->data as $k => $r) {
				if($r == $record) {
					$this->data[$k] = false;
				}
 			}
 			
 			foreach($this->dataCache as $k => $r) {
	 			if($r == $record) {
					$this->dataCache[$k] = false;
				}
 			}
 			
 			$this->reRenderSet();
 			
 			if(empty($this->data))
				$this->data = array();
			
			return $record;
		} else {
			$r = null;
			$position = $record;
			if($this->pagination) { 
				if(is_array($position)) {
					foreach($position as $p) {
						$this->dataCache[$p] = false;
					}
				} else {
					$r = $this->dataCache[$position];
					$this->dataCache[$position] = false;
				}
		
				// rebuild
				$this->reRenderSet();
			} else {
				if(is_array($position)) {
					foreach($position as $p) {
						$this->data[$p] = false;
						$this->dataCache[$p] = false;
					}
				} else {
					$r = $this->dataCache[$position];
					$this->data[$position] = false;
					$this->dataCache[$position] = false;
				}
			}
			
			if(empty($this->data))
				$this->data = array();
			
			return $r;
		}
	}
	
	/**
	 * on customise make a copy of the data in protected
	 *
	 *@name customise
	 *@access public
	*/
	public function customise($loop = array(), $loop1 = array()) {
		$response = parent::customise($loop, $loop1);
		// we always want to apply the customised data of the first state to each record
		$this->protected_customised = $this->customised;
		return $response;
	}
	
}



class DataObjectSet extends DataSet {
	
	/**
	 * some other props
	*/
	/**
	 * filter for this dataset
	 *
	 *@name filter
	 *@access protected
	*/
	protected $filter = array();
	/**
	 * sorting
	 *
	 *@name sort
	 *@access protected
	*/
	protected $sort;
	/**
	 * limits
	 *
	 *@name limit
	 *@access protected
	*/
	protected $limit;
	/**
	 * joins
	 *
	 *@name join
	 *@access protected
	*/
	protected $join;
	/**
	 * for search
	 *
	 *@name search
	 *@access protected
	*/
	protected $search = array();
	/**
	 * versioning
	 *
	 *@name version
	 *@access protected
	*/
	protected $version;
	
	/**
	 * count of the data in this set
	 *
	 *@name count
	 *@access protected
	*/
	protected $count;

	/**
	 * dataobject for this DataObjectSet
	 *
	 *@name dataobject
	 *@access protected
	*/
	public $dataobject;
	
	/**
	 * data
	 *
	 *@name data
	 *@access public
	*/
	public $data = null;
	
	/**
	 * controller of this dataobjectset
	 *
	 *@name controller
	*/
	public $controller = "";
	
	/**
	 * constructor
	 *
	 *@name __construct
	 *@access public
	*/
	public function __construct($class = null, $filter = null, $sort = null, $limit = null, $join = null, $search = null, $version = null) {
		parent::__construct(null);
		
		if(isset($class)) {
			if(is_a($class, "DataObjectSet")) {
				$class = $class->dataobject;
			}
			
			$this->dataobject = Object::instance($class);
			$this->inExpansion = $this->dataobject->inExpansion;
			$this->dataClass = $this->dataobject->class;
			if($this->dataobject->controller != "")
				$this->controller = $this->dataobject->controller;
			
			$this->filter($filter);
			$this->sort = ClassInfo::getStatic($class, "default_sort");
			$this->limit($limit);
			$this->join($join);
			$this->search($search);
			$this->setVersion($version);
			
			$this->protected_customised = $this->customised;
		}
	}
	
	/**
	 * sets the data and datacache of this set
	 *
	 *@name setData
	 *@access public
	*/
	public function setData($data = array()) {
		$this->dataCache = $data;
		$this->data = (array) $data;
		$this->reRenderSet();
	}
	
	/**
	 * this function returns the data as an array
	 *@name ToArray
	 *@access public
	 *@param array - extra fields, which are not in database
	*/
	public function ToArray($additional_fields = array())
	{
			$data = array();
			foreach((array) $this->data as $record) {
				if(is_object($record)) {
					$data[] = $record->toArray($additional_fields);
				} else {
					$data[] = $record;
				}
			}
			return $data;
	}
	
	/**
	 * gets query-version
	 *
	 *@name queryVersion
	 *@access public
	*/
	public function queryVersion() {
		return $this->version;
	}
	
	/**
	 * returns table_name of current dataobject
	 *
	 *@name getTableName
	 *@access public
	*/
	public function getTable_Name() {
		if(!isset($this->dataobject))
			return null;
		return $this->dataobject->Table();
	}
	
	/**
	 * queries the db for records by given range
	 * the data will be stored in the data-var and given back
	 *
	 *@name getRecordsByRange
	 *@access protected
	 *@param int - start
	 *@param int - length
	 *@return array
	*/
	protected function getRecordsByRange($start, $length) {
		if(PROFILE) Profiler::mark("DataObjectSet::getRecordsByRange");
		
		if(isset($this->limits[0], $this->limits[1])) {
			if(($this->limits[0] + $this->limits[1]) <= $start) {
				if(PROFILE) Profiler::unmark("DataObjectSet::getRecordsByRange");
				return array();
			} else if(($this->limits[0] + $this->limits[1]) < ($start + $length)) {
				$length = ($this->limits[0] + $this->limits[1]) - $start;
			}
		}
		
		$data = array();
		for($i = $start; $i < ($start + $length); $i++) {
			if(isset($this->dataCache[$i])) {
				$data[$i] =& $this->dataCache[$i];
			} else {
				$start = $i;
				$length = $length - $i + $start;
				break;
			}
		}
		
		if($length > 0) {
			$count = $start;
			foreach($this->dataobject->getRecords($this->version, $this->filter, $this->sort, array($start, $length), $this->join, $this->search) as $record) {
				if(!isset($data[$count])) 
					$data[$count] = $record;
				$count++;
				unset($record);
			}
			$this->dataCache = $this->dataCache + $data;
			
			if(PROFILE) Profiler::unmark("DataObjectSet::getRecordsByRange");
			return $data;
		} else {
			if(PROFILE) Profiler::unmark("DataObjectSet::getRecordsByRange");
			return $data;
		}
	}
	
	/**
	 * returns the first item
	 *@name first
	 *@access public
	*/
	public function first($forceObject = true)
	{	
			$this->forceData();
			
			if(is_array($this->data) && count($this->data) > 0 && isset($this->data[key($this->data)])) {
				return $this->current(key($this->data));
			} else if($forceObject) {
				return $this->dataobject;
			} else {
				return false;
			}
	}
	
	/**
	 * gets a Range of items in a DataSet of this DataSet
	 * pagination is always ignored
	 *
	 *@name getRange
	 *@access public
	 *@return DataSet
	*/
	public function getRange($start, $length) {
		return new DataSet($this->getRecordsByRange($start, $length));
	}
	
	/**
	 * gets a Range of items as array of this DataSet
	 * pagination is always ignored
	 *
	 *@name getArrayRange
	 *@access public
	 *@return array
	*/
	public function getArrayRange($start, $length) {
		return $this->getRecordsByRange($start, $length);
	}
	
	/**
	 * count
	 *
	 *@name Count
	 *@access public
	*/
	public function Count() {
		if(isset($this->count)) {
			return $this->count;
		} else if(count($this->data) > 0 && (($this->page == 1 && count($this->data) < $this->perPage) || !$this->pagination)) {
			$this->count = count($this->data);
			return $this->count;
		} else {
			$data = $this->dataobject->getAggregate($this->version, 'count(*) as count', $this->filter, array(), $this->limit, $this->join, $this->search);
			if(isset($data[0]["count"])) {
				$this->count = $data[0]["count"];
				return $this->count;
			} else {
				return null;
			}
		}
	}
	
	/**
	 * rewind
	 *
	 *@name rewind
	 *@access public
	*/
	public function rewind() {
		$this->forceData();
		parent::rewind();
	}
	
	/**
	 * gets the current value
	 *@name current
	*/
	public function current($position = null)
	{
		if(!isset($position))
			$position = $this->position;
		
		$this->forceData();
		if(isset($this->data[$position]))
			$data = $this->data[$position];
		else {
			// get next range
			$this->data = $this->getRecordsByRange($position, $this->perPage);
			$data = $this->data[$position];
		}
		
		$data = $this->getConverted($data);
		
		if(is_object($data) && is_a($data, "viewaccessabledata"))
			$data->dataSetPosition = $position;
		
		$data->queryVersion = $this->version;
		
		$this->data[$position] = $data;
		
		return $data;
	}
	
	/**
	 * forces to have the data from the database
	 *
	 *@name forceData
	 *@access public
	 *@param numeric - position
	*/
	public function forceData($position = null) {
		
		if(!isset($this->dataCache) && isset($this->dataobject)) {
			if(!$this->pagination) {
				$this->dataCache = $this->dataobject->getRecords($this->version, $this->filter, $this->sort, $this->limit, $this->join, $this->search);
			}
			$this->reRenderSet();
		}
		
		
		return $this;
	}
	
	
	/**
	 * check if data exists
	 *@name valid
	*/
	public function valid()
	{
		$this->forceData();
		return parent::valid();
	}
	
	/**
	 * filters the data
	 *
	 *@name filter
	 *@access public 
	*/
	public function filter($filter) {
		if(isset($filter) && $this->filter != $filter) {
			$this->filter = $filter;
			$this->purgeData();
		}
		return $this;
	}
	
	/**
	 * adds a filter
	 *
	 *@name addFilter
	 *@access public
	*/
	public function addFilter($filter) {
		if(isset($filter)) {
			$this->filter = array_merge((array) $this->filter, (array) $filter);
			$this->purgeData();
		}
		return $this;
	}
	
	/**
	 * group by a specific field
	 *
	 *@name groupBy
	 *@access public
	*/
	public function groupBy($field) {
		return $this->dataobject->getGroupedRecords($this->version, $field, $this->filter, $this->sort, $this->limit, $this->join, $this->search);
	}
	
	/**
	 * purges current data from this set
	 *
	 *@name purgeData
	 *@access protected
	*/
	protected function purgeData() {
		$this->data = null;
		$this->count = null;
		$this->dataCache = null;
	}
	
	/**
	 * adds a join
	 *
	 *@name addJoin
	 *@access public
	*/
	public function addJoin($join) {
		$this->join = array_merge((array)$this->join, (array)$join);
		$this->purgeData();
		return $this;
	}
	
	/**
	 * removes a join by given key
	 *
	 *@name removeJoin
	 *@access public
	*/
	public function removeJoin($key) {
		unset($this->join[$key]);
		$this->purgeData();
		return $this;
	}
	
	/**
	 * sets the variable join
	 *
	 *@name join
	 *@access public
	*/
	public function join($join) {
		if(isset($join)) {
			$this->join = $join;
			$this->purgeData();
		}
		return $this;
	}
	
	/**
	 * sets limits
	 *
	 *@name limit
	 *@access public
	*/
	public function limit($limit) {
		if(!isset($limit) || count($limit) == 0)
			return $this;
		
		if(is_array($limit)) {
			$limit = array_values($limit);
			if(isset($limit[0], $limit[1])) {
				$this->limit = $limit;
			} else if($limit[0]) {
				$this->limit = array(0, $limit[0]);
			} else {
				return $this;
			}
		} else if($this->limit) {
			$this->limit = array(0, $limit[0]);
		} else {
			return $this;
		}
		$this->purgeData();
		return $this;
	}
	
	/**
	 * activates pagination
	 *
	 *@name activatePagination
	 *@access public
	*/
	public function activatePagination($page = null, $perPage = null) {
		if(isset($perPage) && $perPage > 0)
			$this->perPage = $perPage;
		
		if(isset($page)) {
			
			// first validate the data
			$pages = ceil($this->Count() / $this->perPage);
			if($pages < $page) {
				$page = $pages;
			}
			
			$this->page = $page;
		}
		if(!isset($this->page)) {
			$this->page = 1;
		}
		
		$this->pagination = true;
		$this->purgeData();
	}
	
	/**
	 * resorts the data
	 *
	 *@name sort
	 *@access public
	 *@param string - column
	 *@param string - optional - type
	*/
	public function sort($column, $type = "ASC") {
		if(!isset($column))
			return $this;
		
		if(!$this->canSortBy($column))
			return $this;
		
		switch(strtolower($type)) {
			case "desc":
				$type = "DESC";
			break;
			default:
				$type = "ASC";
			break;
		}
		
		if(isset($this->sort["field"]) && $this->sort["field"] == $column && $this->sort["type"] == $type) {
			return $this;
		}
		
		$this->sort = array("field" => $column, "type" => $type);
		$this->purgeData();
		
		return $this;
	}
	
	/**
	 * checks if we can sort by a specefied field
	 *
	 *@name canSortBy
	*/
	public function canSortBy($field) {
		return $this->dataobject->canSortBy($field);
	}
	
	/**
	 * checks if we can sort by a specefied field
	 *
	 *@name canSortBy
	*/
	public function canFilterBy($field) {
		return $this->canSortBy($field); //! TODO: Implement Filter in DataObjectSet
	}
	
	/**
	 * sets version-type
	 *
	 *@name version
	 *@access public
	*/
	public function setVersion($version) {
		$this->version = $version;
		$this->dataobject->queryVersion = $version;
		$this->purgeData();
		return $this;
	}
	
	/**
	 * returns the current version
	 *
	 *@name getVersion
	 *@access public
	*/
	public function getVersion() {
		return $this->version;
	}
	
	/**
	 * search
	 *
	 *@name search
	 *@access public
	*/
	public function search($search) {
		if(isset($search)) {
			$this->search = $search;
			$this->purgeData();
		}
		return $this;
	}
	
	/**
	 * adds a new record to this set
	 *
	 *@name add
	 *@access public
	*/
	public function push(DataObject $record, $write = false) {
		foreach((array) $this->defaults as $key => $value) {
			if(empty($record[$key]))
				$record[$key] = $value;
		}
		
		$return = parent::push($record);
		if($write) {
			$record->write(false, true);
		}
		return $return;
	}
	
	/**
	 * adds a new record to this set
	 *
	 *@name addMany
	 *@access public
	*/
	public function addMany($data) {
		$addedIDs = array();
		foreach($data as $record) {
			if(is_integer($record)) {
				$_data = DataObject::get_one($this->dataobject, array("id" => $record));
				if($_data) {
					$this->add($_data);
					$addedIDs = $record;
				}
			} else {
				$this->add($record);
				$addedIDs = $record->ID;
			}
		}
		return $addedIDs;
	}
	
	/**
	 * converts the item to the right format
	 *
	 *@name getConverted
	 *@access protected
	 *@param various - data
	*/
	public function getConverted($item) {
		if(is_array($item)) {
			if(isset($item["class_name"]) && ClassInfo::exists($item["class_name"]))
				$object = new $item["class_name"]($item);
			else
				$object = new $this->dataobject->class ($item);
		} else {
			$object = $item;
		}
		
		$object->original = $object->data;
		
		$object->dataset =& $this;
		
		if(is_object($object) && Object::method_exists($object, "customise")) {
			$object->customise($this->protected_customised);
			return $object;
		} else {
			return $object;
		}
	}
	
	
	/**
	 * gets the controller
	 *
	 *@name controller
	 *@access public
	*/
	public function controller($controller = null) {
		
		
		if(isset($controller)) {
			$this->controller = clone $controller;
			$this->controller->model_inst = $this;
			$this->controller->model = $this->dataobject->class;
			return $this->controller;
		}
		
		if(is_object($this->controller))
		{
			return $this->controller;
		}
		
		/* --- */
		
		if($this->controller != "")
		{
				$this->controller = new $this->controller;
				$this->controller->model_inst = $this;
				$this->controller->model = null;
				return $this->controller;
		} else {
			
			if(ClassInfo::exists($this->dataobject->class . "controller"))
			{
					$c = $this->dataobject->class . "controller";
					$this->controller = new $c;
					$this->controller->model_inst = $this;
					$this->controller->model = null;
					return $this->controller;
			} else {
				if(ClassInfo::getParentClass($this->dataobject->class) != "dataobject") {
					$parent = $this->dataobject->class;
					while(($parent = ClassInfo::getParentClass($parent)) != "dataobject") {
						if(!$parent)
							return false;
						
						if(ClassInfo::exists($parent . "controller")) {
							$c = $parent . "controller";
							$this->controller = new $c;
							$this->controller->model_inst = $this;
							$this->controller->model = null;
							return $this->controller;
						}
					}
				}
			}
		}
		return false;
	}
	

		
	/**
	 * toString
	 *
	 *@name toString
	 *@access public
	*/
	public function __toString() {
		if($controller = $this->controller()) {
			if($controller->template != "")
				return $controller->index();
			else
				return false;
		}
		return "controller not found";
	}
	
	/**
	 * bool - for IF in template
 	 *
	 *@name toBool
	 *@access public
	*/
	public function bool() {
		return ($this->Count() > 0);
	}
	
	/**
	 * returns an array of the values of a specific field
	 *
	 *@name fieldToArray
	 *@access public
	*/
	public function fieldToArray($field) {
		$this->forceData();
		return parent::fieldToArray($field);
	}
	
	/**
	 * write to DB
	 *
	 *@name write
	 *@access public
	 *@param bool - to force insert
	 *@param bool - to force write
	 *@param numeric - priority of the snapshop: autosave 0, save 1, publish 2
	*/
	public function write($forceInsert = false, $forceWrite = false, $snap_priority = 2) {
		$writtenIDs = array();
		if(count($this->data) > 0) {
			foreach($this->data as $record) {
				if(is_object($record) && (!isset($writtenIDs[$record->id]) || $record->id == 0)) {
					$writtenIDs[$record->id] = true;
					if(!$record->write($forceInsert, $forceWrite, $snap_priority)) {
						return false;
					}
				}
			}
			return true;
		} else
			return $this->dataobject->write();
	}
	
	/**
	 * deletes the records in stack
	 *
	 *@name remove
	 *@access public
	 *@param bool - force delete
	 *@param bool - if cancel on error, or resume
	 *@param bool - if force to delete versions, too
	*/
	private function remove($force = false, $forceAll = false) {
		foreach($this as $key => $record) {
			if($record->remove($force, $forceAll)) {
				unset($this->data[$key]);
				unset($this->dataCache[$key]);
			}
		}
		return true;
	}
	
	/**
	 * public removal
	*/
	public function getRemove() {
		throwError(6, "Not allowed", "Method remove is not allowed anymore and DataObjectSet, please select a single DataObject");
	}
	
	/**
	 * generates a form
	 *
	 *@name form
	 *@access public
	 *@param string - name
	 *@param bool - edit-form
	 *@param bool - disabled
	*/
	public function generateForm($name = null, $edit = false, $disabled = false) {
		
		// if name is not set, we generate a name from this model
		if(!isset($name)) {
			$name = $this->dataobject->class . "_" . $this->dataobject->versionid . "_" . $this->dataobject->id;
		}
		
		$form = new Form($this->controller(), $name);
		if($disabled)
			$form->disable();
			
		// default submission
		$form->setSubmission("submit_form");	
			
		$form->addValidator(new DataValidator($this->dataobject), "datavalidator");
		
		$form->setResult(clone $this->dataobject);
		
		$form->add(new HiddenField("class_name", $this->dataobject->class));
		
		foreach($this->defaults as $key => $value) {
			$form->add(new HiddenField($key, $value));
		}
		
		// render form
		if($edit) {
			$this->dataobject->getEditForm($form, array());
		} else {
			$this->dataobject->getForm($form, array());
		}
		
		$this->dataobject->callExtending('getForm', $form, $edit);
		$this->dataobject->getActions($form, $edit);
		$this->dataobject->callExtending('getActions', $form, $edit);
		
		if(isset($this->controller) && $this->controller) {
			$this->controller->model_inst = $this->dataobject;
		}
		
		return $form;
	}
	
	/**
	 * generates the form via controller
	 *
	 *@name form
	 *@access public
	*/
	public function form() {
		return $this->controller()->form(null, $this);
	}
	
	/**
	 * generates a form
	 *
	 *@name renderForm
	 *@access public
	*/
	public function renderForm() {
		return $this->controller()->renderForm(null, $this);
	}
	
	
	public function __cancall($offset) {
		$loweroffset = trim(strtolower($offset));
		if($loweroffset == "current")
			return true;

		return parent::__cancall($offset);
	}
	
	// some API patches
	public function isDeleted() {
		return $this->first()->isDeleted();
	}
	public function isPublished() {
		return $this->first()->isPublished();
	}
	public function everPublished() {
		return $this->first()->everPublished();
	}
}

/**
 * for has-many-relation
 *
 *@name HasMany_DataObjectSet
*/
class HasMany_DataObjectSet extends DataObjectSet {

	/**
	 * field for the relation according to this set, for example: pageid or groupid
	 *
	 *@name field
	 *@access protected
	*/
	protected $field;
	
	/**
	 * name of the relation
	 *
	 *@name relationName
	 *@access protected
	*/
	protected $relationName;
	
	/**
	 * sets the relation-props
	 *
	 *@name setRelationENV
	 *@access public
	 *@param string - name
	 *@param string - field
	*/
	public function setRelationENV($name = null, $field = null, $id = null) {
		if(isset($name)) 
			$this->relationName = $name;
		if(isset($field))
			$this->field = $field;
		
		if(isset($id))
			foreach($this as $record)
				$record[$field] = $id;
	}
	
	/**
	 * get the relation-props
	 *
	 *@name getRelationENV
	 *@access public
	 *@return array
	*/
	public function getRelationENV() {
		return array("name" => $this->name, "field" => $this->field);
	}
	
	
	/**
	 * generates a form
	 *
	 *@name form
	 *@access public
	 *@param string - name
	 *@param bool - edit-form
	 *@param bool - disabled
	*/
	public function generateForm($name = null, $edit = false, $disabled = false) {
		
		if(isset($this[$this->field])) {
			$this->dataobject[$this->field] = $this[$this->field];
		} else if(isset($this->filter[$this->field]) && is_string($this->filter[$this->field]) || is_int($this->filter[$this->field])) {
			$this->dataobject[$this->field] = $this->filter[$this->field];
		}
		
		$form = parent::generateForm($name, $edit, $disabled);
		
		if(isset($this[$this->field])) {
			$form->add(new HiddenField($this->field, $this[$this->field]));
		} else if(isset($this->filter[$this->field]) && is_string($this->filter[$this->field]) || is_int($this->filter[$this->field])) {
			$form->add(new HiddenField($this->field, $this->filter[$this->field]));
		}
		return $form;
	}
	
	/**
	 * sets the has-one-relation when adding to has-many-set
	 *
	 *@name push
	*/
	public function push(DataObject $record, $write = false) {
		if($this->class == "hasmany_dataobjectset") {
			if(isset($this[$this->field])) {
				$record[$this->field] = $this[$this->field];
			} else if(isset($this->filter[$this->field]) && (is_string($this->filter[$this->field]) || is_int($this->filter[$this->field]))) {
				$record[$this->field] = $this->filter[$this->field];
			}
		}
		
		$return = parent::push($record);
		if($write) {
			$record->write(false, true);
		}
		return $return;
	}
	
	/**
	 * removes the relation on writing
	 *
	 *@name removeRecord
	*/
	public function removeRecord($record, $write = false) {
		$record = parent::removeRecord($record);
		if($write) {
			$record[$this->field] = 0;
			if(!$record->write()) {
				throwError(6, "Permission-Error", "Could not remove Relation from Record ".$record->class.": ".$record->ID."");
			}
		}
		return $record;
	}
}

/**
 * for many-many-relation
 *
 *@name ManyMany_DataObjectSet
 *@access public
*/
class ManyMany_DataObjectSet extends HasMany_DataObjectSet {
	/**
	 * relation-table
	 *
	 *@name relationTable
	 *@access protected
	*/
	protected $relationTable;
	
	/**
	 * external field, for many-many-relations only
	 *
	 *@name extField
	 *@access protected
	*/
	protected $ownField;
	
	/**
	 * value of $ownField
	 *
	 *@name ownValue
	 *@access protected
	*/
	protected $ownValue;
	
	/**
	 * extra-fields in this many-many-table
	 *
	 *@name extraFields
	 *@access protected
	*/
	protected $extraFields = array();
	
	/**
	 * sets the relation-props
	 *
	 *@name setRelationENV
	 *@access public
	 *@param string - name
	 *@param string - field
	 *@param string - table of relation
	 *@param string - own field, not the field where to set the given IDs, the field where to store the current id
	 *@param string - the value of the own field, so the id
	*/
	public function setRelationENV($name = null, $field = null, $table = null, $ownField = null, &$ownValue = null, $extraFields = array()) {
		parent::setRelationENV($name, $field);
		if(isset($table))
			$this->relationTable = $table;
		
		if(isset($ownField))
			$this->ownField = $ownField;
			
		if(isset($ownValue))
			$this->ownValue = $ownValue;
		
		if(isset($extraFields) && is_array($extraFields))
			$this->extraFields = $extraFields;
		
		if($this->extraFields && $this->ownValue != 0) {
			// search second join
			foreach((array) $this->join as $table => $data) {
				if(strpos($data, $this->relationTable)) {
					unset($this->join[$table]);
				}
			}
			
			$this->join[$this->relationTable] = " INNER JOIN " . DB_PREFIX . $this->relationTable . " AS " . $this->relationTable . " ON " . $this->relationTable . "." . $this->field . " = " . $this->dataobject->table() . ".id AND " . $this->relationTable . "." . $this->ownField . " = '" . $this->ownValue . "'";
		}
	}
	
	/**
	 * get the relation-props
	 *
	 *@name getRelationENV
	 *@access public
	 *@return array
	*/
	public function getRelationENV() {
		return array("name" => $this->name, "field" => $this->field, "relationTable" => $this->relationTable, "ownField" => $this->ownField, "ownValue" => $this->ownValue, "extraFields" => $this->extraFields);
	}
	
	/**
	 * sets the variable join
	 *
	 *@name join
	 *@access public
	*/
	public function join($join) {
		if(isset($join)) {
			$this->join = $join;
			if($this->extraFields)
				$this->join[$this->relationTable] = "";
			$this->purgeData();
		}
		return $this;
	}

	/**
	 * write to DB
	 *
	 *@name write
	 *@access public
	 *@param bool - to force insert
	 *@param bool - to force write
	 *@param numeric - priority of the snapshop: autosave 0, save 1, publish 2
	*/
	public function write($forceInsert = false, $forceWrite = false, $snap_priority = 2) {
		$writtenIDs = array();
		$writeExtraFields = array();
		
		if(count($this->data) > 0) {
			
			// write all records
			foreach($this as $record) {
				
				// check if object and writable
				if((is_object($record) && !isset($writtenIDs[$record->versionid])) || $record->id == 0) {
					// write
					if(!$record->write($forceInsert, $forceWrite, $snap_priority)) {
						return false;
					}
					
					$writtenIDs[$record->versionid] = true;
					$writeExtraFields[$record->versionid] = array();
					
					// add extra fields
					foreach($this->extraFields as $field => $char) {
						if(isset($record[$field])) {
							$writeExtraFields[$record->versionid][$field] = $record[$field];
						}
					}
				}
			}
		} else {
			
			// if we don't have records the local dataobject could be changed an have to be written then
			if(!$this->dataobject || !$this->dataobject->wasChanged()) {
				return true;
			}
			$record = $this->dataobject;
			if($record->write($forceInsert, $forceWrite, $snap_priority)) {
				$writtenIDs[$record->versionid] = true;
				$writeExtraFields[$record->versionid] = array();
				
				// add extra fields
				foreach($this->extraFields as $field => $char) {
					if(isset($record[$field])) {
						$writeExtraFields[$record->versionid][$field] = $record[$field];
					}
				}
			} else
				return false;
		}
		
		// check for existing entries
		$query = new SelectQuery($this->relationTable, array("*"), array($this->ownField => $this->ownValue));
		if($query->execute()) {
			$existing = array();
			$existingFields = array();
			while($row = $query->fetch_object()) {
				$existing[$row->id] = $row->{$this->field};
				$existingFields[$row->{$this->field}] = array();
				
				// add extra fields, which exist
				foreach($this->extraFields as $field => $char) {
					$existingFields[$row->{$this->field}][$field] = $row->{$field};
				}
			}
		} else {
			throwErrorByID(5);
		}
		
		$manipulation = array(
			"insert" => array(
				"command"	=> "insert",
				"table_name"=> $this->relationTable,
				"fields"	=> array(
					
				)
			)
		);
		
		$i = 0;
		foreach(array_keys($writtenIDs) as $id) {
			if(!in_array($id, $existing)) {
				$manipulation["insert"]["fields"][$i] = array(
					$this->ownField => $this->ownValue,
					$this->field	=> $id
				);
				$manipulation["insert"]["fields"][$i] = array_merge($manipulation["insert"]["fields"][$i], $writeExtraFields[$id]);
				$i++;
			} else {
				if($writeExtraFields[$id] != $existingFields[$id]) {
					$manipulation[] = array(
						"command"	=> "update",
						"table_name"=> $this->relationTable,
						"id"		=> array_search($id, $existing),
						"fields"	=> $writeExtraFields[$id]
					);
				}
				unset($existing[array_search($id, $existing)]);
			}
			
		}
		
		if(count($existing) > 0) {
			$manipulation["delete"] = array(
				"command"	=> "delete",
				"table_name"=> $this->relationTable,
				"where"	=> array(
					$this->field => array()
				)
			);
				
			foreach($existing as $remove) {
				$manipulation["delete"]["where"][$this->field][] = $remove;
			}
		}
		
		$this->dataobject->callExtending("onBeforeManipulateManyMany", $manipulation, $this, $writtenIDs, $writeExtraFields);
		if(SQL::manipulate($manipulation)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * writes the many-many-relation immediatly if writing
	 *
	 *@name push
	*/
	public function push(DataObject $record, $write = false) {
		$return = parent::push($record);
		if($write) {
			$record->write(false, true);
			$manipulation = array(
				array(
					"command"	=> "insert",
					"table_name"=> $this->relationTable,
					"fields"	=> array(
						array(
							$this->ownField => $this->ownValue,
							$this->field	=> $record->versionid
						)
					)
				)
			);
			SQL::manipulate($manipulation);
		}
		return $return;
	}
	
	/**
	 * removes the relation on writing
	 *
	 *@name removeRecord
	*/
	public function removeRecord($record, $write = false) {
		$record = parent::removeRecord($record);
		if($write) {
			$manipulation = array(
				array(
					"command"	=> "delete",
					"table_name"=> $this->relationTable,
					"where"		=> array(
						$this->field 	=> $record->versionid,
						$this->ownField => $this->ownValue
					)
				)
			);
			SQL::manipulate($manipulation);
		}
		return $record;
	}
}
