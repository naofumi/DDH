<?php
// It is probably a good idea to further subclass this based on the
// types of query we want to do (AND, OR, customized, etc.)
class QueriedDataSourceBase extends DataSource {
  protected $query;
  protected $query_target;
  protected $query_encoding;
  protected $utf8_encoded_query;

  // We use the $id property of the superclass, but this
  // is actually the query.
  // $query_target is the identifier for the data source against which this query
  // will be tested.
  // $query_encoding is the encoding of the query parameters, which is the same
  // as the encoding of the page on which the link was found (unless the link was preencoded).
  // Autodetection (default value) seems to work well.
  function __construct($source_parameters, $query = array(), $query_target, $query_encoding = array('SJIS', 'UTF-8', 'EUC-JP')) {
    parent::__construct($source_parameters);
    $this->query = $query;
    $this->query_target = $query_target;
    $this->query = $this->clean_query();
    $this->query_encoding = $query_encoding;
  }

  protected function retrieve_data() {
    if (!isset($this->data)){
      $this->data = array();

      // First get the data for the query_target
      $source_attr = $this->source_parameters[$this->query_target];
      $path = $this->path_for_source($this->query_target);
      $assoc_list = $this->get_assoc_list_for_query($path, $source_attr['fields'], $source_attr['encoding']);
      $this->update_data_from_assoc_list($assoc_list);
      $ids = array_keys($this->data);

      // Join the other data sources
      foreach($this->source_parameters as $source_id => $source_attr) {
        if ($source_id == $this->query_target)
          continue;

        $path = $this->path_for_source($source_id);
        $assoc_list = $this->get_assoc_list_for_ids($ids, $path, $source_attr['fields'], $source_attr['encoding']);
        $this->update_data_from_assoc_list($assoc_list);
      }
      uasort($this->data, array($this, "sort_callback"));
      // error_log('SHIT');
      // error_log($this->cmp_in_array("AP", "Biotin",
      //                          array("-", "AP", "Biotin", "Alexa 594")));
    }
  }

  protected function sort_callback($a, $b) {
    // return $this->strcasecmp_norm($a->get('label'), $b->get('label'));
    return 100 * $this->cmp_in_array($a->get('label'), $b->get('label'),
                               array("-", "AMCA", "Cy2", "DyLight 488", "Alexa 488", "FITC", "DyLight 549",
                                     "Cy3", "Rhodamine(TRITC)", "RRX", "Texas Red",
                                     "DyLight 594", "Alexa 594", "Alexa 647", "DyLight 649", "Cy5", 
                                     "Biotin", "HRP", "AP", "4nm Gold")) +
            10 * $this->strcasecmp_norm($a->get('label'), $b->get('label')) +
            1 * $this->strcasecmp_norm($a->get('host'), $b->get('host'));
  }

  protected function strcasecmp_norm($a, $b) {
    return $this->cmp_norm(strcasecmp($a, $b));
  }

  protected function cmp_in_array($a, $b, $array) {
    $a_pos = array_search($a, $array);
    $b_pos = array_search($b, $array);
    if ($a_pos === false)
      $a_pos = 9999;
    if ($b_pos === false)
      $b_pos = 9999;
    return $this->cmp_norm($a_pos - $b_pos);
  }

  protected function cmp_norm($cmp) {
    if ($cmp > 0) {
      return 1;
    } else if ($cmp < 0) {
      return -1;
    } else {
      return 0;
    }    
  }

  // Get data as an associated list from a single source
  protected function get_assoc_list_for_query($source, $field_names, $encoding) {
    $rows = $this->get_rows_for_query($source, $encoding);
    $result = array();
    foreach ($rows as $row) {
      $assoc_list = $this->convert_row_to_assoc_list($row, $field_names);
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
  protected function get_rows_for_query($source, $encoding){
    die('Must implement get_rows_for_query in subclass');
  }

  protected function confirm_assoc_list_matches_query($assoc_list){
    die('Must implement confirm_assoc_list_matches_query in subclass');
  }

  protected function utf8_encoded_query() {
    if (!isset($this->utf8_encoded_query)) {
      foreach(array_keys($this->query) as $key) {
        $this->utf8_encoded_query[$key] = mb_convert_encoding($this->query[$key], 'UTF-8', $this->query_encoding);
      }      
    }
    return $this->utf8_encoded_query;
  }

  // Reorder the $query hash by the order in the source_parameters.
  // Filter out any parameters not in the source_parameters
  protected function clean_query() {
    $result = array();
    $fields = $this->source_parameters[$this->query_target]['fields'];
    foreach ($fields as $field) {
      if (isset($this->query[$field])) {
        $result[$field] = $this->query[$field];
      }
    }
    return $result;    
  }

}