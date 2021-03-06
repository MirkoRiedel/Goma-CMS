<?php
/**
  *@package goma framework
  *@subpackage template
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2011 Goma-Team
  * last modified: 05.02.2011
*/  


defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class template extends object
{
        /**
		 * the variables
		 *@var array - vars
		 *@access public
		 */
        protected $vars = array();
		/**
		 * the ifs
		 *@var array - ifs
		 *@access public
		**/
		protected $ifs = array();
		/**
		 * this var contains the object
		 *@var object
		*/
		protected $object;
		/**
		 * construction
		 *@name __construct
		 *@param object
		 *@access public
		*/
		public function __construct($object = false)
		{
				parent::__construct();
				
				/* --- */
				
				if(!$object)
				{
						$object = Object::singleton("ViewAccessAbleData");
				}
				$this->object = $object;
		}
		/**
		 * to init a template
		 *@access public
		 *@param string - name of the file
		 *@return string - generated html-code
		**/
		public function init($name)
		{
				
		       	return $this->object->customise($this->vars)->renderWith($name);
		}
		/**
		 * to init a template
		 *@access public
		 *@param string - name of the file
		 *@return string - generated html-code
		**/
		public function display($name)
		{
		       	return $this->init($name);
		}
		/**
		 * to assign a variable
		 * you can access with {$variablename} in template to it
		 *@access public
		 *@param string - name of the variable
		 *@param string - value of the variable
		 */
		public function assign($name, $value)
		{
		        $this->vars[$name] = $value;
		}
		/**
		 * for arrays
		 * in templatecode: <!-- BEGIN name --> {$name.variable} <!-- end name -->
		 *@access public
		 *@param string - name
		 *@param array - the array, e.g. array('variable' => 'Hello World');
		 */
		public function assign_vars($name, $arr)
		{
		        $this->vars[$name][] = $arr;
		}
}