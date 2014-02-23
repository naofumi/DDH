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