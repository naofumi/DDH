<?php
// The QueriedDataSourceBase class allows us to set a $query targeted towards 
// a CSV file specified in $query_target. The $query is used to filter the ids 
// after which additional data sources will be joined using the base DataSource
// class functionality.
//
// This class does not implement the actual filtering based on the $query. Subclass this 
// and implement `each_csv_row_for_query`, `confirm_assoc_list_matches_query` and
// if necessary, `sort_callback` to actually use this class.
class QueriedDataSourceBase extends DataSource {
  protected $query;
  protected $query_target;
  protected $partial_match_field_names;
  protected $partial_match_fields;
  protected $maximum_results;
  protected $combo_fields;
  protected $over_limit;


  // We use the $id property of the superclass, but this
  // is actually the query.
  // $query_target is the identifier for the data source against which this query
  // will be tested.
  // Note that the $query is assumed to be encoded in UTF-8. To ensure that this is
  // the case, URL queries must be in UTF-8 and forms must have the accept-charset attribute
  // set to "UTF-8".
  function __construct($source_parameters, $query = array(), $query_target) {
    parent::__construct($source_parameters);
    $this->raw_query = $query;
    $this->query_target = $query_target;
    $this->combo_fields = array();
    $this->clean_query();
    $this->partial_match_fields = array();
    $this->partial_match_field_names = array();
    $this->maximum_results = false;
    $this->over_limit = false;
  }

  public function set_partial_match_fields($partial_match_field_names) {
    $this->partial_match_field_names = $partial_match_field_names;
    $this->recalculate_partial_match_fields();
  }

  protected function recalculate_partial_match_fields() {
    $result = array();
    $query = $this->clean_query();
    // Combo fields are always treated as partial match
    $all_partial_field_names = array_merge($this->partial_match_field_names, 
                                           array_keys($this->combo_fields));

    foreach($all_partial_field_names as $field_name) {
      if (isset($query[$field_name])) {
        $keywords = preg_split("/[\pZ\pC\pM\pP]/", $query[$field_name]);
        usort($keywords, array($this, "compare_string_length"));
        $result[$field_name] = $keywords;        
      }
    }
    $this->partial_match_fields = $result;
    return $result;
  }

  // Set the combo_fields property after reordering them
  // according to the definition in `config.php` ($this->source_paramters)
  public function set_combo_fields($combo_fields) {
    $fields = $this->source_parameters[$this->query_target]['fields'];
    foreach($combo_fields as $combo_field_name => $sub_fields) {
      usort($sub_fields, function($a, $b) use ($fields) {
        $this->cmp_in_array($a, $b, $fields);
      });
    }
    $this->combo_fields = $combo_fields;
    $this->clean_query();
    $this->recalculate_partial_match_fields();
  }

  public function set_maximum_results($maximum_results) {
    $this->maximum_results = $maximum_results;
  }

  public function maximum_results() {
    return $this->maximum_results;
  }

  public function facets($fields) {
    if ($this->maximum_results_was_reached()) {
      // If $this->maximum_results_was_reached(),
      // then it's no use showing facets anyway.
      return array();
    } else {
      return parent::facets($fields);

    }
  }

  public function should_show_facets() {
    return $this->total_rows() && !$this->maximum_results_was_reached();
  }

  public function maximum_results_was_reached() {
    return $this->over_limit;
  }

  private function compare_string_length($a, $b) {
    $a_len = strlen($a);
    $b_len = strlen($b);
    if ($a_len == $b_len) {
      return 0;
    }
    return ($a_len < $b_len ? 1 : -1);    
  }

  protected function retrieve_data() {
    if (!isset($this->data)){
      $this->data = array();

      // First get the data for the query_target and set $this->ids
      $this->update_by_query();

      // Join the other data sources
      foreach($this->source_parameters as $source_id => $source_attr) {
        if ($source_id == $this->query_target)
          continue;
        $this->update_from_source_id($source_id);
      }
      if (method_exists($this, "sort_callback")) {
        uasort($this->data, array($this, "sort_callback"));
      }
    }
  }

  protected function update_by_query() {
    $assoc_list = $this->get_assoc_list_for_query();
    $this->update_data_from_assoc_list($assoc_list);
    $this->ids = array_keys($assoc_list);
  }

  // Get data as an associated list from a single source.
  protected function get_assoc_list_for_query() {
    $path = $this->path_for_source($this->query_target);
    $source_attr = $this->source_parameters[$this->query_target];
    $result = array();

    $line_count = 1;
    $this->each_csv_row_for_query($path, $source_attr['encoding'], function ($row) use (&$result, &$line_count) {
        if ($this->maximum_results && ($line_count > $this->maximum_results)) {
            $this->over_limit = true;
            return false; // Sends a signal to the caller loop to break
        }
        $assoc_list = $this->get_assoc_list_for_single_row($row);

        // We double check because each_csv_row_for_query is not optimized for accuracy
        if (!$this->confirm_assoc_list_matches_query($assoc_list)) {
        
        } else {
          $result[$row[0]] = $assoc_list;
          $line_count++;
        }
        return true;
    });

    return $result;
  }

  protected function get_assoc_list_for_single_row($row) {
    $source_attr = $this->source_parameters[$this->query_target];
    return $this->convert_row_to_assoc_list($row, $source_attr['fields']);    
  }

  // Read the CSV file and send all rows that match the grep regex.
  // The $row (the result from $str_getcsv()) is sent to the callback.
  //
  // The function `each_csv_row_for_query()` should call the callback with
  // the next $row value and iterate while the return value of the callback is true.
  // If the return value of the callback is false, then `each_csv_row_for_query()`
  // should terminate. This allows us to set an upper limit to the number
  // of results to return.
  //
  // For performance, we only check for the presence of the query values
  // and we don't check for exact matches.
  // Hence, we usually do a $this->confirm_assoc_list_matches_query() in the callback.
  protected function each_csv_row_for_query($source, $encoding, $callback){
    die('Must implement each_csv_row_for_query in subclass');
  }

  // This should return true if the current $row (as represented in the $assoc_list)
  // matches the query specified in $this->query. It should return either true
  // or false.
  protected function confirm_assoc_list_matches_query($assoc_list){
    die('Must implement confirm_assoc_list_matches_query in subclass');
  }

  // Clean the query.
  //
  // 1. Reorder the $query hash by the order in the source_parameters.
  // 2. Filter out any parameters not in the source_parameters.
  // 3. Remove blank parameters
  // 4. Strip whitespace from the beginning and end (including all UTF-8 space).
  //
  // This is important because of the way we use a Regex to 
  // go through the CSV file (the order of fields matters). 
  // We run this in the constructer
  // so that $this->query is "clean".
  protected function clean_query() {
    $result = array();
    $combo_fields = array_keys($this->combo_fields);
    $fields = $this->source_parameters[$this->query_target]['fields'];
    foreach (array_merge($combo_fields, $fields) as $field) {
      if (isset($this->raw_query[$field])) {
        $trimmed_query = $this->trim($this->raw_query[$field]);
        if ($trimmed_query !== "") {
          $result[$field] = $this->raw_query[$field];
        }
      }
    }
    $this->query = $result;
    return $result;
  }

}