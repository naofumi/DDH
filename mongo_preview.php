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
  require(dirname(__FILE__).'/jsonp.php');
  // require(dirname(__FILE__).'/mongodb_data_source.php');

  basic_auth();

  $source_id = $_GET['source_id'];
  $filename = $source_parameters[$source_id]['filename'];
  $encoding = $source_parameters[$source_id]['encoding'];
  $fields = $source_parameters[$source_id]['fields'];

  $updated_at = $_GET['updated_at'];


  $row_count = get_row_count($file, $encoding);
  if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
  } else {
    $page = 1;    
  }
  $per_page = 100;
  $total_pages = intval($row_count / $per_page) + 1;
  $rows = get_rows(($page - 1) * $per_page, $per_page, $file, $encoding, $delimiter);
  $pagination_leader = 5;
  $pagination_middle = 3;

  include('header.php');
?>
<?php echo_flash(); ?>
<div>
  <a href="upload.php">&lt; アップロード画面に戻る</a>
</div>
<h1>"<?php echo $file_identifier ?>"をプレビュー</h1>
<?php if ($page > 1){ echo "<a href=\"".add_query_to_url($_SERVER['REQUEST_URI'], array('page'=>($page - 1)))."\">&lt</a>"; } ?>
<?php for ($i = 1; $i <= $total_pages; $i++): ?>
  <?php if (($i == $pagination_leader || $i == $total_pages - $pagination_leader - 1) && !(abs($page - $i) < $pagination_middle)): ?>
    ...
  <?php endif; ?>
  <?php if ($i < $pagination_leader || $i > $total_pages - $pagination_leader - 1 || abs($page - $i) < $pagination_middle): ?>
    <?php echo "<a href=\"".
               add_query_to_url($_SERVER['REQUEST_URI'], array('page'=>$i)).
               "\">$i</a>"." "; ?>
  <?php endif; ?>
<?php endfor; ?>
<?php if ($page < $total_pages){ echo "<a href=\"".add_query_to_url($_SERVER['REQUEST_URI'], array('page'=>($page + 1)))."\">&gt</a>"; } ?>
<table>
  <tr>  
    <?php for ($i = 0; $i < count($fields); $i++ ): ?>
      <th><?php echo $fields[$i] ?></th>
    <?php endfor; ?>
  </tr>
  <?php foreach ($rows as $row): ?>
    <tr>
      <?php for ($i = 0; $i < count($fields); $i++ ): ?>
        <td><?php if (isset($row[$i])) echo $row[$i]; ?></td>
      <?php endfor; ?>
    </tr>
  <?php endforeach; ?>
</table>
<?php include('footer.php') ?>
