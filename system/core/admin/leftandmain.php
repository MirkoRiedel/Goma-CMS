<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2013  Goma-Team
  * last modified: 04.01.2013
  * $Version 2.3
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class LeftAndMain extends AdminItem {
	
	/**
	 * the base template of the view
	 *
	 *@name baseTemplate
	 *@access public
	*/
	public $baseTemplate = "admin/leftandmain.html";
	
	/**
	 * defines the url-handlers
	 *
	 *@name url_handlers
	 *@access public
	*/
	public $url_handlers = array(
		"updateTree/\$marked!/\$search"	=> "updateTree",
		"edit/\$id!/\$model"			=> "cms_edit",
		"del/\$id!/\$model"				=> "cms_del",
		"add/\$model"					=> "cms_add",
		"versions"						=> "versions"
	);
	
	/**
	 * defines the allowed actions
	 *
	 *@name allowed_actions
	 *@access public
	*/
	public $allowed_actions = array(
		"cms_edit", "cms_add", "cms_del", "updateTree", "savesort", "versions"
	);
	
	/**
	 * this var defines the tree-class
	 *
	 *@name tree_class
	 *@access public
	*/
	public $tree_class = "";
	
	/**
	 * title of root-node
	 *
	 *@name root_node
	 *@access public
	*/
	public $root_node = "";
	
	/**
	 * colors of the nodes
	 * key is class, value is a array with color and name
	 * for example:
	 * array("withmainbar" => array("color" => "#cfcfcf", "name" => "with mainbar"))
	 *
	 *@name colors
	 *@access public
	 *@var array
	*/
	public $colors = array();
	
	/**
	 * marked node
	 *
	 *@name marked
	 *@access public
	*/
	public $marked = 0;
	
	/**
	 * just one time in the request resume should be called
	 *
	 *@name resumeNum
	*/
	static public $resumeNum = 0;
	
	/**
	 * sort-field
	 *
	 *@name sort_field
	 *@access protected
	*/
	protected $sort_field;
	
	/**
	 * tree
	 *
	 *@name tree
	*/
	public $tree;
	
	/**
	 * init tree
	 *
	 *@name Init
	*/
	public function Init() {
		$this->createTree();
		parent::Init();
	}
	
	/**
	 * gets the title of the root node
	 *
	 *@name getRootNode
	 *@access public
	*/
	public function getRootNode() {
		return parse_lang($this->root_node);
	}
	
	/**
	 * generates the options for the create-select-field
	 *
	 *@name CreateOptions
	 *@access public
	*/
	public function createOptions() {
		$options = array();
		foreach($this->models as $model) {
			$options[$model] = ClassInfo::getClassTitle($model);
		}
		return $options;
	}
	
	/**
	 * inserts the data in the leftandmain-template
	 *
	 *@name serve
	 *@access public
	*/
	public function serve($content) {
		if(Core::is_ajax()) {
			HTTPResponse::setBody($content);
			HTTPResponse::output();
			exit;
		}
		
		// add resources
		Resources::add("system/core/admin/leftandmain.js", "js", "tpl");
		
		if(isset($this->sort_field)) {
			Resources::addData("var LaMsort = true;");
		} else {
			Resources::addData("var LaMsort = false;");
		}
		
		$search = isset($_GET["searchtree"]) ? text::protect($_GET["searchtree"]) : "";
		

		Resources::addData("var adminURI = '".$this->adminURI()."';");
		
		$data = $this->ModelInst();
		
		if(defined("LAM_CMS_ADD"))
			$this->ModelInst()->addmode = 1;
		
		if($this->getRootNode()) {
			$node = new TreeNode("root", null, $this->getRootNode(), BASE_SCRIPT . $this->namespace . URLEND, "TreeHolder", "images/icons/fatcow16/world.png");
			$node->setExpanded();
			$this->tree->wrap($node);
		}
		
		$output = $data->customise(array("CONTENT"	=> $content, "activeAdd" => $this->getParam("model"), "SITETREE" => $this->tree->render(), "searchtree" => $search, "ROOT_NODE" => $this->getRootNode(), "types" => $this->types()))->renderWith($this->baseTemplate);
		
		// parent-serve
		return parent::serve($output);
	}

	/**
	 * creates the Tree
	 *
	 *@name createTree
	 *@access public
	*/
	public function createTree($search = "") {
		if(ClassInfo::hasInterface($this->tree_class, "TreeServer")) {
			$this->tree = TreeRenderer::getByClass($this->class, $this->tree_class);
			if(isset($_SESSION["expanded_" . $this->tree_class])) {
				if(is_array($_SESSION["expanded_" . $this->tree_class])) {
					foreach($_SESSION["expanded_" . $this->tree_class] as $id) {
						$this->tree->addExpandedRecord($id);
					}
				} else {
					$this->tree->addExpandedRecord($_SESSION["expanded_" . $this->tree_class]);
				}
			}
		} else {
			$this->tree = new TreeRenderer($this->class, array(
				new TreeNode(0, 0, "Tree is not supported with this version of " . ClassInfo::$appENV["app"]["name"] . ". Please update!", "", "TreeHolder", "images/icons/fatcow16/delete.png")
			));
		}
	}
	
	/**
	 * sets all treenodes to given node expanded
	 *
	 *@name setExpanded
	 *@access public
	*/
	public function setExpanded($id) {
		$this->tree->addExpandedRecord($id);
		$_SESSION["expanded_" . $this->tree_class] = $id;
	}
	
	/**
	 * gets updated data of tree for searching or normal things
	 *
	 *@name updateTree
	 *@access public
	*/
	public function updateTree() {
		
		$this->marked = $this->getParam("marked");
		$search = $this->getParam("search");
		HTTPResponse::setBody($this->createTree($search), isset($_SESSION[$this->class . "_LaM_marked"]) ? $_SESSION[$this->class . "_LaM_marked"] : 0);
		HTTPResponse::output();
		exit;
	}
	
	/**
	 * Actions of editing 
	*/
	
	/**
	 * saves data for editing a site via ajax
	 *
	 *@name ajaxSave
	 *@access public
	 *@param array - data
	 *@param object - response
	*/
	public function ajaxSave($data, $response) {
		if($model = $this->save($data)) {
			// notify the user
			Notification::notify($model->class, lang("successful_saved", "The data was successfully written!"), lang("saved"));
			
			$this->setExpanded($model["id"]);
			
			$response->exec("if(getInternetExplorerVersion() <= 7 && getInternetExplorerVersion() != -1) { var href = '".BASE_URI . $this->adminURI()."/record/".$model->id."/edit".URLEND."'; if(location.href == href) location.reload(); else location.href = href; } else { reloadTree(function(){ LoadTreeItem('".$model["class_name"] . "_" . $model["id"]."'); }); }");
			return $response;
		} else {
			$dialog = new Dialog(lang("less_rights"), lang("error"));
			$response->exec($dialog);
			return $response;
		}
	}
	
	
	/**
	 * saves sort
	 *
	 *@name savesort
	 *@access public
	*/
	public function savesort() {
		$field = $this->sort_field;
		foreach($_POST["treenode"] as $key => $value) {
			DataObject::update($this->tree_class, array($field => $key), array("recordid" => $value));
		}
		$this->marked = $this->getParam("id");
		HTTPResponse::setBody($this->createTree());
		HTTPResponse::output();
		exit;
	}
	
	/**
	 * hides the deleted object
	 *
	 *@name hideDeletedObject
	 *@access public
	*/
	public function hideDeletedObject($response, $data) {
		$response->exec("reloadTree(function(){ LoadTreeItem(0);});");
		return $response;
	}
	
	/**
	 * publishes data for editing a site via ajax
	 *
	 *@name ajaxSave
	 *@access public
	 *@param array - data
	 *@param object - response
	*/
	public function ajaxPublish($data, $response) {
		
		if($model = $this->save($data, 2)) {
			// notify the user
			Notification::notify($model->class, lang("successful_published", "The data was successfully published!"), lang("published"));
			
			$this->setExpanded($model["id"]);
			
			$response->exec("if(getInternetExplorerVersion() <= 9 && getInternetExplorerVersion() != -1) { var href = '".BASE_URI . $this->adminURI()."/record/".$model->id."/edit".URLEND."'; if(location.href == href) location.reload(); else location.href = href; } else {reloadTree(function(){ LoadTreeItem('".$model["class_name"] . "_" . $model["id"]."'); });}");
			return $response;
		} else {
			$dialog = new Dialog(lang("less_rights"), lang("error"));
			$response->exec($dialog);
			return $response;
		}
	}
	
	/**
	 * gets the options for add
	 *
	 *@name Types
	 *@access public
	*/
	public function Types() {
		$data = $this->createOptions();
		$arr = new DataSet();
		foreach($data as $option => $title) {
			$arr->push(array("value" => $option, "title" => $title, "icon" => ClassInfo::getClassIcon($option)));
		}
		return $arr;
	}
	
	/**
	 * hook in this function to decorate a created record of record()-method
	 *
	 *@name decorateRecord
	 *@access public
	*/
	public function decorateRecord(&$record) {
		if(!$record->getVersion()) $record->version = "state";
		$this->setExpanded($record->id);
	}
	
	/**
	 * view all versions
	 *
	 *@name versions
	 *@access public 
	*/
	public function versions() {
		if($this->ModelInst() && $this->ModelInst()->versioned) {
			$controller = new VersionsViewController($this->ModelInst());
			$controller->subController = true;
			return $controller->handleRequest($this->request);
		}
		return false;
	}
	
	/**
	 * adds content-class left-and-main to content-div
	 *
	 *@name contentClass
	 *@access public
	*/
	public function contentClass() {
		return parent::contentclass() . " left-and-main";
	}
	
	/**
	 * add-form
	 *
	 *@name cms_add
	 *@access public
	*/
	public function cms_add() {	
		
		define("LAM_CMS_ADD", 1);
		
		$model = clone $this->modelInst();
		
		if($this->getParam("model")) {
			if(count($this->models) > 1) {
				foreach($this->models as $_model) {
					$_model = trim(strtolower($_model));
					if(is_subclass_of($this->getParam("model"), $_model) || $_model == $this->getParam("model")) {
						$type = $this->getParam("model");
						$model = new $type;
						break;
					}
				}
			} else {
				$models = array_values($this->models);
				$_model = trim(strtolower($models[0]));
				if(is_subclass_of($this->getParam("model"), $_model) || $_model == $this->getParam("model")) {
					$type = $this->getParam("model");
					$model = new $type;
				}
			}
		} else {
			Resources::addJS('$(function(){$(".leftbar_toggle, .leftandmaintable tr > .left").addClass("active");$(".leftbar_toggle, .leftandmaintable tr > .left").removeClass("not_active");$(".leftbar_toggle").addClass("index");});');
		
			$model = new ViewAccessableData();
			return $model->customise(array("adminuri" => $this->adminURI(), "types" => $this->types()))->renderWith("admin/leftandmain_add.html");
		}
		
		if($model->versioned && $model->canWrite($model)) {
			$model->queryVersion = "state";
		}
		
		return $this->selectModel($model)->form();
	}
	
	/**
	 * index-method
	 *
	 *@name index
	*/
	public function index() {
		Resources::addJS('$(function(){$(".leftbar_toggle, .leftandmaintable tr > .left").addClass("active");$(".leftbar_toggle, .leftandmaintable tr > .left").removeClass("not_active");$(".leftbar_toggle").addClass("index");});');
		return parent::index();
	}
}
