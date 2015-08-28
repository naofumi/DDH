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

  $preview_directory = dirname(__FILE__).'/../data/preview/';
  if (!file_exists($preview_directory) || !is_writable($preview_directory))
    die("$preview_directory must be available and writable by Apache.");
  $current_directory = dirname(__FILE__).'/../data/current/';
  if (!file_exists($current_directory) || !is_writable($current_directory))
    die("$current_directory must be available and writable by Apache.");
  $previous_directory = dirname(__FILE__).'/../data/previous/';
  if (!file_exists($previous_directory) || !is_writable($previous_directory))
    die("$previous_directory must be available and writable by Apache.");
  
  $directories = array($preview_directory, $current_directory,
                       $previous_directory);

  authenticate();

  // File was uploaded
  if ($_FILES) {
    $uploaddir = $preview_directory;
    $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
    if ($_FILES['userfile']['type'].strpos('csv') === false) {
      echo '<div class="notice">';
      echo "ERROR: This file is not a CSV file.";
      echo "</div>";
    } else if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
      $_SESSION["flash"] = "Successfully uploaded \"".$_FILES['userfile']['name'].
                           "\" (".$_FILES['userfile']['size']." bytes)"."\n";
      header("Location: ".$_SERVER["REQUEST_URI"]);
    } else {
      echo '<div class="notice">';
      echo "ERROR: ".$_FILES['userfile']['error']."\n";
      echo "<pre>";
      print_r($_FILES);
      echo "</pre>";
      echo "</div>";
    }
  } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Publish preview button was pressed.
    if (isset($_POST['publish_preview'])) {
      // TODO: Put stuff to move files here
      publish_preview_files();
      set_flash("Published files in Preview");
      redirect_to();
    } else if (isset($_POST['publish_rollback'])) {
      // TODO: Put stuff to move files here
      rollback_files();
      set_flash("Rollbacked files in Previous");
      redirect_to();
    }
  } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  }

  include('header.php');
?>
<?php echo_flash(); ?>
<fieldset>
  <legend>サーバ上にあるファイル</legend>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
    <table class="directory_view" style="width:100%">
      <tr>
        <th>控え</th>
        <th>現行</th>
        <th>前回</th>
      </tr>
      <tr>
        <?php foreach ($directories as $directory): ?>
          <td>
            <?php foreach(scandir($directory) as $file): ?>
              <?php if (substr(basename($file), 0, 1) === ".") {continue;} ?>
              <div class="file<?php if (!in_array(basename($file), all_filenames())) {echo " greyed";} ?>">
                <?php echo basename($file); ?> <a href="<?php echo "preview.php?file=".basename($directory)."/$file" ?>" class="preview_button">[内容確認]</a>
                <div class="date">(更新: <?php echo date("Y-m-d H:i:s", filemtime($directory.$file)) ?>)</div>
              </div>
            <?php endforeach; ?>
          </td>
        <?php endforeach; ?>
      </tr>
      <tr class="publish_action_cells">
        <td><input type=submit name="publish_preview" value="'控え'のファイルを公開 =>>"></td>
        <td></td>
        <td><input type=submit name="publish_rollback" value="<<= '前回'のファイルにもどす"></td>
      </tr>
    </table>
  </form>
</fieldset>
<fieldset>
  <legend>新しいファイルを"控え"にアップロード</legend>
  <h3>CSVファイル形式の制限</h3>
  現時点では改行を含むCSVファイルには対応していません。改行を含むCSVの場合は、その行が検索されないなどの問題が予想されます。
  <h3>注意</h3>
  ファイル名を厳密に守らないと反映されません。<br />
  <?php foreach($source_parameters as $key => $value): ?>
    "<?php echo $key ?>" のデータは "<?php echo $value['filename']?>"<br />
  <?php endforeach; ?>
  にしてください。<br /><br />

  <!-- The data encoding type, enctype, MUST be specified as below -->
  <form enctype="multipart/form-data" action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
    <!-- MAX_FILE_SIZE (in bytes) must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
    <!-- Name of input element determines name in $_FILES array -->
    ファイルを選択: <input name="userfile" type="file" />
    <input type="submit" value="アップロード" />
  </form>
</fieldset>

<?php include('footer.php') ?>
