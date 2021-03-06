<?php
/**
  *@package goma
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2010  Goma-Team
  *@todo remove hacks
  *@author Fabian Parzefall, edited by Daniel Gruber
  * last modified: 05.07.2011
*/

defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class CSSMin extends Object
{
		/**
		  * the before char
		  *@name a
		  *@access protected
		  *@var string
		*/
		protected $a = "";
		/**
		  * the current char
		  *@name b
		  *@access protected
		  *@var string
		*/
		protected $b = "";
		/**
		 * the next char
		 *
		 *@name c
		 *@access protected 
		*/
		protected $c = "";
		/**
		  * the data to minify
		  *@name input
		  *@access protected
		  *@var string
		*/
		protected $input = "";
		/**
		  * the length of the data
		  *@name inputLength
		  *@access protected
		  *@var numeric
		*/
		protected $inputLenght = 0;
		/**
		  * the current position
		  *@name inputIndex
		  *@access protected
		  *@var numeric
		*/
		protected $inputIndex = 0;
		/**
		  * the minfied version
		  *@name output
		  *@access public
		  *@var string
		*/
		public $output = "";
		/**
		 * this array contains the data for the obfuscator
		 *@name dataarray1
		 *@access public
		 *@var string
		*/
		public static $dataarray1 = array(
				" :", " {", " }", " ;", " '", " \"", " ,", "  ", ";;"
		);
		/**
		 * this array contains the data for the obfuscator
		 *@name dataarray2
		 *@access public
		 *@var string
		*/
		public static $dataarray2 = array(
				": ", "{ ", "} ", "; ", "' ", "\" ", ", ", "  "
		);
		/**
		 * minfies css-code
		 *@name minify
		 *@param string - css
		 *@return new code
		*/
		public static function minify($css)
		{
			$cssmin = new cssmin($css);
			$cssmin->min();
			return $cssmin->output;
		}
		/**
		 *@name __construct
		 *@param string - css-code
		*/
		public function __construct($input)
		{
				$this->input = $input;
				$this->input = str_replace(array("\r\n", "\r", "\n", "	"), " ", $this->input);
				$this->input = preg_replace("/\/\*(.*)\*\//Usi", "", $this->input); // comments
				$this->inputLenght = strlen($this->input);
		}
		/**
		 * minfied the css-code
		 *@name min
		 *@access public
		 *@return string - minfied version
		*/
		public function min()
		{
				
				if(PROFILE) Profiler::mark("cssmin::min");
				$this->input = str_replace("\t", " ", $this->input);
				while($this->inputIndex < $this->inputLenght)
				{
						$this->a = isset($this->input{$this->inputIndex - 1}) ? $this->input{$this->inputIndex - 1} : null;
						$this->b = $this->input{$this->inputIndex};
						$this->c = isset($this->input{$this->inputIndex + 1}) ? $this->input{$this->inputIndex + 1} : null;
						
						if(!in_array($this->b . $this->c, self::$dataarray1) && !in_array($this->a . $this->b, self::$dataarray2))
						{
								$this->output .= $this->b;
						}
						$this->inputIndex++;
				}
				if(PROFILE) Profiler::unmark("cssmin::min");
				
				
				$this->output = str_replace(";}", "}", $this->output);
				$this->output = str_replace(" 0px", " 0", $this->output);
				return $this->output;
		}
}