<?php
  $suppress_reverse_proxy_requirement = true;
  require(dirname(__FILE__).'/jsonp.php');

  authenticate();

  $data_source = new MongoDBDataSource($source_parameters, 'preview');

  $snapshots = $data_source->snapshots();

  // File was uploaded
  if ($_FILES) {
    $uploaddir = $data_source->staging_directory();
    if (!file_exists($uploaddir) || !is_writable($uploaddir))
      die("$uploaddir must be available and writable by Apache.");

    $uploadfile = $uploaddir.basename($_FILES['userfile']['name']);
    $extension = strtolower(pathinfo($uploadfile, PATHINFO_EXTENSION));
    if (($extension != 'csv') && ($extension != 'txt')) {
      echo '<div class="notice">';
      echo "ERROR: このファイル\"$uploadfile\"は CSV でも TXT(タブ区切りテキスト) でもないようです.";
      echo "</div>";
    } else if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
      $flash_message = "";
      $flash_message .= "\"".$_FILES['userfile']['name'].
                        "\" (".$_FILES['userfile']['size']." bytes) をサーバにアップロードしました"."<br />";
      $flash_message .= $data_source->load_new_sources();
      set_flash($flash_message);
      redirect_to();
    } else {
      echo '<div class="notice">';
      echo "ERROR: ".$_FILES['userfile']['error']."\n";
      echo "<pre>";
      print_r($_FILES);
      echo "</pre>";
      echo "</div>";
    }
  } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Publish button was pressed.
    if (isset($_POST['publish']) && $_POST['publish'] == 'preview') {
      if ($data_source->current_preview_snapshot() == $data_source->current_snapshot()) {
        set_flash("Error: 現在公開中のスナップショットと同じです");
      } else {
        $data_source->publish_preview_snapshot($_POST['comment']);
        set_flash("プレビューを公開しました");        
      }
      redirect_to();
    } else if (isset($_POST['publish'])) {
      // TODO: Put stuff to move files here
      $data_source->publish_snapshot($_POST['publish']);
      set_flash("".date("Y-m-d H:i:s", $_POST['publish'])."のバージョンに戻しました");
      redirect_to();
    } else if (isset($_POST['preview'])) {
      // Change the preview version
      $_SESSION['preview'] = $_POST['preview'];
      if ($_POST['preview'] == "preview") {
        set_flash("準備中のバージョンをプレビューします");
        redirect_to();
      } else if ($_POST['preview']) {
        set_flash("".date("Y-m-d H:i:s", $_POST['preview'])."のバージョンをプレビューします");
        redirect_to();        
      } else {
        set_flash("通常モードに戻しました");
        redirect_to();                
      }
    } else if (isset($_POST['drop_database'])) {
      $data_source->db->drop();
      redirect_to();
    }
  } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  }

  include('header.php');
?>
<?php echo_flash(); ?>
<div style="width:60%;float:left;">
  <fieldset >
    <legend>準備中のスナップショット</legend>
      <table class="directory_view" style="width:100%">
        <?php $snapshot = $data_source->current_preview_snapshot() ?>
        <?php $uploaded_source_ids = $data_source->preview_snapshot() ? 
                                     array_keys($data_source->preview_snapshot()['sources']) :
                                     array(); ?>
        <tr>
          <th>
            準備中のスナップショット
            <form action="" method=post style="display:inline">
              <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
              <?php if(true): ?>
                <button type=submit name="preview" value="preview">プレビューする</button>
              <?php endif; ?>
              <?php if($uploaded_source_ids): ?>
                <button type=submit name="publish" value="preview" onclick="return confirm('本当に準備中のバージョンを公開しますか？');">公開</button>
                <div style="font-size:smaller">コメント</div>
                <textarea name="comment" style="width:90%"></textarea>
              <?php endif; ?>
            </form>
          </th>
        </tr>
        <tr>
          <td>
            <ol>
              <?php foreach ($snapshot['sources'] as $source_id => $updated_at): ?>
                <li class="<?php echo in_array($source_id, $uploaded_source_ids) ? '' : 'dimmed' ?>">
                  <?php if ($updated_at): ?>
                    <div style="float:right">
                      <span class="date"><?php echo date("Y-m-d H:i:s", $updated_at) ?> Up</span>
                      <a href="<?php echo "mongodb_preview.php?updated_at=$updated_at&source_id=$source_id" ?>" class="preview_button">[内容]</a>
                      <a href="<?php echo "select_options.php?updated_at=$updated_at&source_id=$source_id" ?>" class="preview_button">[分析]</a>
                    </div>
                  <?php endif; ?>
                  <div><?php echo $source_id ?></div>
                  <div style="clear:both"></div>
                </li>
              <?php endforeach; ?>
            </ol>
          </td>
        </tr>
      </table>
  </fieldset>
  <fieldset >
    <legend>スナップショット</legend>
      <table class="directory_view" style="width:100%">
        <?php foreach ($snapshots as $published_at => $snapshot): ?>
          <tr>
            <th class="<?php echo isset($snapshot['current']) && $snapshot['current'] ? 'current_snapshot' : '' ?>">
              <?php if (isset($snapshot['current']) && $snapshot['current']) {echo "<span style='color:red'>公開中</span>";} ?>
              <?php echo date("Y-m-d H:i:s", $published_at) ?> 初公開バージョン<?php echo $published_at ?>
              <form action="" method=post style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
                <?php if(!isset($snapshot['current']) || !$snapshot['current']): ?>
                  <button type=submit name="preview" value="<?php echo $published_at ?>">プレビューする</button>
                <?php endif; ?>
                <?php if (!isset($snapshot['current']) || !$snapshot['current']): ?>
                  <button type=submit name="publish" value="<?php echo $published_at ?>" onclick="return confirm('本当に公開中のバージョンを変更しますか？');">これに戻す</button>
                <?php endif; ?>
              </form>
              <?php if (isset($snapshot['comment'])): ?>
                <div style="font-size:smaller">
                  <?php echo $snapshot['comment'] ?>
                </div>
              <?php endif; ?>
            </th>
          </tr>
          <tr>
            <td>
              <ol>
                <?php foreach ($snapshot['sources'] as $source_id => $updated_at): ?>
                  <li>
                    <?php if ($updated_at): ?>
                      <div style="float:right">
                        <span class="date"><?php echo date("Y-m-d H:i:s", $updated_at) ?> Up</span>
                        <a href="<?php echo "mongodb_preview.php?updated_at=$updated_at&source_id=$source_id" ?>" class="preview_button">[内容]</a>
                        <a href="<?php echo "select_options.php?updated_at=$updated_at&source_id=$source_id" ?>" class="preview_button">[分析]</a>
                         <a href="<?php echo "export_configure.php?updated_at=$updated_at&source_id=$source_id&export_all=1" ?>" class="preview_button">[書出]</a>
                      </div>
                    <?php endif; ?>
                    <div><?php echo $source_id ?></div>
                    <div style="clear:both"></div>
                  </li>
                <?php endforeach; ?>
              </ol>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
  </fieldset>
</div>
<fieldset style="width:35%;float:right;">
  <legend>プレビューモード</legend>
  <?php if (is_preview()): ?>
    現在プレビューモードです。<br>
    "<?php echo preview_version_name() ?>"のバージョンが表示されます。
    <form action="" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
      <input type="hidden" name="preview" value="" />
      <input type="submit" value="通常モードに戻す" />
    </form>
  <?php else: ?>
    現在通常モードです。プレビューをするには、各バージョンのプレビューボタンをクリックしてください。
  <?php endif; ?>
</fieldset>
<fieldset style="width:35%;float:right;">
  <legend>ファイルをアップロード</legend>
  <!-- The data encoding type, enctype, MUST be specified as below -->
  <form enctype="multipart/form-data" action="" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
    <!-- MAX_FILE_SIZE (in bytes) must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
    <!-- Name of input element determines name in $_FILES array -->
    ファイルを選択: <input name="userfile" type="file" />
    <input type="submit" value="アップロード" />
  </form>
  <h3>アップロードについて</h3>
  <div style="font-size:smaller">
    アップロードされたファイルはまず「プレビュー」に表示されます。ここで内容を確認できます。
    またプレビューモードをオンにすれば、実際のウェブページで内容を確認できます。<br />
    ファイルを一つでもアップロードすれば、「公開」ボタンが表示され、プレビューの内容を公開できます。
  </div>
  <h3>注意</h3>
  <div style="font-size:smaller">
    ファイル名を厳密に守らないと反映されません。<br />
    <?php foreach($source_parameters as $key => $value): ?>
      "<?php echo $key ?>" のデータは "<?php echo $value['filename']?>"<br />
    <?php endforeach; ?>
    にしてください。
  </div>
</fieldset>
<fieldset>
  <!-- TODO: デモ専用につき、あとで削除 -->
  <legend>デモ専用のコントロール</legend>
  <form action="" method="POST" onsubmit="return confirm('本当にデータベースを初期化しますか?');">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]?>">
    <input type="hidden" name="drop_database" value="" />
    <input type="submit" value="データベースを初期化" />
  </form>

</fieldset>
<div style="clear:both"></div>
<?php include('footer.php') ?>
