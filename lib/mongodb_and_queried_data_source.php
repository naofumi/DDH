<?php
require_once(dirname(__FILE__).'/mongodb_queried_data_source_base.php');

// MongoDBAndQueriedDataSource implements a DataSource that
// takes a query of the following format and does an AND for each 
// field.
//
// ['field_name' => 'query_keyword', 'field_name' => 'query_keyword', ...]
class MongoDBAndQueriedDataSource extends MongoDBQueriedDataSourceBase {
 
  // mongodb_query() analyzed $this->query and 
  // converts it into a mongodb query format using $this->partial_match_fields
  // $this->combo_fields, and expanded_queries.
  protected function mongodb_query() {
    $mongodb_query = array();
    $partial_match_fields = $this->partial_match_fields;
    $partial_match_field_names = array_keys($partial_match_fields);
    $combo_field_names = array_keys($this->combo_fields);
    
    foreach(array_keys($this->query) as $key) {
      // Combo fields are handled as OR queries on 
      // each field. We use partial_match_regex
      // instead of complete match.
      if (isset($this->combo_fields[$key])) {
        $or_queries = array();
        foreach($this->combo_fields[$key] as $sub_field) {
          array_push($or_queries, 
                    ["row.$sub_field" => 
                      ['$regex' => new MongoDB\BSON\Regex($this->partial_match_regex($partial_match_fields[$key]), 'i')]
                    ]);
        };
        $mongodb_query['$or'] = $or_queries;
      } else if (in_array($key, $partial_match_field_names)) {
        // Partial match simply uses the partial match regex
        $mongodb_query["row.$key"] = 
                     ['$regex' => new MongoDB\BSON\Regex($this->partial_match_regex($partial_match_fields[$key]), 'i')];
      } else {
        $expanded_query = $this->get_modified_query_in_key($key);
        if (preg_match("/^\/(.*)\/$/", $expanded_query[1], $matches)) {
          // If the $expanded_query specifies a regex, then 
          // we simpy apply that regex to the search.
          $mongodb_query["row.$key"] = ['$regex' => new MongoDB\BSON\Regex($matches[1], "i")];
        } else if (preg_match("/^ddhq:(.*)/", $expanded_query[1], $matches)) {
          // If the $expanded_query specifies a ddhq: style query, then 
          // decode this and add it to the search.
          $ddhq = $this->decode_ddhq($matches[1]);
          $mongodb_query["row.$key"] = $ddhq;
        } else {
          // If the $expanded_query is not a regex (a string),
          // then we do case-insensitve complete matching.
          $mongodb_query["row.$key"] = ['$regex' => new MongoDB\BSON\Regex("^".preg_quote($expanded_query[1])."$", "i")];
        }            
      }
    }
    return $mongodb_query;    
  }

  function decode_ddhq($ddhq_string) {
    $result = array();
    $tuples = explode(",", $ddhq_string);
    foreach ($tuples as $tuple) {
      $tuple_array = explode(":", $tuple);
      $result["$".$tuple_array[0]] = $tuple_array[1] + 0.0;
    }
    return $result;
  }
}