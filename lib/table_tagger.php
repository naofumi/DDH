<?php
require_once(dirname(__FILE__).'/simplehtmldom_1_5/simple_html_dom.php');

class TableTagger {
  // public $source_parameters;
  // public $preview_directory;
  // public $current_directory;
  // public $previous_directory;
  // protected $data;
  // protected $ids;
  public $dom;
  public $grid = array();
  protected $id_col = null;
  protected $id_regex = false;

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
            // error_log("POO:".$current_row."-".($current_col + $i));
            $this->grid[$current_row + $j][$current_col + $i] = $cell;
          }
        }

        $current_col = $current_col + $colspan;          
      }
      $current_col = 1;
      $current_row++;
    }
  }

  public function add_class_to_grid($row, $col, $class_name) {
    $cell = $this->cell_by_grid($row, $col);
    $this->add_class_to_cell($cell, $class_name);
  }

  public function add_class_to_cell($cell, $class_name) {
    $class_array = $this->class_array($cell);
    array_push($class_array, $class_name);
    $new_class_array = array_unique($class_array);
    $cell->class = join(" ", $new_class_array);
    return $cell;
  }

  public function remove_class_from_grid($row, $col, $class_name) {
    $cell = $this->cell_by_grid($row, $col);
    $this->remove_class_from_cell($cell, $class_name);
  }

  public function remove_class_from_cell($cell, $class_name) {
    $class_array = $this->class_array($cell);
    if (($index = array_search($class_name, $class_array)) !== false) {
      array_splice($class_array, $index, 1);
    }
    $cell->class = join(" ", $class_array);
    return $cell;
  }

  private function class_array($cell) {
    if ($cell->class) {
      return preg_split("/\s+/", $cell->class);
    } else {
      return array();
    }
  }

  public function set_id_col($col_num) {
    return $this->id_col = $col_num;
  }

  public function id_col() {
    return $this->id_col;
  }

  public function set_id_regex($regex) {
    return $this->id_regex = $regex;
  }

  public function set_tag_for_field_in_col($field_name, $col_num, $remove_content = false) {
    for($row_num = 1; $row_num <= count($this->grid); $row_num++) {
      $row = $this->grid[$row_num];
      $id_cell = $this->cell_by_grid($row_num, $this->id_col());
      $id_value = $id_cell->plaintext;
      if (!$this->id_regex || preg_match($this->id_regex, $id_value)) {
        $tag_class = $id_value."_x_".$field_name;
        $target_cell = $this->cell_by_grid($row_num, $col_num);
        $this->add_class_to_cell($target_cell, $tag_class);
        $this->add_class_to_cell($target_cell, "ddhcell");
        if ($remove_content) {
          $target_cell->innertext = "";
        }
      }
    }
    return $this->dom;
  }
}