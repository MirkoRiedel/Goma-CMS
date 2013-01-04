<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 -  2013 Goma-Team
  * last modified: 04.01.2013
  * $Version 1.0
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

interface TreeServer {
	/**
	 * generates a normal tree from a given parentnode on
	 *
	 *@name generateTree
	 *@access public
	 *@param parentID
	 *@param params - custom params for the tree
	*/
	public static function generateTree($parentID = null, $params = array());
	
	/**
	 * generates a tree which was generates because of search
	 *
	 *@name generateSearchTree
	 *@access public
	 *@param string - search-expression
	 *@param parentID
	 *@param params - custom params for the tree
	*/
	public static function generateSearchTree($search, $parentID = null, $params = array());
	
	/**
	 * this returns a list of methods which are supported as params
	 *
	 *@name getParams
	*/
	public function getParams();
}

class TreeRenderer extends Object {
	/**
	 * tree-nodes
	 *
	 *@name tree
	 *@access public
	*/
	public $tree;
	
	/**
	 * array of marked nodes
	 *
	 *@name marked
	 *@access public
	*/
	public $marked = array();
	
	/**
	 * array of nodeids which should be expanded
	 *
	 *@name expandedNodes
	*/
	public $expandedNodes = array();
	
	/**
	 * array of recordids which should be expanded
	 *
	 *@name expandedRecords
	*/
	public $expandedRecords = array();
	
	/**
	 * constructor needs an array of TreeNode-Objects or a TreeNode-Object
	 *
	 *@name __construct
	 *@access public
	 *@param array|treenode
	*/
	public function __construct($tree) {
		if(is_object($tree) && is_a($tree, "TreeNode")) {
			$this->tree = array($tree);
		} else if(is_array($tree)) {
			$this->tree = $tree;
		} else {
			throwError(6, "Invalid Argument Exception", "Tree seems invalid");
		}
	}
	
	/**
	 * returns the tree-renderer by class and parentid and params
	 *
	 *@name getByClass
	 *@access public
	 *@param string - class
	 *@param string - parentid
	 *@param array - params
	*/
	public static function getByClass($class, $parentID = 0, $params = array()) {
		if(!ClassInfo::hasInterface($class, "TreeServer")) {
			throwError(6, "Invalid Argument Error", "Tree-Class '".$class."' is no TreeServer");
		}
			
		return new TreeRenderer(call_user_func_array(array($class, "generateTree"), array($parentID, $params)));
	}
	
	/**
	 * adds an expanded recordid
	 *
	 *@name addExpandedRecord
	*/
	public function addExpandedRecord($id) {
		$this->expandedRecords[$id] = $id;
	}
	
	/**
	 * removes an expanded recordid
	 *
	 *@name removeExpandedRecord
	*/
	public function removeExpandedRecord($id) {
		unset($this->expandedRecords[$id]);
	}
	
	/**
	 * adds an expanded nodeid
	 *
	 *@name addExpandedNode
	*/
	public function addExpandedNode($id) {
		$this->expandedNodes[$id] = $id;
	}
	
	/**
	 * removes an expanded nodeid
	 *
	 *@name removeExpandedNode
	*/
	public function removeExpandedNode($id) {
		unset($this->expandedNodes[$id]);
	}
	
	/**
	 * renders the tree
	 *
	 *@name renderTree
	*/
	public function renderTree($url = null) {
		
	}
	
	/**
	 * wrap tree with given node
	 *
	 *@name wrap
	*/
	public function wrap(TreeNode $node) {
		$node->setChildren($this->tree);
		$this->tree = array($node);
	}
	
	/**
	 * adds a node to the tree
	 *
	 *@name add
	 *@access public
	*/
	public function add($node) {
		if(is_array($node)) {
			foreach($node as $n) {
				$this->tree[$n->nodeid] = $n;
			}
		} else {
			$this->tree[$node->nodeid] = $node;
		}
	}
}

class TreeNode extends Object {
	/**
	 * id of the node
	 *
	 *@name nodeid
	*/
	public $nodeid;
	
	/**
	 * record-id
	 *
	 *@name recordID
	 *@access public
	*/
	public $recordID;
	
	/**
	 * title of the node
	 *
	 *@name title
	*/
	public $title;
	
	/**
	 * url when we click on the treenode
	 *
	 *@name url
	*/
	public $url;
	
	/**
	 * class-name of the tree-node
	 *
	 *@name treeclass
	 *@access public
	*/
	public $treeclass;
	
	/**
	 * icon of this treenode
	 *
	 *@name icon
	*/
	public $icon;
	
	/**
	 * bubbles with different colors
	 * for example: Modified, Submitted
	 *
	 *@name bubbles
	 *@name protected
	*/
	protected $bubbles;
	
	/**
	 * children
	 *
	 *@name children
	*/
	protected $children;
	
	/**
	 * params for ajax-children
	 *
	 *@name ajaxParams
	 *@access protected
	*/
	protected $ajaxParams;
	
	/**
	 * force on state for children:
	 * - open or closed
	 * null for cookie-based
	 *
	 *@name childState
	*/
	public $childState;
	
	/**
	 * you can put the model here
	 *
	 *@name model
	 *@access public
	*/
	public $model;
	
	/**
	 * generates a new treenode
	 *
	 *@name __construct
	 *@access public
	*/
	public function __construct($nodeid, $recordid, $title, $url, $class_name, $icon = null) {
		
		if(strtolower($class_name) != "treeholder" && !ClassInfo::hasInterface($class_name, "TreeServer")) {
			throwError(6, "Invalid Argument Error", "Tree-Class '".$class_name."' is no TreeServer");
		}
		
		$this->nodeid = $nodeid;
		$this->recordid = $recordid;
		$this->title = $title;
		$this->url = $url;
		$this->treeclass = $class_name;
		if(isset($icon) && $icon && $icon = ClassInfo::findFile($icon, $class_name)) {
			$this->icon = $icon;
		} else if(strtolower($class_name) != "treeholder") {
			$this->icon = ClassInfo::getClassIcon($class_name);
		}
	}
	
	/**
	 * returns the icon
	 *
	 *@name getIcon
	 *@access public
	*/
	public function getIcon() {
		return $this->icon;
	}
	
	/**
	 * sets an icon
	 *
	 *@name setIcon
	 *@access public
	*/
	public function setIcon($icon) {
		if($icon && $icon = ClassInfo::findFile($icon)) {
			$this->icon = $icon;
			return true;
		}
		
		return false;
	}
	
	/**
	 * adds a bubble
	 *
	 *@name addBubble
	 *@access public
	 *@param text
	 *@param color: green, yellow, red, blue, grey, orange
	*/
	public function addBubble($text, $color = "blue") {
		switch($color) {
			case "green":
				$bg = "#9dffa2";
				$color = "#098e00";
			break;
			case "red":
				$bg = "#ff7f74";
				$color = "#8e0812";
			break;
			case "yellow":
				$bg = "#ffdf98";
				$color = "#ce9400";
			break;
			default:
				$bg = "#d4f1ff";
				$color = "#005888";
			break;
		}
		
		$this->bubbles[md5($text)] = array("text" => $text, "bg" => $bg, "color" => $color);
	}
	
	/**
	 * removes a bubble
	 *
	 *@name removeBubble
	 *@access public
	 *@param text
	*/
	public function removeBubble($text) {
		unset($this->bubbles[md5($text)]);
	}
	
	/**
	 * sets children
	 *
	 *@name setChildren
	 *@access public
	*/
	public function setChildren($children) {
		// validate and stack it in
		$this->children = array();
		foreach($children as $k => $child) {
			$this->children[$child->nodeid] = $child;
		}
	}
	
	/**
	 * sets children loading via Ajax
	 *
	 *@name setChildrenAjax
	 *@access public
	*/
	public function setChildrenAjax($bool = true, $params = array()) {
		if($bool && $this->children == array()) {
			$this->children = "ajax";
			$this->params = $params;
		} else if(!$bool && $this->children == "ajax") {
			$this->children = array();
			$this->ajaxParams = null;
		}
	}
	
	/**
	 * adds a child
	 *
	 *@name addChild
	 *@access public
	*/
	public function addChild(TreeNode $child) {
		if($this->children != "ajax") {
			if(!isset($this->children))
				$this->children = array();
			
			$this->children[$child->nodeid] = $child;
		} else {
			throwError(6, "PHP-Error", "Could not add Child to node, because seems not as childable, maybe set to ajax?");
		}
	}
	
	/**
	 * removes a child
	 *
	 *@name removeChild
	 *@access public
	*/
	public function removeChild(TreeNode $child) {
		if(is_array($this->children)) {
			unset($this->children[$child->nodeid]);
		} else {
			throwError(6, "PHP-Error", "Could not remove Child from node, because seems not as childable, maybe set to ajax?");
		}
	}
	
	/**
	 * gets all children
	 *
	 *@name Children
	*/
	public function Children() {
		return $this->children;
	}
	
	/**
	 * gets all children
	 *
	 *@name getChildren
	*/
	public function getChildren() {
		return $this->children();
	}
	
	/**
	 * forces to get children
	 *
	 *@name forceChildren
	*/ 
	public function forceChildren() {
		if($this->children == "ajax") {
			return call_user_func_array(array($this->treeclass, "generateTree"), array($this->recordid, $this->ajaxParams));
		} else {
			return $this->Children();
		}
	}
	
	/**
	 * sets children collapsed
	 *
	 *@name setCollapsed
	 *@access public
	*/
	public function setCollapsed() {
		$this->childState = "collapsed";
	}
	
	/**
	 * sets children expanded
	 *
	 *@name setCollapsed
	 *@access public
	*/
	public function setExpanded() {
		$this->childState = "expanded";
	}
	
	/**
	 * sets children to cookie-based
	 *
	 *@name setCookieBased
	 *@access public
	*/
	public function setCookieBased() {
		$this->childState = null;
	}
	
	/**
	 * returns the url for this treenode
	 *
	 *@name getURL
	 *@access public
	 *@param string - given url with parameters
	*/
	public function getURL($given = null) {
		if(!isset($given))
			$given = $this->url;
		
		$newurl = $given;
		preg_match_all('/\$([a-zA-Z0-9_\-]+)/', $given, $matches);
		foreach($matches[1] as $match) {
			switch(strtolower($match)) {
				case "id":
				case "recordid":
					$newurl = str_replace('$' . $match, $this->recordID, $newurl);
				break;
				case "nodeid":
					$newurl = str_replace('$' . $match, $this->nodeid, $newurl);
				break;
				case "title":
					$newurl = str_replace('$' . $match, $this->title, $newurl);
				break;
				case "class":
				case "class_name":
				case "classname":
				case "treeclass":
					$newurl = str_replace('$' . $match, $this->treeclass, $newurl);
				break;
				default:
					$record = $this->record();
					$newurl = str_replace('$' . $match, $record[$match], $newurl);
				break;
			}
		}
	}
	
	/**
	 * returns the record
	 *
	 *@name record
	*/
	public function record() {
		if(isset($this->model))
			return $this->model;
		
		$this->model = DataObject::Get_by_id($this->treeclass, $this->recordid);
		return $this->model;
	}
}

class TreeHolder extends TreeNode {}