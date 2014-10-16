<?php
  $suppress_reverse_proxy_requirement = true;
  require(dirname(__FILE__).'/jsonp.php');

  $preview_directory = dirname(__FILE__).'/../data/preview/';
  if (!file_exists($preview_directory) || !is_writable($preview_directory))
    die("$preview_directory must be available and writable by Apache.");
  $current_directory = dirname(__FILE__).'/../data/current/';
  if (!file_exists($current_directory) || !is_writable($current_directory))
    die("$current_directory must be available and writable by Apache.");
  
  $directories = array('preview' => $preview_directory, 'current' => $current_directory);

  basic_auth();

  if (isset($_GET['status'])) {
    $directory = $directories[$_GET['status']];
  }

  if (isset($_GET['file'])) {
    $file_identifier = preg_replace('/\.\./', '', $_GET['file']);
    $file = $directory.$file_identifier;

    foreach($source_parameters as $key => $value) {
      if ($value['filename'] == basename($file)) {
        $fields = $value['fields'];
        $encoding = $value['encoding'];
        $delimiter = isset($value['delimiter']) ? $value['delimiter'] : ",";
      }
    }    
  }
    // $handle = popen("$iconv_path --from-code $encoding --to-code UTF-8//IGNORE//TRANSLIT $source | LANG_ALL=UTF-8 $gnugrep_path -i -P $escaped_regexp", "r");

  $iconv_path = $GLOBALS["iconv_path"];
  if (!$iconv_path) {
    die ('$iconv_path is not set in config.php');
  }
  $result = array();
  if (isset($_GET['field'])) {
    $fh = popen("$iconv_path --from-code $encoding --to-code UTF-8//IGNORE//TRANSLIT $file", "r");
    // $fh = fopen($file, "r");
    $position = array_search($_GET['field'], $fields);
    $filter_position = array_search($_GET['filter'], $fields);
    if ($fh) {
      $index = 1;
      while ($line = fgets($fh)) {
        $row = str_getcsv($line, $delimiter);
        if (isset($row[$position])) {
          $value = $row[$position];
        } else {
          $value = null;
        }
        if (isset($_GET['filter_value']) && isset($row[$filter_position]) && 
            !preg_match("/".$_GET['filter_value']."/i", $row[$filter_position])) {
          continue;
        }
        if (isset($result[$value])) {
          $result[$value] = $result[$value] + 1;
        } else {
          $result[$value] = 1;
        }
        $index++;
      }
    }
  }


  include('header.php');
?>
<?php echo_flash(); ?>
<fieldset>
  <legend>全選択肢を調べるファイル</legend>
  <p>
  DDHではフィールドの値そのものを使って検索を行うので、CSVファイルの中でどのようなフィールドが使われているかを知る必要がある。ここではそれを調べる。
  </p>
  <p>ファイル名、調べるフィールドを選択すると、そのフィールドの中で使われているすべての値、そして出現回数が表示される。またFilterを使って、対象を絞り込むこともできる（小文字大文字を無視した正規表現）。
  </p>
  <fieldset>
    <legend>対象ファイル、フィールド</legend>
    <form method="get">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"] ?>">
      <div>
        <label for="status">preview or current</label>
        <?php select_tag('status', ['preview', 'current']) ?>
      </div>
      <div>
        <?php if (isset($directory)): ?>
          <label for="file">ファイル</label>
          <?php select_tag('file', scandir($directory)) ?>
        <?php endif; ?>
      </div>
      <div>
        <?php if (isset($fields)): ?>
          <label for="field">field_symbol</label>
          <?php select_tag('field', $fields) ?>
        <?php endif; ?>
      </div>
      <div>
        <?php if (isset($fields)): ?>
          <label for="Filter">Filter</label>
          <?php select_tag('filter', $fields) ?>
          <?php text_field('filter_value') ?>
        <?php endif; ?>
      </div>
      <div style="text-align:right">
        <button type="button" onclick="window.location='select_options.php'">クリア</button>
        <input type="submit" name="submit" value="検索" />
      </div>
    </form>
  </fieldset>
</fieldset>
<fieldset>
  <label>結果</label>
  <table>
    <?php foreach($result as $key => $value): ?>
      <tr>
        <td>
          <?php echo $key ?>
        </td>
        <td>
          <?php echo $value ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</fieldset>
<?php include('footer.php') ?>
