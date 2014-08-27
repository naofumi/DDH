<?php
// The QueriedDataSourceBase class allows us to set a $query targeted towards 
// a CSV file specified in $query_target. The $query is used to filter the ids 
// after which additional data sources will be joined using the base DataSource
// class functionality.
//
// This class does not implement the actual filtering based on the $query. Subclass this 
// and implement `get_csv_rows_for_query`, `confirm_assoc_list_matches_query` and
// if necessary, `sort_callback` to actually use this class.
class QueriedDataSourceBase extends DataSource {
  protected $query;
  protected $query_target;
  protected $partial_match_fields;


  // We use the $id property of the superclass, but this
  // is actually the query.
  // $query_target is the identifier for the data source against which this query
  // will be tested.
  // Note that the $query is assumed to be encoded in UTF-8. To ensure that this is
  // the case, URL queries must be in UTF-8 and forms must have the accept-charset attribute
  // set to "UTF-8".
  function __construct($source_parameters, $query = array(), $query_target) {
    parent::__construct($source_parameters);
    $this->query = $query;
    $this->query_target = $query_target;
    $this->query = $this->clean_query();
    $this->partial_match_fields = array();
  }

  public function set_partial_match_fields($partial_match_fields) {
    log_var_dump($partial_match_fields);
    $query = $this->query;
    foreach($partial_match_fields as $field_name) {
      if (isset($query[$field_name])) {
        $keywords = preg_split("/[\pZ\pC\pM\pP]/", $query[$field_name]);
        usort(&$keywords, array($this, "compare_string_length"));
        var_dump($keywords);
        $this->partial_match_fields[$field_name] = $keywords;        
      }
    }
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
    $source_attr = $this->source_parameters[$this->query_target];
    $path = $this->path_for_source($this->query_target);

    $csv_rows = $this->get_csv_rows_for_query($path, $source_attr['encoding']);
    $result = array();
    foreach ($csv_rows as $row) {
      $assoc_list = $this->convert_row_to_assoc_list($row, $source_attr['fields']);
      // We double check because get_csv_rows_for_query is not optimized for accuracy
      if (!$this->confirm_assoc_list_matches_query($assoc_list))
        continue;
      $result[$row[0]] = $assoc_list;
    }
    return $result;
  }

  // Read the CSV file and collect all
  // rows that match the egrep regex for the $ids.
  //
  // For performance, we only check for the presence of the query values
  // and we don't check for exact matches.
  // We have to do a double check, which is easier after the
  // assoc_list is generated.
  protected function get_csv_rows_for_query($source, $encoding){
    die('Must implement get_rows_for_query in subclass');
  }

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
    $fields = $this->source_parameters[$this->query_target]['fields'];
    foreach ($fields as $field) {
      if (isset($this->query[$field])) {
        $trimmed_query = $this->trim($this->query[$field]);
        if ($trimmed_query !== "") {
          $result[$field] = $this->query[$field];
        }
      }
    }
    return $result;    
  }

}