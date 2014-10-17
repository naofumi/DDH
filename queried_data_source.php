<?php
require_once(dirname(__FILE__).'/queried_data_source_base.php');

// QueriedDataSource implement a DataSource that allows us to 
// filter on the query target using a query that has the following syntax;
//
// array('field_name' => 'query_keyword', 'field_name' => 'query_keyword', ...)
//
// The filter will be an AND on each key=>value set.
// `value` must exactly match query (except for case: this class is case insensitive).
//
// To allow more flexible and powerful matching, you can set
// the $query_expanders global in `config.php`. This will allow
// you to use partial matches for the egrep phase and
// regular expressions for the `confirm_assoc_list_matches_query`
// phase. See the comments on `config.php` for details
class QueriedDataSource extends QueriedDataSourceBase {

  // Get the expanded query from the `$query_expanders`
  // global set in `config.php`.
  protected function get_expanded_query_in_key($key) {
    $query_expanders = $GLOBALS["query_expanders"];
    $key = mb_strtolower($key);
    $query = mb_strtolower($this->query[$key]);

    if (array_key_exists($key, $query_expanders) && 
        ($expanded_query = $this->array_value_for_key_case_insensitive($query_expanders[$key], $query)) &&
        $expanded_query[0]) {
        return $expanded_query;
    } else {
        return array($query, $query);
    }
  }

  private function array_value_for_key_case_insensitive($array, $key) {
    $lower_key = mb_strtolower($key);
    foreach ($array as $k => $v) {
      if (mb_strtolower($k) == $lower_key) {
        return $v;
      }
    }
    return null;
  }

  // The facets function in the parent DataSource does not take expanded_queries
  // into consideration. Here we add facet information for the
  // expanded queries.
  public function retrieve_facets() {
    $facets = parent::retrieve_facets();

    $fields = $this->facet_fields;
    $query_expanders = $GLOBALS["query_expanders"];
    foreach ($fields as $field_name) {
      if (!isset($facets[$field_name])) {continue;};

      if (isset($query_expanders[$field_name])) {
        foreach ($query_expanders[$field_name] as $param => $extended_query) {
          $total_count = 0;
          foreach ($facets[$field_name] as $value => $count) {
            if (preg_match($extended_query[1], mb_strtolower($value))) {
              $total_count = $total_count + $count;
            }
          }
          if ($total_count > 0) {
            $facets[$field_name][$param] = $total_count;            
          }
        }
      }
    }
    $this->facets = $facets;
    return $this->facets;
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

  // Read the CSV file and collect all
  // rows that match the egrep regex. The rows
  // are sent to the $callback for further processing.
  //
  // For performance, we only check for the presence of the query values
  // and we don't check for exact matches.
  // We normally do a double check in the callback.
  protected function each_csv_row_for_query($source, $encoding, $delimiter, $callback){

    if (!$this->query)
      return array();
    $result = array();

    // Prepare the regex for grep matching
    $partial_match_fields = $this->partial_match_fields;
    $partial_match_field_names = array_keys($partial_match_fields);
    $combo_field_names = array_keys($this->combo_fields);
    $regexp = "";

    foreach(array_keys($this->query) as $key) {
        if (in_array($key, $partial_match_field_names) ||
            in_array($key, $combo_field_names)) {
            $regexp = $this->partial_match_regex($partial_match_fields[$key]);
        } else {
            $expanded_query = $this->get_expanded_query_in_key($key);
            $regexp .= ".*".preg_quote($expanded_query[0]);            
        }
    }
    $this->get_lines_with_gnugrep($regexp, $source, $encoding, function($line) use ($callback, $delimiter){
        $row = str_getcsv($line, $delimiter);

        // The implementation of `each_csv_row_for_query()` must 
        // observe the return value of the callback, and exit from
        // the loop if it returns false. This allows us to limit
        // the number of results to return.
        // To break out of the loop, we return false.
        if ($callback($row) === false) {
            return false;
        }
    });
  }

  protected function confirm_assoc_list_matches_query($assoc_list){
   foreach($this->query as $field => $value) {
        if (!$this->confirm_assoc_list_matches_query_for_field($assoc_list, $field)){
            return false;
        }
    }
    // If all fields matched
    return true;
  }

  protected function confirm_assoc_list_matches_query_for_field($assoc_list, $field) {
    $partial_match_regexes = array();
    $partial_match_fields = $this->partial_match_fields;
    $partial_match_field_names = array_keys($partial_match_fields);
    $regexp = "";

    if (isset($this->combo_fields[$field])) {
        // If this is a combo field, we simply join the
        // sub_fields together to create a single value to 
        // match against.
        $field_value = "";
        foreach($this->combo_fields[$field] as $sub_field) {
            $field_value .= " ".$assoc_list[$sub_field];
        }
    } else {
        $field_value = $assoc_list[$field];
    }

    if (in_array($field, $partial_match_field_names)) {
        $regexp = $this->partial_match_regex($partial_match_fields[$field]);
        if (!preg_match("/$regexp/i", $field_value)) {
            return false;
        }
    } else {
        $expanded_query = $this->get_expanded_query_in_key($field);
        if (preg_match("/^\/.*\/$/", $expanded_query[1])) {
            if (!preg_match($expanded_query[1]."i", mb_strtolower($field_value))) {
                // If the query is a regular expression
                // and a field failed to match
                return false;
            }
        } else if (mb_strtolower($field_value) != mb_strtolower($expanded_query[1])) {
            // If the query is not a regular expression
            // and a field failed to match
            return false;
        }            
    }
    return true;
  }
}