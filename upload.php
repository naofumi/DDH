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
  require(__DIR__.'/jsonp.php');

  $preview_directory = dirname(__FILE__).'/../data/preview/';
  $current_directory = dirname(__FILE__).'/../data/current/';
  $previous_directory = dirname(__FILE__).'/../data/previous/';
  $directories = array($preview_directory, $current_directory,
                       $previous_directory);

  basic_auth();

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
  <table class="directory_view" style="width:100%">
    <tr>
      <th>Preview</th>
      <th>Current</th>
      <th>Previous</th>
    </tr>
    <tr>
      <?php foreach ($directories as $directory): ?>
        <td style="width: 33%;vertical-align:top;">
          <?php foreach(scandir($directory) as $file): ?>
            <?php if (substr(basename($file), 0, 1) === ".") {continue;} ?>
            <div>
              <?php echo basename($file); ?> <a href="<?php echo "preview.php?file=".basename($directory)."/$file" ?>">[確認]</a><br />
              (<?php echo date("y-m-d H:i:s O", filemtime($directory.$file)) ?>)
            </div>
          <?php endforeach; ?>
        </td>
      <?php endforeach; ?>
    </tr>
  </table>
</fieldset>
<fieldset>
  <legend>公開ステータスの変更</legend>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
    <ol>
      <li><input type=submit name="publish_preview" value="'Preview'のファイルを公開する"></li>
      <li><input type=submit name="publish_rollback" value="'Previous'にある元のファイルにもどす"></li>
    </ol>
  </form>
</fieldset>
<fieldset>
  <legend>新しいファイルを"Preview"にアップロード</legend>
  ファイル名は
  <?php foreach($source_parameters as $key => $value): ?>
    "<?php echo $key ?>"は"<?php echo $value['filename']?>", 
  <?php endforeach; ?>
  にしてください。（それ以外のファイル名はアップロードされますが、無視されます）

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
