<?php
/////////////////////////////////////
//
// class DataRowBase
//
// This is an abstract class to represent a row of data in
// a DataSource class. We must subclass this class to 
// define a DataRow class in `config/data_row.php`. 
//
// In the subclass DataRow, we can define how each field
// is displayed.
//
// In the views, we access the data in each row by calling
// #get([symbol]). #get() will first check to see if there is
// a method that is the same as `symbol`. If it exists, then
// it will return the return value of that method. If a
// method does not exist, then it will return the value _as is_
// from the MongoDB row. This means that when we want to 
// customise the display of a certain row, we can simply 
// implement a method that returns the HTML for that row in
// the DataRow class.
//
// In the DataRowBase class, in addition to the #set() and #get()
// methods, we implement the following methods for convinience.
//
// **`is_campaign()`**
// This looks up the `starts_at`, `ends_at` and `campaign_price` fields
// and determines if the product is current undergoing a campaign.
// This is used to change the display of the price when a campaign
// is running.
//
// **`td() th() table_cell()`**
// These are used to generate <td> and <th> tags for HTML table
// display. The important feature of these methods is that
// they can generate `rowspan` attributes for an easier to read
// table format.
//
// This works in coordination with MongoDBDataSource#add_rowspan()
// and this method has to be called on the data_source for
// the rowspan feature to work.
//
//
// 
//
/////////////////////////////////////
abstract class DataRowBase {
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

	public function get_raw($field) {
		if (isset($this->row[$field])) {
			return $this->row[$field];
		} else {
			return null;
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
	// then it won't have a $row[$field."_rowspan"] value since
	// `MongoDBDataSource#add_rowspans()` does not take in consideration
	// the methods added in DataRow() and DataRowBase().
	// In these cases, provide a native field in the 
	// $field_for_rowspan argument. This field will
	// be used instead to obtain the $row[$field."_rowspan"] value.
	public function td($field, $attributes = array(), $field_for_rowspan = null) {
		return $this->table_cell('td', $field, $attributes, $field_for_rowspan);
	}

	public function th($field, $attributes = array(), $field_for_rowspan = null) {
		return $this->table_cell('th', $field, $attributes, $field_for_rowspan);
	}

	public function table_cell($tag_name, $field, $attributes = array(), $field_for_rowspan = null) {
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
