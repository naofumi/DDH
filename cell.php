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


  // Generate output JSON and a list
  // of scripts that we will call to adorn the
  // page.
  $result = array();
  $scripts = array();
  foreach($data_source->ids() as $id) {
    foreach($cells[$id] as $field) {
      if ($data_source->row($id)->get($field)) {
        if (is_preview()) {
          $result[$id."_x_".$field] = "<span style='font-size:10px;color:red;'>$id - $field</span><span class='ddh_campaign'>".$data_source->row($id)->get($field)."</span>";  
        } else {
          $result[$id."_x_".$field] = "<span class='ddh_campaign'>".$data_source->row($id)->get($field)."</span>";  
        }
      }
    }
    // TODO: We should refactor the $scripts collection out of the view
    //       and put it into a DataSource method so that we can get the
    //       list of scripts like `$data_source->campaign_javascript_files()`.
    if ($data_source->row($id)->is_campaign() && $data_source->row($id)->get('campaign_javascript_file')) {
      $scripts[$data_source->row($id)->get('campaign_javascript_file')] = true;
    }
  }

  $json = json_encode($result);
  $scripts_as_js = json_encode(array_keys($scripts));


  $output = <<<JS
json = $json;
for (var key in json) {
  var elements = document.getElementsByClassName(key);
  for (var i = 0; i < elements.length; i++) {
    elements[i].innerHTML = json[key];
  }
}
scripts = $scripts_as_js;
(function(){
  var fjs = document.getElementsByTagName('script')[0];
  for (var i=0; i < scripts.length; i++) { 
    var js = document.createElement('script');
    js.src = "/ddh_jp/javascripts/" + scripts[i];
    js.setAttribute('async', 'true');
    fjs.parentNode.insertBefore(js, fjs);
  }
})();
JS;

  echo $output;
  cache_end();
