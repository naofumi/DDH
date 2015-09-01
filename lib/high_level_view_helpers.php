<?
// High level view helpers that aim to
// provide default control elements.
//
// This should mainly benefit coding of tedious HTML controls.

//////////////////////////////////////
// For Search
//////////////////////////////////////
function text_entry_control($parameter_name, $label_html) {
?>
  <div class="query_menu text_entry_control <?= $parameter_name ?>">
    <label for="<?= $parameter_name ?>"><?= $label_html ?></label>
    <?php echo text_field($parameter_name) ?>
  </div>
<?
}

function select_menu_control($parameter_name, $label_html, $data_source, $default = null, $sort_callback = false) {
?>
  <div class="query_menu select_menu_control <?= $parameter_name ?>">
    <label for="<?= $parameter_name ?>"><?= $label_html ?></label>
    <? 
      if ($data_source->total_rows()){
        $facets = $data_source->facets[$parameter_name];
      } else {
        $facets = $data_source->all_values_in_field_sorted($parameter_name);
      }
      if ($sort_callback !== false) {
        uksort($facets, $sort_callback);
      }
      select_tag_with_facet($parameter_name, $facets, null, $default);
    ?>
  </div>
<?
}

function checkbox_control($parameter_name, $value, $label_html, $data_source) {
  $checked = (isset($_GET[$parameter_name]) && 
              $_GET[$parameter_name] == $value) ? true : false;
  $facets = $data_source->facets();
?>
  <div class="query_menu checkbox_control <?= $parameter_name ?>">
    <label for="<?= $parameter_name ?>"><?= $label_html ?></label>
    <input type="checkbox" name="<?= $parameter_name ?>" value="<?= $value ?>" <?= $checked ? "checked" : "" ?>>
    <? if ($data_source->total_rows()): ?>
      (<?= isset($facets[$parameter_name][$value]) ? 
              $facets[$parameter_name][$value] : 
              0; ?>)
    <? endif; ?>
  </div>
<?
}

//////////////////////////////////
// For Export
/////////////////////////////////
function export_checkbox_control($field_name, $checked_fields) {
  $checked = in_array($field_name, $checked_fields); ?>
  <div class="export_menu checkbox_control">
    <label for="<?= $field_name ?>"><?= $field_name ?></label>
    <input type="checkbox" name="export[]" value="<?= $field_name ?>" <?= $checked ? "checked" : "" ?>>
  </div> <?
}

