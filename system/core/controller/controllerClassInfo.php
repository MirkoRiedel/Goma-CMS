<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 22.07.2012
  * $Version 1.0.1
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class ControllerClassInfo extends Extension {
	/**
	 * generates extra class-info for controllers
	 *@name generate
	 *@access public
	 *@param string - class
	*/
	public function generate($class)
	{
		if(class_exists($class) && class_exists("RequestHandler") && is_subclass_of($class, "RequestHandler")) {
			
			if(!ClassInfo::isAbstract($class)) {
				$give = null;
				$c = new $class($give, $give, $give, $give);
				
				$allowed_actions = array();
				foreach($c->callExtending("allowed_actions") as $actions) {
					$allowed_actions = array_merge($allowed_actions, $actions);
					unset($actions);
				}
				if(count($allowed_actions) > 0) {
					ClassInfo::$class_info[$class]["allowed_actions"] = array_map("strtolower", $allowed_actions);
					ClassInfo::$class_info[$class]["allowed_actions"] = ArrayLib::map_key("strtolower", classinfo::$class_info[$class]["allowed_actions"]);
				}
				unset($allowed_actions);
				
				$url_handlers = array();
				foreach($c->callExtending("url_handlers") as $handlers) {
					$url_handlers = array_merge($url_handlers, $handlers);
					unset($handlers);
				}
				
				if(count($url_handlers) > 0)
					ClassInfo::$class_info[$class]["url_handlers"] = $url_handlers;
				unset($url_handlers);
			}
		}
	}
}
Object::extend("ClassInfo", "ControllerClassInfo");