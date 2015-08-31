<?php
require_once (dirname(__FILE__) . '/simpletest/autorun.php');
require_once (dirname(__FILE__) . '/../lib/table_tagger.php');
require_once (dirname(__FILE__) . '/../lib/view_helpers.php');

class TableTaggerTest extends UnitTestCase {
  public $html;

  function setUp() {
    $this->html = <<<HTML
<table>
  <tr>
    <th>cell-1-1</th>
    <td>cell-1-2</td>
    <td>cell-1-3</td>
  </tr>
  <tr>
    <th colspan="2">cell-2-1</th>
    <td>cell-2-3</td>
  </tr>
  <tr>
    <th rowspan="2">cell-3-1</th>
    <td class="class1 class2">cell 3-2</td>
    <td>cell-3-3</td>
  </tr>
  <tr>
    <td>cell-4-2</td>
    <td>cell-4-3</td>
  </tr>
</table>

HTML;
  }
  function testSimpleCellTh() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell-1-1", $table_tagger->cell_by_grid(1,1)->plaintext);
  }

  function testSimpleCellTd() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell-1-2", $table_tagger->cell_by_grid(1,2)->plaintext);
  }

  function testColSpanCell() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell-2-1", $table_tagger->cell_by_grid(2,2)->plaintext);
  }

  function testRowSpanCellFirstRow() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell-3-1", $table_tagger->cell_by_grid(3,1)->plaintext);
  }

  function testRowSpanCellSecondRow() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell-3-1", $table_tagger->cell_by_grid(4,1)->plaintext);
  }

  function testAddClass() {
    $table_tagger = new TableTagger($this->html);
    $table_tagger->add_class_to_grid(1, 2, "my_class");
    $this->assertEqual("my_class", $table_tagger->dom->find("tr", 0)->find("td", 0)->class);
    // error_log($table_tagger->dom);
  }

  function testRemoveClass() {
    $table_tagger = new TableTagger($this->html);
    $table_tagger->remove_class_from_grid(3, 2, "class1");
    $this->assertEqual("class2", $table_tagger->dom->find("tr", 2)->find("td", 0)->class);
  }

  function testAddTag() {
    $table_tagger = new TableTagger($this->html);
    $table_tagger->set_id_col(1);
    $table_tagger->set_tag_for_field_in_col("price", 3);
    $this->assertEqual("cell-3-1_x_price ddhcell", $table_tagger->dom->find("tr", 2)->find("td", 1)->class);
  }

  function testAddTagOnlyForIdsMatchingRegex() {
    $table_tagger = new TableTagger($this->html);
    $table_tagger->set_id_col(1);
    $table_tagger->set_id_regex("/cell-2/");
    $table_tagger->set_tag_for_field_in_col("price", 3);
    $this->assertEqual("cell-2-1_x_price ddhcell", $table_tagger->dom->find("tr", 1)->find("td", 0)->class);
    $this->assertFalse($table_tagger->dom->find("tr", 2)->find("td", 1)->class);
  }

}
