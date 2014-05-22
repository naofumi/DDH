<?php
require_once (dirname(__FILE__) . '/simpletest/autorun.php');
require_once (dirname(__FILE__) . '/../table_tagger.php');

class TableTaggerTest extends UnitTestCase {
  public $html;

  function setUp() {
    $this->html = <<<HTML
<table>
  <tr>
    <th>cell 1-1</th>
    <td>cell 1-2</td>
    <td>cell 1-3</td>
  </tr>
  <tr>
    <th colspan="2">cell 2-1</th>
    <td>cell 2-3</td>
  </tr>
  <tr>
    <th rowspan="2">cell 3-1</th>
    <td>cell 3-2</td>
    <td>cell 3-3</td>
  </tr>
  <tr>
    <td>cell 4-2</td>
    <td>cell 4-3</td>
  </tr>
</table>

HTML;
  }
  function testSimpleCellTh() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell 1-1", $table_tagger->cell_by_grid(1,1)->plaintext);
  }

  function testSimpleCellTd() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell 1-2", $table_tagger->cell_by_grid(1,2)->plaintext);
  }

  function testColSpanCell() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell 2-1", $table_tagger->cell_by_grid(2,2)->plaintext);
  }

  function testRowSpanCellFirstRow() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell 3-1", $table_tagger->cell_by_grid(3,1)->plaintext);
  }
  function testRowSpanCellSecondRow() {
    $table_tagger = new TableTagger($this->html);
    $this->assertEqual("cell 3-1", $table_tagger->cell_by_grid(4,1)->plaintext);
  }
}
