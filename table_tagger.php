<?php
require_once(dirname(__FILE__).'/../simplehtmldom_1_5/simple_html_dom.php');

class TableTagger {
  // public $source_parameters;
  // public $preview_directory;
  // public $current_directory;
  // public $previous_directory;
  // protected $data;
  // protected $ids;
  public $dom;
  public $grid = array();

  function __construct($html) {
    $this->dom = str_get_html($html);
    $this->fill_grid();
  }

  // Get the DOM element for the position
  // in the table specified with $row, $col.
  // This function understands rowspan and colspan.
  public function cell_by_grid($row, $col) {
    return $this->grid[$row][$col];
    // return $this->dom->find('tr', $row - 1)->find('td,th', $col - 1);
  }

  public function rows() {
    return $this->dom->find('tr');
  }

  // `fill_grid()` traverses the table
  // and creates a nested array ("grid")
  // which references each cell in the table.
  // It understands rowspans and colspans.
  //
  // Use this in the `cell($row, $col)` function
  // to get cells corresponding to positions in
  // the table.
  //
  // We use $row = 1, $col = 1 as the top-left cell.
  private function fill_grid() {
    $this->grid = array();
    $rowspan_memo = array();
    $rows = $this->rows();
    $current_row = 1;
    $current_col = 1;
    foreach ($rows as $row) {
      if (!array_key_exists($current_row, $this->grid))
        $this->grid[$current_row] = array();
      $cells = $row->find('td,th');
      foreach ($cells as $cell) {
        while(array_key_exists($current_col, $this->grid[$current_row])) {
          $current_col++;
        }

        if ($cell->colspan > 1) {
          $colspan = $cell->colspan;
        } else {
          $colspan = 1;
        }
        if ($cell->rowspan > 1) {
          $rowspan = $cell->rowspan;
        } else {
          $rowspan = 1;
        }

        for ($i=0; $i < $colspan; $i++) { 
          for ($j=0; $j < $rowspan; $j++) { 
            error_log("POO:".$current_row."-".($current_col + $i));
            $this->grid[$current_row + $j][$current_col + $i] = $cell;
          }
        }

        $current_col = $current_col + $colspan;          
      }
      $current_col = 1;
      $current_row++;
    }
  }
}