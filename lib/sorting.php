<?php
/////////////////////////////////////////////////
// Functions for sorting
/////////////////////////////////////////////////


// The PHP strcasecmp function return value is not
// limited to -1, 0, 1, which makes it difficult to
// use when we have multiple sort criteria.
// strcasecmp_norm only returns -1, 0, 1.
function strcasecmp_norm($a, $b) {
  return cmp_norm(strcasecmp($a, $b));
}

// Compares $a and $b based on their indices in
// $array. If they are not present, then they will 
// be sent to the end of the array.
function cmp_in_array($a, $b, $array) {
  if ($a == $b) {
    return 0;
  } else {
    $a_pos = array_search($a, $array);
    $b_pos = array_search($b, $array);
    if ($a_pos === false)
      $a_pos = 9999999;
    if ($b_pos === false)
      $b_pos = 9999999;
    return cmp_norm($a_pos - $b_pos);      
  }
}

// Converts signed integer to -1, 0, 1
function cmp_norm($cmp) {
  if ($cmp > 0) {
    return 1;
  } else if ($cmp < 0) {
    return -1;
  } else {
    return 0;
  }    
}
