<?php
// Cycles through odd/even
$odd_even_cycle = 0;
function odd_even($values = array("even", "odd")){
  global $odd_even_cycle;
  $cycle_size = count($values);
  $result = $values[$odd_even_cycle % $cycle_size];
  $odd_even_cycle++;
  return $result;
}

function odd_even_reset(){
  global $odd_even_cycle;
  $odd_even_cycle = 0;
}

// Santize and decode a single param value
function sanitize_param($param) {
  return htmlspecialchars(mb_convert_encoding($param, 'UTF-8', array('SJIS', 'UTF-8', 'EUC-JP')));
}

function dasherize($string) {
  $lower = strtolower($string);
  return preg_replace("/(\W)/", "_", $lower);
}

function select_tag($name, $options = array(), $attributes = array()) {
  echo "<select name=\"$name\"".attributes_string_from_hash($attributes).">";
  $is_assoc_options = is_assoc($options);
  echo "<option value=\"\"></option>";
  foreach ($options as $value => $tag) {
    if (!$is_assoc_options) {
      $value = $tag;
    }
    if (isset($_GET[$name]) && $_GET[$name] === $value) {
      $selected = " selected";
    } else {
      $selected = "";
    }
    echo "<option value=\"$value\"$selected>$tag</option>";
  }
  echo "</select>";
}

function attributes_string_from_hash($attributes) {
  $attribute_string = "";
  foreach($attributes as $name => $value) {
    $attribute_string = $attribute_string." $name=\"$value\"";
  }
  return $attribute_string;  
}

// http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
function is_assoc($array) {
  return (bool)count(array_filter(array_keys($array), 'is_string'));
}