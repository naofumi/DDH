<?php
require_once(__DIR__.'/queried_data_source_base.php');
// It is probably a good idea to further subclass this based on the
// types of query we want to do (AND, OR, customized, etc.)
class QueriedDataSource extends QueriedDataSourceBase {
  // Read the CSV file and collect all
  // rows that match the egrep regex for the $ids.
  //
  // For performance, we only check for the presence of the query values
  // and we don't check for exact matches.
  // We have to do a double check, which is easier after the
  // assoc_list is generated.
  //
  // We initially did egrep with the original source file in the original encoding.
  // This however didn't work sometimes so we now use nkf to convert the file
  // to utf-8, and then we do the egrep.
  protected function get_rows_for_query($source, $encoding){
    if (!$this->query)
      return array();
    $result = array();
    
    $encoding = "UTF-8";
    // Encode the query into the same encoding as the source file.
    foreach(array_keys($this->query) as $key) {
      $source_encoded_query[$key] = mb_convert_encoding($this->query[$key], $encoding, $this->query_encoding);
    }      

    $regexp = implode(".*", array_values($source_encoded_query));
    $lines = array();
    // Quickly filter the file with 'egrep'.
    // Careful optimization of the regex is important!
    // exec("egrep '(?:$regexp)' $source", $lines);
    exec("/usr/local/bin/nkf -w $source | egrep '(?:$regexp)'", $lines);
    error_log('Egrep REGEX');
    error_log("egrep '(?:$regexp)' $source");
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