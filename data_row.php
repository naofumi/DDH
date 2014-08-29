<?php

class DataRowBase {
	protected $row;

	public function __construct() {
		$this->row = array();
	}

	public function set($field, $value) {
		return $this->row[$field] = $value;
	}

	public function get($field) {
		if (method_exists($this, $field)) {
			return call_user_func(array($this, $field));
		} else {
			if (isset($this->row[$field])) {
				return $this->row[$field];
			} else {
				return null;
			}
		}
	}

	public function increment($field) {
		return $this->row[$field]++;
	}

	public function fields() {
		return array_keys($this->row);
	}

	// Tells us if this product has a currently running campaign
	public function is_campaign() {
		if (isset($this->row['starts_at']) && $this->row['starts_at'] &&
		    isset($this->row['ends_at']) && isset($this->row['ends_at'])) {
		  return strtotime($this->row['starts_at']) <= time() && 
				      strtotime($this->row['ends_at']) >= time();
		} else if (isset($this->row['campaign_price']) && $this->row['campaign_price']) {
			return true;
		}
	}

	// This function generates a <td> tag from the $field in $row
	// with the rowspan generated from $row[$field."_rowspan"] value.
	// Additional attributes such as `class` and `style` can be
	// set using the $attributes argument.
	//
	// If the $field is not a native field in the $source_parameters,
	// then it won't have a $row[$field."_rowspan"] value. In these
	// cases, provide a native field in the $field_for_rowspan argument. This field will
	// be used to get the $row[$field."_rowspan"] value.
	function td($field, $attributes = array(), $field_for_rowspan = null) {
		if (!$field_for_rowspan) {
			$field_for_rowspan = $field;
		}
	  $result = "";
	  $attribute_string = "";
	  foreach($attributes as $name => $value) {
	    $attribute_string = $attribute_string." $name=\"$value\"";
	  }
	  if ($this->get($field_for_rowspan."_rowspan") && $this->get($field_for_rowspan."_rowspan") > 1) {
	    $attribute_string = $attribute_string." rowspan=".$this->get($field_for_rowspan."_rowspan");
	  }
	  if (!$this->get($field_for_rowspan."_rowspan") || $this->get($field_for_rowspan."_rowspan") != -1) {
	    $result = "<td$attribute_string>";
	    $result = $result.$this->get($field);
	    $result = $result."</td>\n";      
	  }
	  return $result;
	}

	function th($field, $attributes = array(), $field_for_rowspan = null) {
		return $this->table_cell('th', $field, $attributes, $field_for_rowspan);
	}

	function table_cell($tag_name, $field, $attributes = array(), $field_for_rowspan = null) {
		if (!$field_for_rowspan) {
			$field_for_rowspan = $field;
		}
	  $result = "";
	  $attribute_string = "";
	  foreach($attributes as $name => $value) {
	    $attribute_string = $attribute_string." $name=\"$value\"";
	  }
	  if ($this->get($field_for_rowspan."_rowspan") && $this->get($field_for_rowspan."_rowspan") > 1) {
	    $attribute_string = $attribute_string." rowspan=".$this->get($field_for_rowspan."_rowspan");
	  }
	  if (!$this->get($field_for_rowspan."_rowspan") || $this->get($field_for_rowspan."_rowspan") != -1) {
	    $result = "<$tag_name$attribute_string>";
	    $result = $result.$this->get($field);
	    $result = $result."</$tag_name>\n";      
	  }
	  return $result;
	}

}
