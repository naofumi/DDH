<?php
require_once (dirname(__FILE__) . '/simpletest/autorun.php');
require_once (dirname(__FILE__) . '/../jsonp.php');
require_once (dirname(__FILE__) . '/../../config.php');

class JsonPTest extends UnitTestCase {
  function testConvertRowsToAssocLists() {
  	$row = array("cell_0", "cell_1", "cell_2");
  	$field_names = array("field_0", "field_1", "field_2");
  	$assoc_list = convert_row_to_assoc_list($row, $field_names);
  	$this->assertIdentical(array("field_0" => "cell_0", 
  	                             "field_1" => "cell_1", 
  	                             "field_2" => "cell_2"),
  	                       $assoc_list);
  }

  function testGetRowsForIds() {
  	$test_data_csv = dirname(__FILE__).'/price_list_test_data.csv';
  	$ids = array("201234");
  	$rows = get_rows_for_ids($ids, $test_data_csv);
  	$this->assertIdentical(array("201234" => array("201234", "10µl", "￥1345")),
  	                       $rows);
  }

  function testGetAccosListForIds() {
  	$test_data_csv = dirname(__FILE__).'/price_list_test_data.csv';
  	$ids = array("201234");
  	$field_names = array('cat_no','package','price','status');
		$assoc_list = get_assoc_list_for_ids($ids, $test_data_csv, $field_names);
		$this->assertIdentical(array("201234" => 
		                         array("cat_no" => "201234", 
		                               "package" => "10µl", 
		                               "price" => "￥1345",
		                               "status" => null)
		                      ), $assoc_list);
  }

  function testGetIds() {
  	$this->assertEqual(get_ids("1"), array(1));
  	$this->assertEqual(get_ids("1,2"), array(1, 2));
  	$this->assertEqual(get_ids(""), array());
  	$this->assertEqual(get_ids("1,2,test"), array(1, 2, "test"));
  }

  function testGetAll() {
    $result = get_all_for_ids(array('201234'));
    $this->assertEqual($result['201234']['price'], '￥1345');
  }

}
?>