<?php
/**
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 19.02.2012
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

/**
 * you also can use a controller-extension, so you can controller-methods
*/
abstract class ControllerExtension extends Controller implements ExtensionModel
{
		/**
		 * works the same as on {@link requestHandler}
		 *
		 *@name url_handlers
		 *@access public
		*/
		public $url_handlers = array();
		
		/**
		 * works the same as on {@link requestHandler}
		 *
		 *@name allowed_actions
		 *@access public
		*/
		public $allowed_actions = array();
		
		/**
		 * extra_methods
		 *
		 *@name extra_methods
		 *@access public
		*/
		public static $extra_methods = array();
		
		/**
		 * the owner-class
		 *@name owner
		 *@access protected
		*/
		protected $owner;
		
		/**
		 * sets the owner-class
		 *@name setOwner
		*/		
		public function setOwner($object)
		{
				if(!is_object($object))
				{
						throwError(20,'PHP-Error', '$object isn\'t a object in '.__FILE__.' on line '.__LINE__.'');
				}
				if(class_exists($object->class))
				{
						$this->owner = $object;
				} else
				{
						throwError(20, 'PHP-Error', 'Class '.$class.' doesn\'t exist in context.');
				}
		}
		
		/**
		 * gets the owner of class
		 *@name getOwner
		*/
		public function getOwner()
		{
				return $this->owner;
		}
		
		/**
		 * gets the url handlers
		*/
		public function url_handlers() {
			return $this->url_handlers;
		}
		
		/**
		 * gets the allowed_actions
		*/
		public function allowed_actions() {
			return $this->allowed_actions;
		}
}