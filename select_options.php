<?php
  $suppress_reverse_proxy_requirement = true;
  require(dirname(__FILE__).'/jsonp.php');

  authenticate();

  $source_id = $_GET['source_id'];
  $updated_at = $_GET['updated_at'];
  $field = $_GET['field'];
  $filter = $_GET['filter'];
  $filter_value = $_GET['filter_value'];

  $fields = $source_parameters[$source_id]['fields'];

  $data_source = new MongoDBDataSource($source_parameters, 'current');
  $collection = $data_source->db->$source_id;
  $cursor = $collection->find(['updated_at' => (int)$updated_at]);

  // Go through all rows and count occurences of each value
  $row_value_count_in_source = array();
  if(isset($field)) {
    $index = 1;
    foreach ($cursor as $id => $value) {
      $row = $value['row'];
      if (isset($row[$field])) {
        $value = $row[$field];
      } else {
        $value = null;
      }
      if (isset($filter) && isset($row[$filter]) && 
          !preg_match("/".$_GET['filter_value']."/i", $row[$filter])) {
        continue;
      }
      if (isset($row_value_count_in_source[$value])) {
        $row_value_count_in_source[$value] += 1;
      } else {
        $row_value_count_in_source[$value] = 1;
      }
      $index++;
    }    
  }
  uksort($row_value_count_in_source, "strnatcmp");

  include('header.php');
?>
<?php echo_flash(); ?>
<h1>
  "<?php echo $source_id ?>" (<?php echo $updated_at ? date("Y-m-d H:i:s", $updated_at) : "準備中" ?> バージョン) ファイルの分析
</h1>
<fieldset>
  <legend>config/field_values.phpの分析</legend>
  <p>
    特殊な処理をしたいfieldについては、field_values.phpにフィールド値を列挙してある。その値が現在のCSVファイルとマッチしているかどうかを確認し、品質確認する必要がある。
  </p>
  <p>
    「該当タグがない値」の中に、本当は選択できるようにしたい値があれば、field_values.phpを書き換えるか、もしくは元のCSVファイルを変更する。例えばCSVファイルの中で"マウス"と書いたり"ﾏｳｽ"と書いてしまっていたりしているのに、field_values.phpでは"マウス"のみを登録していると、「該当タグがない値」のところに"ﾏｳｽ"が出てしまう。<br>
    「該当値がないタグ」はselect_tagなどに表示されないので、特に問題にはならないが、field_values.phpがわかりにくくなるので、もう使わないタグは外しておいたほうが良いだろう。
  </p>
  <table style="width: 90%">
    <tr>
      <th>フィールド</th>
      <th>該当タグがない値</th>
      <th>該当値がないタグ</th>
    </tr>
  <? 
    $fields_to_analyse = array_intersect($fields, array_keys($field_settings));
    foreach($fields_to_analyse as $field_to_analyse) {
      $counts_for_field = $data_source->counts_for_field_of_source($field_to_analyse, $source_id);
      ?>
      <tr>
        <th><?= $field_to_analyse ?></th>
        <td><?= join(", ", $counts_for_field['uncaptured_values']) ?></td>
        <td>
          <?
            $tags_in_settings = tags_for_field($field_to_analyse);
            $values_in_result = array_keys(array_filter($counts_for_field['result'], function($a) {return $a != 0;}));
            $unused_tags = array_diff($tags_in_settings, $values_in_result);
          ?>
          <?= join(", ", $unused_tags) ?>
        </td>
      </tr>
      <?
    }
  ?>
  </table>
  <!-- counts_for_field_of_source -->
</fieldset>
<fieldset>
  <legend>フィールド値の分析</legend>
  <p>
  DDHではフィールドの値そのものを使って検索を行うので、CSVファイルの中でどのようなフィールドが使われているかを知る必要がある。ここではそれを調べる。
  </p>
  <p>ファイル名、調べるフィールドを選択すると、そのフィールドの中で使われているすべての値、そして出現回数が表示される。またFilterを使って、対象を絞り込むこともできる（小文字大文字を無視した正規表現）。
  </p>
  <fieldset>
    <legend>対象ファイル、フィールド</legend>
    <form method="get">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"] ?>">
      <input type="hidden" name="source_id" value="<?php echo $source_id ?>">
      <input type="hidden" name="updated_at" value="<?php echo $updated_at ?>">
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
    <tr>
      <td>
        <table>
          <?php foreach($row_value_count_in_source as $key => $value): ?>
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
      </td>
      <td>
        <strong>php array</strong>
        <pre style="border:solid 1px black;padding:5px;">
[
<?php foreach ($row_value_count_in_source as $key => $value): ?>
  <?php echo "\"$key\" => null, // count $value</br>" ?>
<?php endforeach; ?>
]
        </pre>
      </td>
    </tr>
  </table>
</fieldset>
<?php include('footer.php') ?>
