<?php
require_once(__DIR__.'/queried_data_source_base.php');

// QueriedDataSource implement a DataSource that allows us to 
// filter on the query target using a query that has the following syntax;
//
// array('field_name' => 'query_keyword', 'field_name' => 'query_keyword', ...)
//
// The filter will be an AND on each key=>value set.
// `value` must exactly match query.
class QueriedDataSource extends QueriedDataSourceBase {
  // Read the CSV file and collect all
  // rows that match the egrep regex for the $ids.
  //
  // For performance, we only check for the presence of the query values
  // and we don't check for exact matches.
  // We have to do a double check, which is easier after the
  // assoc_list is generated.
  protected function get_csv_rows_for_query($source, $encoding){
    if (!$this->query)
      return array();
    $result = array();

    foreach(array_keys($this->query) as $key) {
      // Convert encoding of query to match source CSV file encoding.
      $encoded_query = $this->char_encoded_query($encoding);
      $escaped_query[$key] = preg_quote($encoded_query[$key]);
    }

    $regexp = implode(".*", array_values($escaped_query));
    $lines = array();
    // Quickly filter the file with 'egrep'.
    // Using NKF is more robust then using "LANG=SJIS egrep"
    // but performance suffers pretty bad on large files.
    // exec("/usr/local/bin/nkf -w $source | egrep '(?:$regexp)'", $lines);
    //
    // We are currently using `"` for the quotes in the shell code.
    // This is because we have "F(ab')2" type stuff. We should
    // add code to escape quotes.
    exec("LANG=".$encoding." egrep \"(?:$regexp)\" $source", $lines);
    error_log("egrep '(?:$regexp)' $source");
    log_var_dump($lines);
    foreach ($lines as $line) {
      $row = str_getcsv($line);
      $row = $this->row_convert_encoding($row, $encoding);
      $result[$row[0]] = $row;
    }
    return $result;
  }

  protected function confirm_assoc_list_matches_query($assoc_list){
    foreach($this->utf8_encoded_query() as $field => $value) {
      if ($assoc_list[$field] != $value) {
        return false;
      }
    }
    return true;
  }

}