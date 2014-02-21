<?php 
// price_table.php is used to insert whole tables.
//
  require('ddh/jsonp.php');

  $data_source = new DataSource($source_parameters, get_ids());
  start_jsonp($data_source);

  // Retrieve data from CSV files
  $bench_start = microtime(true);
  $bench_time = microtime(true) - $bench_start;

  $data_source->add_rowspans();
  odd_even_reset();
?>
<div class="price clearfix">
  <h3>価格表</h3>
  <table class="<?php echo is_preview() ? 'preview' : '' ?>">
    <tr class="odd">
      <th>製品名</th>
      <th>数量</th>
      <th>Cat.No.</th>
      <th>価格</th>
    </tr>
    <?php foreach($data_source->rows() as $row): ?>
      <tr class="<?php echo odd_even() ?>">
        <?php echo $row->td('name', array('class' => 'Pname')); ?>
        <?php echo $row->td('package'); ?>
        <?php echo $row->td('cat_no'); ?>
        <?php echo $row->td('price'); ?>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php
  if (is_preview())
    echo "CSV parse time $bench_time secs.";
?>

<?php
  output_jsonp();