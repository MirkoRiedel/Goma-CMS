<?php
/**
  * inspiration by Silverstripe 3.0 GridField
  *
  *@package goma framework
  *@link http://goma-cms.org
  *@license: http://www.gnu.org/licenses/gpl-3.0.html see 'license.txt'
  *@Copyright (C) 2009 - 2012  Goma-Team
  * last modified: 01.11.2012
  * $Version - 1.0
 */
 
defined('IN_GOMA') OR die('<!-- restricted access -->'); // silence is golden ;)

class TableFieldFilterHeader implements TableField_HTMLProvider, TableField_DataManipulator, TableField_ActionProvider {
	/**
	 * provides HTML-fragments
	 *
	 *@name provideFragments
	*/
	public function provideFragments($tableField) {
		$forTemplate = new ViewAccessableData();
		$fields = new DataSet();
		
		$state = $tableField->state->tableFieldFilterHeader;
		$filterArguments = $state->columns->toArray();
		$columns = $tableField->getColumns();
		$currentColumn = 0;
		
		foreach($columns as $columnField) {
			$currentColumn++;
			$metadata = $tableField->getColumnMetadata($columnField);
			$title = $metadata['title'];
			
			if($title && $tableField->getData()->canFilterBy($columnField)) {
				$value = '';
				if(isset($filterArguments[$columnField])) {
					$value = $filterArguments[$columnField];
				}
				$f = new TextField('filter['.$columnField.']', '', $value);
				$f->addExtraClass('tablefield-filter');
				$f->addExtraClass('no-change-track');

				$f->input->attr('placeholder', lang("form_tablefield.filterBy") . $title);
				
				if($currentColumn == count($columns)){
					$raction = new TableField_FormAction($tableField, "reset" . $columnField, lang("form_tablefield.reset"), "reset", null);
					$raction->addExtraClass("tablefield-button-reset");
					$raction->addExtraClass("no-change-track");
					
					$action = new TableField_FormAction($tableField, "filter" . $columnField, lang("search"), "filter", null);
					$action->addExtraClass("tablefield-button-filter");
					$action->addExtraClass("no-change-track");
					
					$field = new FieldSet($columnField . "_sortActions", array(
						$f,
						$raction,
						$action
					));
					$field->addExtraClass("sortActionsWithField");
				} else {
					$field = $f;
				}
			} else {
				if($currentColumn == count($columns)){
					$raction = new TableField_FormAction($tableField, "reset" . $columnField, lang("form_tablefield.reset"), "reset", null);
					$raction->addExtraClass("tablefield-button-reset");
					$raction->addExtraClass("no-change-track");
					
					$action = new TableField_FormAction($tableField, "filter" . $columnField, lang("search"), "filter", null);
					$action->addExtraClass("tablefield-button-filter");
					$action->addExtraClass("no-change-track");
					
					$field = new FieldSet($columnField . "_sortActions", array(
						$raction,
						$action
					));
				} else {
					$field = new HTMLField("", "");
				}
			}
			$field->setForm($tableField->Form());

			$fields->push(array("field" => $field->field(), "name" => $columnField, "title" => $title));
		}

		return array(
			'header' => $forTemplate->customise(array("fields" => $fields))->renderWith("form/tableField/filterHeader.html")
		);
	}
	
	/**
	 * manipulates the dataobjectset
	 *
	 *@name manipulate
	*/
	public function manipulate($tableField, $data) {
		$state = $tableField->state->tableFieldFilterHeader;
		if(!isset($state->columns)) {
			return $data;
		} 
		
		$filterArguments = $state->columns->toArray();
		foreach($filterArguments as $columnName => $value ) {
			if($data->canFilterBy($columnName) && $value) {
				$data->AddFilter(array($columnName => array("LIKE", $value)));
			}
		}
		return $data;
	}
	
	/**
	 * provide some actions of this tablefield
	 *
	 *@name getActions
	 *@access public
	*/
	public function getActions($tableField) {
		return array("filter", "reset");
	}
	
	public function handleAction($tableField, $actionName, $arguments, $data) {
		$state = $tableField->state->tableFieldFilterHeader;
		if($actionName === 'filter') {
			if(isset($data['filter'])){
				foreach($data['filter'] as $key => $filter ){
					$state->columns->$key = $filter;
				}
			}
		} elseif($actionName === 'reset') {
			$state->columns = null;
		}
	}
}