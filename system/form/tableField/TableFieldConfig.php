<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 22.08.2012
  * $Version - 1.0
 */
 
defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class TableFieldConfig extends Object {
	
	/**
	 *
	 * @return TableFieldConfig 
	 */
	public static function create(){
		return new TableFieldConfig();
	}
	
	/**
	 * contains all the components
	 *
	 *@name components 
	 */
	protected $components = null;
	
	/**
	 * constructor
	 */
	public function __construct() {
		$this->components = array();
	}
	
	/**
	 * adds a component to the set
	 *
	 *@name addComponent
	 *@access public
	 */
	public function addComponent(TableFieldComponent $component, $insertBefore = null) {
		if($insertBefore) {
			array_unshift($this->components, $component);
		} else {
			array_push($this->components, $component);
		}
		return $this;
	}

	/**
	 * @param TableFieldComponent One or more components
	 */
	public function addComponents() {
		$components = func_get_args();
		foreach($components as $component) 
			$this->addComponent($component);
		return $this;
	}
	
	/**
	 * @param TableFieldComponent $component 
	 * @return TableFieldConfig $this
	 */
	public function removeComponent(TableFieldComponent $component) {
		foreach($this->components as $k => $c) {
			if($c == $component) {
				unset($this->components[$k]);
			}
		}
		$this->components = array_values($this->components);
		return $this;
	}
	
	/**
	 * @param String Class name or interface
	 * @return TableFieldConfig $this
	 */
	public function removeComponentsByType($type) {
		$components = $this->getComponentsByType($type);
		foreach($components as $component) {
			$this->removeComponent($component);
		}
		return $this;
	}
	
	/**
	 * @return components as array
	 */
	public function getComponents() {
		return $this->components;
	}

	/**
	 * Returns all components extending a certain class, or implementing a certain interface.
	 * 
	 * @param String Class name or interface
	 * @return ArrayList Of TableFieldComponent
	 */
	public function getComponentsByType($type) {
		$components = array();
		foreach($this->components as $component) {
			if($component instanceof $type) 
				array_push($components, $component);
		}
		return $components;
	}

	/**
	 * Returns the first available component with the given class or interface.
	 * 
	 * @param String ClassName
	 * @return TableFieldComponent
	 */
	public function getComponentByType($type) {
		foreach($this->components as $component) {
			if($component instanceof $type) 
				return $component;
		}
	}
}

/**
 * A simple readonly, paginated view of records,
 * with sortable and searchable headers.
 */
class TableFieldConfig_Base extends TableFieldConfig {
	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up per page
	 * @return GridFieldConfig_Base
	 */
	public static function create($itemsPerPage=null){
		return new TableFieldConfig_Base($itemsPerPage);
	}

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		parent::__construct();
		
		$this->addComponent(new TableFieldDataColumns());
		$this->addComponent(new TableFieldToolbarHeader());
		$this->addComponent($sort = new TableFieldSortableHeader());
		$this->addComponent($filter = new TableFieldFilterHeader());
		$this->addComponent($pagination = new TableFieldPaginator($itemsPerPage));

	}
}

/**
 * A simple editable, paginated view of records,
 * with sortable and searchable headers.
 */
class TableFieldConfig_Editable extends TableFieldConfig_Base {
	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up per page
	 * @return GridFieldConfig_Base
	 */
	public static function create($itemsPerPage=null){
		return new TableFieldConfig_Editable($itemsPerPage);
	}

	/**
	 *
	 * @param int $itemsPerPage - How many items per page should show up
	 */
	public function __construct($itemsPerPage=null) {
		parent::__construct($itemsPerPage);
		
		$this->addComponent(new TableFieldEditButton());
		$this->addComponent(new TableFieldDeleteButton());
		$this->addComponent(new TableFieldAddButton());
	}
}