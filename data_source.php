<?php
require_once(__DIR__.'/data_row.php');

class DataSourceBase {
	public $source_parameters;
	public $preview_directory;
	public $current_directory;
	public $previous_directory;
	protected $data;
	protected $ids;
	protected $rowspanable;

	function __construct($source_parameters, $ids = array()) {
		$this->source_parameters = $source_parameters;
		$this->preview_directory = dirname(__FILE__).'/../data/preview/';
		$this->current_directory = dirname(__FILE__).'/../data/current/';
		$this->previous_directory = dirname(__FILE__).'/../data/previous/';
		$this->ids = $ids;
	}

	// Return a hash for the data source. 
	// The ids are searched in all sources and joined together.
	// Keys are the $ids, and the values are DataRow objects.
	protected function retrieve_data() {
		if (!isset($this->data)){
			$all = array();
			foreach($this->source_parameters as $source_id => $source_attr) {
				$path = $this->path_for_source($source_id);

				$assoc_list = $this->get_assoc_list_for_ids($this->ids, $path, $source_attr['fields'], $source_attr['encoding']);
				$this->update_data_from_assoc_list($assoc_list);
			}			
			$this->data = $all;
		}
	}

	protected function path_for_source($source_id) {
	  if (is_preview()) {
	    $directory = $this->preview_directory;
	  } else {
	    $directory = $this->current_directory;
	  }
	  return $directory.$this->source_parameters[$source_id]['filename'];
	}

	protected function update_data_from_assoc_list($assoc_list) {
	  foreach($assoc_list as $id => $values) {
	    if (!isset($this->data[$id]))
	      $this->data[$id] = new DataRow();
	    foreach($values as $field => $value) {
	      $this->data[$id]->set($field, $value);
	    }
	  }    
	}



	public function ids(){
		$this->retrieve_data();
		return array_keys($this->data);
	}

	public function rows(){
		$this->retrieve_data();
		return array_values($this->data);
	}

	public function row($id){
		$this->retrieve_data();
		return $this->data[$id];
	}

	// Gets the update_at for the 
	// CSV files, and returns the most
	// recent one. Useful for generating
	// cache keys.
	public function last_updated_at(){
		$last_updated_at = 0;
		foreach($this->source_parameters as $key => $value) {
			if (is_preview()) {
				$path = $this->preview_directory.$value['filename'];
			} else {
				$path = $this->current_directory.$value['filename'];
			}
			$fmt = filemtime($path);
			if ($fmt > $last_updated_at) {
				$last_updated_at = $fmt;
			}
		}
		return $last_updated_at;
	}

	public function rowspanable() {
		if (isset($this->rowspanable)) {
			return $this->rowspanable;			
		} else {
			$result = array();
			foreach($this->source_parameters as $key => $value) {
				if (isset($value['rowspanable'])) {
					$result = array_merge($result, $value['rowspanable']);
				}
			}
			return $this->rowspanable = $result;			
		}
	}

	// Add $field."_rowspan" keys to $data_source to allow
	// the table to be displayed using rowspans for 
	// repetitive cells. If $field."_rowspan" is "-1"
	// then that <td> will not be drawn. Otherwise,
	// the <td> will have a "colspan" of $field."_rowspan".
	//
	// This compares a field value to that which directly
	// precedes it, and if they are identical, then it
	// tags it for a rowspan.
	//
	// Only the fields which are included in "rowspanable" in the
	// source_parameters will be rowspaned.
	public function add_rowspans() {
	  $previous_row = array();
	  $previous_id = null;
	  // Hash that stores the id of the span start row.
	  // $span_start_id[field_name] contains the row id.
	  $span_start_id = array(); 
	  foreach($this->ids() as $id) {
	    $row = $this->row($id);
	    foreach($row->fields() as $field) {
	    	if (!in_array($field, $this->rowspanable()))
	    		continue;
	      if ($previous_row &&
	          $previous_row->get($field) == $row->get($field)) {
	        // If this is the second row (the first time we need to set span)
	        if (!isset($span_start_id[$field]) || !$span_start_id[$field]){
	          $span_start_id[$field] = $previous_id;
	          $this->row($span_start_id[$field])->set($field."_rowspan", 1);
	        }
	        $this->row($span_start_id[$field])->increment($field."_rowspan");
	        // Setting $field."_rowspan" to -1 tells the view helper that the <td>
	        // for this cell should not be drawn.
	        $this->row($id)->set($field."_rowspan", -1);
	      } else {
	        $span_start_id[$field] = null;
	      }
	    }
	    $previous_row = $row;
	    $previous_id = $id;
	  }
	  return $this;
	}

	/////////////////////////////////////////////////
	/// Functions to retrieve data from the CSV files
	/////////////////////////////////////////////////
	//
	// TODO: Writing the $source each time is a pain.
	//       We should write once only in the config file.
	//
	protected function convert_row_to_assoc_list($row, $field_names) {
		$result = array();
		for ($i = 0; $i < count($field_names); $i++) {
			$value = isset($row[$i]) ? $row[$i] : null;
			$result[$field_names[$i]] = $value;
		}
		// return array_combine($field_names, array_slice($row, 0, count($field_names)));
		return $result;
	}

	// Read the CSV file and collect all
	// rows that match the egrep regex for the $ids.
	//
	// The ID must always be the left-most row. Otherwise,
	// the regex will become complex and reduce performance.
	//
	// Matching is not strict for performance reasons
	// but you can reanalyze the results if more
	// accuracy is needed.
	protected function get_rows_for_ids($ids, $source, $encoding){
		if (!$ids)
			return array();
	  $result = array();
	  $regexp = implode("|", $ids);
	  $lines = array();
	  // Quickly filter the file with 'egrep'.
	  // Careful optimization of the regex is important!
	  exec("egrep '^\"?(?:$regexp)' $source", $lines);
	  foreach ($lines as $line) {
			$row = str_getcsv($line);
			$row = $this->row_convert_encoding($row, $encoding);
			$result[$row[0]] = $row;
	  }
	  return $result;
	}

	// Get data as an associated list from a single source
	protected function get_assoc_list_for_ids($ids, $source, $field_names, $encoding) {
		$rows = $this->get_rows_for_ids($ids, $source, $encoding);
		$result = array();
		foreach ($rows as $row) {
			$result[$row[0]] = $this->convert_row_to_assoc_list($row, $field_names);
		}
		return $result;
	}

	// Convert each cell in the row from $encoding to 'UTF-8'
	protected function row_convert_encoding($row, $encoding) {
		for($i = 0; $i < count($row); $i++) {
			$row[$i] = mb_convert_encoding($row[$i], 'UTF-8', $encoding);
		}
		return $row;
	}


	protected function augment_data_source($value) {
		die('override the augment_data_source method in config.php');
	}
}