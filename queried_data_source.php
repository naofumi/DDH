<?php
require_once(__DIR__.'/data_row.php');
$encoding = "sjis";
class QueriedDataSource extends DataSource {
  private $query;

  function __construct($source_parameters, $query) {
    parent::__construct($source_parameters);
    $this->query = $query;
  }

}