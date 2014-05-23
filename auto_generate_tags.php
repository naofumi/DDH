<?php
  require(dirname(__FILE__).'/jsonp.php');
  require(dirname(__FILE__).'/table_tagger.php');

  basic_auth();
  if ($_GET['submit']) {
    $parameters = $_GET;
    $table_tagger = new TableTagger($parameters['original_html']);
    $table_tagger->set_id_col($parameters['id_col']);
    $table_tagger->set_id_regex($parameters['id_regex']);

    for ($i=0; $i < count($_GET['fields']); $i++) { 
      $field = $_GET['fields'][$i];
      $col_num = $_GET['col_nums'][$i];
      if ($field && $col_num) {
        $table_tagger->set_tag_for_field_in_col($field, $col_num);
      }
    }

    // TODO: Pretty print the result using tidy
    //       The PHP library requires that we configure PHP for this.
    //       Instead, we will try simply using the command line `tidy` command.
    // $tidy_result = 
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
    <label>HTMLをここにペースト</label><br />
    <textarea name="fuck" style="width: 600px;height: 6em;"><?php echo $table_tagger->dom ?></textarea><br />
  </fieldset>
</fieldset>

<?php include('footer.php') ?>
