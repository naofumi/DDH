<?php
  error_reporting(E_ERROR | E_PARSE);

  $suppress_reverse_proxy_requirement = true;
  require(dirname(__FILE__).'/jsonp.php');
  require(dirname(__FILE__).'/table_tagger.php');

  basic_auth();
  $parameters = $_GET;
  if ($_GET['submit']) {
    $table_tagger = new TableTagger($parameters['original_html']);
    $table_tagger->set_id_col($parameters['id_col']);
    $table_tagger->set_id_regex($parameters['id_regex']);

    for ($i=0; $i < count($_GET['fields']); $i++) { 
      $field = $_GET['fields'][$i];
      $col_num = $_GET['col_nums'][$i];
      if ($field && $col_num) {
        $table_tagger->set_tag_for_field_in_col($field, $col_num, true);
      }
    }

    // TODO: Pretty print the result using tidy
    //       The PHP library requires that we configure PHP for this.
    //       Instead, we will try simply using the command line `tidy` command.
    $tidy_result = array();
    exec("echo ".escapeshellarg($table_tagger->dom)." | tidy -i -wrap 1000 -raw -utf8", $tidy_result);
    $tidy_result = join("\n", $tidy_result);
    // $tidy_result = preg_replace('/<body>(.*)<\/body>/m', "$1", $tidy_result);
    $matches = array();
    preg_match('/<body>(.*)<\/body>/s', $tidy_result, $matches);
    $tidy_result = $matches[1];
  }

  include('header.php');
?>
<?php echo_flash(); ?>
<fieldset class="url_encoder">
  <legend>半自動的タグ作成</legend>
  <p>
    DDHのper-cell JSON embeddingを行う場合、データを埋め込む各箇所に"[id]_x_[price]"などのclassを付けなければならず、非常に手間がかかる。そこでこの半自動的タグ生成では、表のどの列を[id]とし、どの列を[price]とするかを指定すれば、自動的にタグを付けたHTMLを返してくれる。
    例えば[id]が1列目にあれば、「id記載の列」欄には"1"を、価格情報が4列目にあれば「フィールド」を"price"にし、「列番号」を"4"にする。
  </p>
  <form method="get">
    <fieldset>
      <legend>パラメータ</legend>
      <table>
        <tr>
          <th rowspan="2">id</th>
          <th>id記載の列</th>
          <td><input type="text" name="id_col" value="<?php echo $parameters['id_col'] ?>"/></td>
        </tr>
        <tr>
          <th>id制限Regex<br />(/cell-2/)</th>
          <td><input type="text" name="id_regex" value="<?php echo $parameters['id_regex'] ?>"/></td>
        </tr>
        <?php 
          for ($i=0; $i < 5; $i++) { 
            ?>
            <tr>
              <th rowspan="2"><?php echo $i + 1 ?></th>
              <th>フィールド</th>
              <td><input type="text" name="fields[]" value="<?php echo $parameters['fields'][$i] ?>"/></td>
            </tr>
            <tr>
              <th>列番号</th>
              <td><input type="text" name="col_nums[]" value="<?php echo $parameters['col_nums'][$i] ?>"/></td>
            </tr>
            <?php
          }
        ?>
      </table>
    </fieldset>

    <fieldset>
      <legend>元のHTML</legend>
      <label>HTMLをここにペースト</label><br />
      <textarea name="original_html" style="width: 600px;height: 6em;"><?php echo $parameters['original_html'] ?></textarea><br />
      <p>
        <input type=submit name="submit" value="タグを自動的に付ける"> | 
        <a href="?">Reset</a>
      </p>
    </fieldset>
  </form>
  <fieldset>
    <legend>新しいHTML</legend>
    <label>HTMLをここからコピー</label><br />
    <textarea name="result_html" style="width: 600px;height: 6em;" readonly><?php echo $tidy_result ?></textarea><br />
    <h4>プレビュー</h4>
    <div class="tagged_result">
      <?php echo $tidy_result ?>
    <div>
  </fieldset>
</fieldset>
<script>
(function(){

  var cells = document.getElementsByClassName('ddhcell');
  for (var i = 0; i < cells.length; i++) {
    var cell = cells[i];
    var tag = "";
    for (var j = 0; j < cell.classList.length; j++) {
      if (RegExp("_x_").test(cell.classList[j])) {
        tag = cell.classList[j] + " " + tag;
      }
    };
    cell.innerHTML = "<span style='color:red'>"+ tag + "</span>" + cell.innerHTML;
  };
})()

</script>
<?php include('footer.php') ?>
