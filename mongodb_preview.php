<?php
  // We upload a file, and prepend it with
  // a version number (which is related to the)
  // uploaded datatime.
  //
  // The file is saved in the data folder.
  // We check the filename of the file. The filename
  // must match the format that we have specified in
  // the config file (filenames must be consistent).
  // Uploaded files are automatically 
  $suppress_reverse_proxy_requirement = true;
  require(dirname(__FILE__).'/jsonp.php');

  authenticate();

  $source_id = $_GET['source_id'];
  $filename = $source_parameters[$source_id]['filename'];
  $encoding = $source_parameters[$source_id]['encoding'];
  $fields = $source_parameters[$source_id]['fields'];

  $updated_at = $_GET['updated_at'];

  $data_source = new MongoDBDataSource($source_parameters, 'current');

  $collection = $data_source->db->$source_id;

  $row_count = $collection->count(['updated_at' => (int)$updated_at]);

  $start_from_row = isset($_GET['from_line']) ? $_GET['from_line'] : 0;

  if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
  } else {
    $page = 1;    
  }
  $per_page = 100;
  $total_pages = intval($row_count / $per_page) + 1;
  $cursor = $collection->find(['updated_at' => (int)$updated_at])->
                         sort(['row_num' => 1])->skip($start_from_row + ($page - 1) * $per_page)->limit($per_page);

  $rows = array();
  $id_field = $source_parameters[$source_id]['id_field'];
  foreach ($cursor as $id => $value) {
    $row = $value['row'];
    $row_num = $value['row_num'];
    $rows[$row_num] = $row;
  }

  $pagination_leader = 5;
  $pagination_middle = 3;

  include('header.php');
?>
<?php echo_flash(); ?>
<div>
  <a href="snapshots.php">&lt; スナップショット管理画面に戻る</a>
</div>
<h1>"<?php echo $source_id ?>" (<?php echo date("Y-m-d H:i:s", $updated_at) ?>バージョン) の内容確認</h1>
<form action="" method=get style="margin: 10px 0;">
  <input type="hidden" name="source_id" value="<?php echo $_GET['source_id'] ?>">
  <input type="hidden" name="updated_at" value="<?php echo $_GET['updated_at'] ?>">
  表示開始業番号<input type="text" name="from_line" value="<?php echo isset($_GET['from_line']) ? $_GET['from_line'] : '' ?>" style="width: 100px;font-size: 14px;">
  <button type=submit name="search" style="font-size: 14px;">表示</button>
</form>
<div class="pagination_controls" style="font-size: 16px;margin: 10px 0;">
  <?php if ($page > 1){ echo "<a href=\"".add_query_to_url($_SERVER['REQUEST_URI'], array('page'=>($page - 1)))."\">&lt</a>"; } ?>
  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <?php if (($i == $pagination_leader || $i == $total_pages - $pagination_leader - 1) && !(abs($page - $i) < $pagination_middle)): ?>
      ...
    <?php endif; ?>
    <?php if ($i < $pagination_leader || $i > $total_pages - $pagination_leader - 1 || abs($page - $i) < $pagination_middle): ?>
      <?php echo "<a href=\"".
                 add_query_to_url($_SERVER['REQUEST_URI'], array('page'=>$i)).
                 "\" class=\"".($page == $i ? 'selected' : '')."\">$i</a>"." "; ?>
    <?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $total_pages){ echo "<a href=\"".add_query_to_url($_SERVER['REQUEST_URI'], array('page'=>($page + 1)))."\">&gt</a>"; } ?>
</div>  
<table class="directory_view">
  <tr>  
    <th></th>
    <?php for ($i = 0; $i < count($fields); $i++ ): ?>
      <th><?php echo $fields[$i] ?></th>
    <?php endfor; ?>
  </tr>
  <?php foreach ($rows as $row_num => $row): ?>
    <tr>
      <th><?php echo $row_num ?></th>
      <?php foreach($row as $key => $value): ?>
        <td><?php echo $value; ?></td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
</table>
<?php include('footer.php') ?>
