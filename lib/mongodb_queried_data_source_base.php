<?php
require_once(dirname(__FILE__).'/mongodb_data_source.php');
// The MongoDBQueriedDataSourceBase class allows us to set a $query targeted towards 
// a $source_id specified in $query_target. The $query is used to generate a 
// list of $ids from which rows will be compiled from all the data sources 
// through the base MongoDBDataSource class functionality.
//
// This class does not implement the `mongodb_query()` method which
// generates the parameters to send to MongoDB.
// To actually use this DataSource, subclass this and implement `mongodb_query()` method
//
// To allow more flexible and powerful matching, set the possible query values
// in `field_values.php`. You can use regular expressions and 
// numeric range matching.
//
// ## Usage (of subclasses)
//
// $data_source = new MongoDBAntibodyQueryDataSource($source_parameters, $_GET, 'jackson_second', preview_version());
//
// The query is usually taken directly from the GET parameters.
// The query is translated into a mongoDB query in the following way.
//
// 1. If a field is specified as a combo_field, then we do a 
//    partial match against each subfield and do an OR of the results.
// 2. If a field is specified as a partial_match_field, then
//    we do a partial match against the field.
// 3. Othewise, we modify the query for the field according to
//    the `field_values.php`. We then determine if the query
//    specifies a regular expression, a numeric comparison,
//    or a regular string. We construct the query accordingly.
// 4. The final mongoDB query is an AND of each field.
//
// This means that we can perform the following types of queries are
// supported. This should cover most cases.
// 1. Full text matches and tokenised partial text matches 
//    (case-insensitive) are very simple to do.
// 2. Partial text matches (tokenised) against multiple fields 
//    are supported (OR logic).
// 3. Regular expression matches against single fields are supported.
// 4. Numeric comparisons against numeric fields ("numeric_fields"
//    in $source_parameters) are supported.
//
// The following are methods to configure the QueryDataSource object.
//
//  * Specify combo_fields for OR behaviour
//  $data_source->set_combo_fields(array("combo_name" => array("name", "alternative_name", "cat_no", "reactivity")));
//
//  * Specify partial match fields for partial match behaviour
//  $data_source->set_partial_match_fields(array("name", "reactivity"));
//
//  * Specify facet fields to enable drill down behaviour
// $data_source->set_facet_fields(array('reactivity', 'label', 'host', 'form', 'target', 'kyushu', 'multi_label', 'for_flow_cytometry', 'for_eikyu_funyu', 'for_fluorecent_wb', 'price'));
//
//  * Set the maximum number of results to retrieve
//  $data_source->set_maximum_results(1000);
//
//  * Set sort order.
//    Sort order of the results. Previously we had to subclass
//    MongoDBQueriedDataSourceBase and override `sort_callback()` 
//    to supply the sorting callback function. We can now also
//    assign a lambda through the `set_sort_callback()` function.
//  $data_source->set_sort_callback(function() {[callback function code]})
abstract class MongoDBQueriedDataSourceBase extends MongoDBDataSource {
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
  //
  // $snapshot_version is used when we want to get a version other than 'current'
  // for preview purposes.
  // Usually, you would use `preview_version()` to get the version in
  // the controller (jsonp.php).
  function __construct($source_parameters, $query = array(), $query_target, $snapshot_version) {
    parent::__construct($source_parameters, $snapshot_version);
    $this->raw_query = $query;
    $this->query_target = $query_target;
    $this->combo_fields = array();
    $this->clean_query();
    $this->partial_match_fields = array();
    $this->partial_match_field_names = array();
    $this->maximum_results = false;
    $this->over_limit = false;
  }

  ///////////////////////////////////////////
  // Modifying the query for better matching
  ///////////////////////////////////////////

  // partial_match_fields are the the fields that use partial matching.
  public function set_partial_match_fields($partial_match_field_names) {
    $this->partial_match_field_names = $partial_match_field_names;
    $this->recalculate_partial_match_fields();
  }

  // Partial match query require some modification before
  // use. Here, we split up the query string into tokens
  // and sort the tokens into length of string (could help query speed)
  protected function recalculate_partial_match_fields() {
    $result = array();
    $query = $this->clean_query();
    // Combo fields are always treated as partial match
    $all_partial_field_names = array_merge($this->partial_match_field_names, 
                                           array_keys($this->combo_fields));

    foreach($all_partial_field_names as $field_name) {
      if (isset($query[$field_name])) {
        // The "/[\pZ\pC\pM\pP]/" regex is supposed to cover non-letter
        // Unicode.
        $keywords = preg_split("/[\pZ\pC\pM\pP]/u", $query[$field_name]);
        usort($keywords, array($this, "compare_string_length"));
        $result[$field_name] = $keywords;        
      }
    }
    $this->partial_match_fields = $result;
    return $result;
  }

  // Public accessor function to provide access to the
  // cleaned up query.
  public function query() {
    return $this->query;
  }

  // Public accessor function to check if the query (after clean up)
  // is empty
  public function is_query_empty() {
    return (count($this->query()) == 0);
  }

  // Takes an array of tokens and generates
  // a regular expression string that will
  // to an AND match.
  protected function partial_match_regex($tokens) {
    $regexp = "";
    foreach($tokens as $token) {
        if (strlen($token) > 0) {
            $regexp .= "(?=.*".preg_quote($token).")";
        }
    }
    return $regexp;
  }


  // Get the modified query from the value set in
  // field_values.php
  protected function get_modified_query_in_key($key) {
    $key = strtolower($key);
    $query = $this->query[$key];

    if (settings_for_field($key)) {
      if (array_key_exists($query, settings_for_field($key))) {
        if (settings_for_field($key)[$query] === null) {
          return [$query, $query];
        } else {
          return [$query, settings_for_field($key)[$query]];
        }
      } else {
        die("Value for $query in field $key not set in field_values.php");
      }
    } else {
      return [$query, $query];
    }
  }

  private function array_value_for_key_case_insensitive($array, $key) {
    $lower_key = strtolower($key);
    foreach ($array as $k => $v) {
      if (strtolower($k) == $lower_key) {
        return $v;
      }
    }
    return null;
  }

  // Set the combo_fields property after reordering them
  // according to the definition in `config.php` ($this->source_paramters)
  public function set_combo_fields($combo_fields) {
    $fields = $this->source_parameters[$this->query_target]['fields'];
    foreach($combo_fields as $combo_field_name => $sub_fields) {
      usort($sub_fields, function($a, $b) use ($fields) {
        cmp_in_array($a, $b, $fields);
      });
    }
    $this->combo_fields = $combo_fields;
    $this->clean_query();
    $this->recalculate_partial_match_fields();
  }

  private function compare_string_length($a, $b) {
    $a_len = strlen($a);
    $b_len = strlen($b);
    if ($a_len == $b_len) {
      return 0;
    }
    return ($a_len < $b_len ? 1 : -1);    
  }

  // Retrieve all data
  // Do update_by_query() to get all the ids and then join from all other data sources.
  // Cached in $this->data
  protected function retrieve_data() {
    if (!isset($this->data)){
      $start_time = microtime(TRUE);
      $this->data = array();

      // First get the data for the query_target and set $this->ids
      $this->update_by_query();
      $end_time = microtime(TRUE);
      error_log("BENCHMARK update_by_query(do the query on query target): ".($end_time - $start_time));

      // Join the other data sources
      $join_other_start_time = microtime(TRUE);
      foreach($this->source_parameters as $source_id => $source_attr) {
        $data_source_start_time = microtime(TRUE);
        if ($source_id == $this->query_target)
          continue;
        $this->update_from_source_id($source_id);
        $data_source_end_time = microtime(TRUE);
        error_log("BENCHTIME join data source $source_id: ".($data_source_end_time - $data_source_start_time));
      }
      $end_time = microtime(TRUE);
      error_log("BENCHTIME update_from_source_id(join allother data sources): ".($end_time - $join_other_start_time));
    }
  }

  // This is where the query actually happens.
  // TODO: We could significanly reduce memory usage by integrating the
  //       get_assoc_list_for_query() function and the update_data_with_assoc_list() function.
  //       These functions pass data via $assoc_list which should not be necessary.
  protected function update_by_query() {

    $assoc_list = $this->get_assoc_list_for_query();

    $this->update_data_with_assoc_list($assoc_list);
    $this->ids = array_keys($assoc_list);
  }

  // Get data as an associated list from a single source.
  protected function get_assoc_list_for_query() {
    // $path = $this->path_for_source($this->query_target);
    $source_id = $this->query_target;
    // $source_attr = $this->source_parameters[$source_id];
    // $delimiter = $this->delimiter($source_id);
    $id_field = $this->source_parameters[$source_id]['id_field'];
    $result = array();
    $line_count = 1;
    $this->each_assoc_list_for_query($source_id, function ($assoc_list) use (&$result, &$line_count, $id_field) {
      if ($this->maximum_results && ($line_count > $this->maximum_results)) {
          $this->over_limit = true;
          return false; // Sends a signal to the caller loop to break
      }
      $result[$assoc_list[$id_field]] = $assoc_list;
      $line_count++;
    });
    return $result;
  }

  // We generate the query parameters for the MongoDB query
  // send the request to MongoDB and iterate through the results.
  protected function each_assoc_list_for_query($source_id, $callback){
    if (!$this->query) {
      return array();
    }
    $result = array();

    $snapshot = $this->snapshot();
    $source_updated_at = $snapshot['sources'][$source_id];

    $mongodb_query = $this->mongodb_query();
    array_push($mongodb_query['$and'],
               ['updated_at' => $source_updated_at]);

    error_log("mongodb_query: ");
    log_var_dump($mongodb_query);

    $result = array();
    $count = $this->db->$source_id->count($mongodb_query);
    $cursor = $this->db->$source_id->find($mongodb_query);
    $id_field = $this->source_parameters[$source_id]['id_field'];
    foreach ($cursor as $id => $value) {
      $row = $value['row'];
      $callback($row);
      $result[$row[$id_field]] = $row;
    }
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

  // Returns all the values for the $field in the
  // current query_target together with count.
  //
  // Use this to get select_tag options when no
  // results are returned.
  public function all_values_in_field($field) {
    return $this->all_values_in_field_of_source($field, $this->query_target);
  }

  public function all_values_in_field_sorted($field) {
    return $this->all_values_in_field_of_source_sorted($field, $this->query_target);
  }
}