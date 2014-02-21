<?php
// cell is used to insert data into
// cells.

  require('jsonp.php');
  header('Content-Type: application/javascript');
  
  // Process request params
  $cells = array();
  if ($_GET['reqs']) {
    $requests = explode(',', $_GET['reqs']);
    foreach ($requests as $request) {
      $id_field = explode('_x_', $request);
      $cells[$id_field[0]][] = $id_field[1];
    }
  }

  // Retrieve data from CSV files
  $data_source = new DataSource($source_parameters, array_keys($cells));

  cache_start($data_source);

  // Generate output JSON
  $result = array();
  foreach($data_source->ids() as $id) {
    foreach($cells[$id] as $field) {
      if ($data_source->row($id)->get($field)) {
        if (is_preview()) {
          $result[$id."_x_".$field] = "<span style='font-size:10px;color:red;'>$id - $field</span>".$data_source[$id]->get($field);  
        } else {
          $result[$id."_x_".$field] = $data_source->row($id)->get($field);  
        }
      }
    }
  }

  $json = json_encode($result);

  $output = <<<JS
json = $json;
for (var key in json) {
  var elements = document.getElementsByClassName(key);
  for (var i = 0; i < elements.length; i++) {
    elements[i].innerHTML = json[key];
  }
}
JS;

  echo $output;
  cache_end();
