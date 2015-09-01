<?
$suppress_reverse_proxy_requirement = true;
require(dirname(__FILE__).'/jsonp.php');

authenticate();

$source_id = $_GET['source_id'];
$updated_at = $_GET['updated_at'];
$all_field_in_source = $source_parameters[$source_id]['fields']; 

if (isset($_GET['export_all'])) {
  $fields_to_export = $all_field_in_source;
} else if (isset($_GET['export'])) {
  $fields_to_export = $_GET['export'];
}

include('header.php');
?>
<?php echo_flash(); ?>
<h1>データのエキスポート（ファイル: '<?= $source_parameters[$source_id]['filename'] ?>'）</h1>

<form method="get" action="export_preview.php">
  <? 
    foreach($all_field_in_source as $field_name) {
      export_checkbox_control($field_name, $fields_to_export); 
    }
  ?>
  <div style="text-align:right">
    <button type="submit" name="submit" value="export" style="font-size:20px">&nbsp;エキスポート&nbsp;</button><br />
  </div>

</form>

<?
include('footer.php');
?>