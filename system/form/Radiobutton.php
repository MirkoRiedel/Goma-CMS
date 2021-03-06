<?php
/**
  *@package goma form framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 20.09.2012
  * $Version 2.1.2
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class RadioButton extends FormField
{
		/**
		 * options for this set
		 *
		 *@name options
		 *@access public
		*/
		public $options;
		
		/**
		 * which radio-buttons are disabled
		 *
		 *@name disabledNodes
		 *@access public
		*/
		public $disabledNodes = array();
		
		/**
		 * defines if we hide disabled nodes
		 *
		 *@name hideDisabled
		 *@access public
		*/
		public $hideDisabled = false;
		
		/**
		 *@name __construct
		 *@param string - name
		 *@param string - title
		 *@param array - options
		 *@param string - select
		 *@param object - form
		 *@access public
		*/
		public function __construct($name, $title = null, $options = array(), $selected = null, $form = null)
		{
				$this->options = $options;
				parent::__construct($name, $title, $selected, $form);
				
		}
		
		/**
		 * generates the options
		 *
		 *@name options
		 *@access public
		*/
		public function options() {
			$this->callExtending("onBeforeOptions");
			return $this->options;
		}
		
		/**
		 * renders a option-record
		 *
		 *@name renderOption
		 *@access public
		*/
		public function renderOption($name, $value, $title, $checked = null, $disabled = null) {
			if(!isset($checked))
				$checked = false;
			
			if(!isset($disabled))
				$disabled = false;
			
			$id = "radio_" . md5($this->ID() . $name . $value);
			
			$node = new HTMLNode("div", array("class" => "option"), array(
				$input = new HTMLNode('input', array(
					"type" 	=> "radio",
					"name" 	=> $name,
					"value"	=> $value,
					"id"	=> $id
				)),
				$_title = new HTMLNode('label', array(
					"for" => $id
				), $title)
			));
			
			if($checked)
				$input->checked = "checked";
			
			if($disabled)
				$input->disabled = "disabled";
				
			if(isset($disabled) && $disabled && $this->hideDisabled)
				$node->css("display", "none");
				
			$this->callExtending("renderOption", $node, $input, $_title);
			
			return $node;
		}
		
		/**
		 * renders the field
		 *
		 *@name field
		 *@access public
		*/
		public function field()
		{
				$this->callExtending("beforeField");
				
				$this->container->append(new HTMLNode(
					"label",
					array(),
					$this->title
				));
				
				$node = new HTMLNode("div", array("class" => "inputHolder"));
				
				foreach($this->options as $value => $title) {
					
					if($value == $this->value) {
						if($this->disabled || isset($this->disabledNodes[$value])) {
							$node->append($this->renderOption($this->PostName(), $value, $title, true, true));
						} else {
							$node->append($this->renderOption($this->PostName(), $value, $title, true, false));
						}
					} else {
						if($this->disabled || isset($this->disabledNodes[$value])) {
							$node->append($this->renderOption($this->PostName(), $value, $title, false, true));
						} else {
							$node->append($this->renderOption($this->PostName(), $value, $title, false, false));
						}
					}
				}

				$this->container->append($node);
				
				$this->callExtending("afterField");
				
				return $this->container;
		}
		
		/**
		 * adds an option
		 *
		 *@name addOption
		 *@access public
		 *@param string - key
		 *@param mixed - val
		 *@param bool - if to prepend instead of append
		*/
		public function addOption($key, $val, $prepend = false) {
			if(!$prepend)
				$this->options[$key] = $val;
			else
				$this->options = array_merge(array($key => $val), $this->options);
		}
		
		/**
		 * removes an option
		 *
		 *@name removeOption
		 *@access public
		 *@param string - key
		*/
		public function removeOption($key) {
			unset($this->options[$key]);
		}
		
		/**
		 * disables a specific radio-button
		 *
		 *@name disableNode
		 *@access public
		*/
		public function disableOption($id) {
			$this->disabledNodes[$id] = true;
		}
		
		/**
		 * enables a specific radio-button
		 *
		 *@name enableNode
		 *@access public
		*/
		public function enableOption($id) {
			unset($this->disabledNodes[$id]);
		}
	
		/**
		 * validation for security reason
		 *
		 *@name validate
		*/
		public function validate($value) {
			if(!isset($this->options[$value])) {
				return false;
			}
		
			return true;
		}
}