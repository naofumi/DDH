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

// Santize a single param value.
function sanitize_param($param) {
  return htmlspecialchars($param);
}

function dasherize($string) {
  $lower = mb_strtolower($string);
  $dasherized = preg_replace("/(\W+)/u", "_", $lower);
  // $double_dash_removed = preg_replace("/(_+)/", "`_", $dasherized);
  return $dasherized;
}

// Create a select tag. The menu options are provided in $options.
// $options can be a regular array or an associated array.
// If it is a regular array, the values will be used both for the parameters
// and display. If it is an associated array, then the keys will be
// used for the parameters and the values will be used for diplay.
//
// The $default allows us to specify which option is selected when the 
// parameter has not been set.
function select_tag($name, $options = array(), $attributes = array(), $default = null) {
  echo "<select name=\"$name\"".attributes_string_from_hash($attributes).">";
  $is_assoc_options = is_assoc($options);
  echo "<option value=\"\"></option>";
  foreach ($options as $value => $tag) {
    if (!$is_assoc_options) {
      $value = $tag;
    }
    if (isset($_REQUEST[$name]) && strtolower($_REQUEST[$name]) === strtolower($value)) {
      $selected = " selected";
    } else if (!isset($_REQUEST[$name]) && $default == $value) {
      $selected = " selected";
    } else {
      $selected = "";
    }
    echo "<option value=\"$value\"$selected>$tag</option>";
  }
  echo "</select>";
}

// Generates a select tag using the facets as the options.
function select_tag_with_facet($name, $facets = array(), $attributes = array(), $default = null) {
  $options = array();
  foreach ($facets as $value => $count) {
    $options[$value] = "$value ($count)";
  }
  select_tag($name, $options, $attributes, $default);
}

// Generates a select tag while summing up the counts for
// `ddhq:` ranged options.
// $options must have `ddhq:` ranged values.
// $facets must have numeric keys.
function select_tag_with_ranged_facet($name, $options = array(), $facets = array(), $attributes = array(), $default = null) {
  $new_options = array();
  foreach ($options as $range => $tag) {
    $ddhq_range = preg_replace("/^ddhq:/", "", $range);
    $range_count = 0;
    foreach ($facets as $value => $count) {
      if (is_in_range($value, $ddhq_range)) {
        $range_count = $range_count + $count;
      }
    }
    $new_options[$range] = $range_count ? "$tag ($range_count)" : "$tag";
  }
  select_tag($name, $new_options, $attributes, $default);
}

// Range is as ddhq formatted string
// We don't do equal comparisons because we use floats
function is_in_range($value, $range) {
  $tuples = explode(",", $range);
  foreach ($tuples as $tuple) {
    $condition = explode(":", $tuple);
    $operator = $condition[0];
    $compare_to = $condition[1];
    if ($operator == "lt") {
      if ($value < $compare_to)
        continue;
    } else if ($operator == "gt") {
      if ($value > $compare_to)
        continue;      
    }
    return false;
  }
  return true;
}

function attributes_string_from_hash($attributes) {
  $attribute_string = "";
  if (!$attributes) {
    return "";
  }
  foreach($attributes as $name => $value) {
    $attribute_string = $attribute_string." $name=\"$value\"";
  }
  return $attribute_string;  
}

function text_field($name, $attributes = array()) {
  $value = "";
  if (isset($_GET[$name])) {
    $value = $_GET[$name];
  }
  echo "<input type=\"text\" value=\"$value\" name=\"$name\"".attributes_string_from_hash($attributes).">";

}

// http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
function is_assoc($array) {
  return (bool)count(array_filter(array_keys($array), 'is_string'));
}