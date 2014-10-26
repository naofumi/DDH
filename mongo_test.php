<?php
  $suppress_reverse_proxy_requirement = true;
  require(dirname(__FILE__).'/jsonp.php');
  require_once ('../mongo_antibody.php');
  // $query = ['host' => 'Bovine(ｳｼ)', 'label' => "Alexa 488"];
  $query = $_GET;

  // $data_source = new AntibodyQueryDataSource($source_parameters, $query, 'jackson_second');
  $data_source = new MongoDBAntibodyQueryDataSource($source_parameters, $query, 'jackson_second', preview_version());

  // $data_source->drop_database();
  // $data_source->publish_new_sources();

  cache_start($data_source);

  $data_source->set_facet_fields(array('reactivity', 'label', 'host', 'form', 'target', 'kyushu', 'multi_label', 
                                               'for_flow_cytometry', 'for_eikyu_funyu', 'for_fluorecent_wb', 'price'));
  $facets = $data_source->facets();

  $data_source->add_rowspans();

  odd_even_reset();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <link href="http://www.iwai-chem.co.jp/css/main.css" rel="stylesheet" type="text/css">
  <style>
  .product_list, 
  .product_list td, 
  .product_list th {
    border: solid 1px #999;
    font-size: small;
    text-align: center;
  }
  .product_detail {
    font-size:xx-small;
    line-height:1.5em;
    text-align:left;
    padding-left:5px;
  }
  .product_detail > b {
    color:#404792;
    font-size: x-small;
  }
  .product_detail.toxic > b {
    color: red;
  }
  .product_list {
    width: 100%;
  }
  .product_list a {
    color:#404792;
  }
  .product_list a:visited {
    color:#404792;
  }
  .query_form {
    border: solid 1px #999;
    padding: 15px;
  }
  .query_menu {
    display: block;
  }
  .query_menu label {
    display: inline-block;
    width: 200px;
  }
  .preview {
    background-color: pink;
  }
  .notice {
    border: solid 2px orange;
  }
  .label_amca, .label_dylight_405 {
    background-color: lightblue !important;
  }
  .label_cy2, .label_dylight_488, .label_alexa_488, .label_fluorescein_fitc_, .label_fluorescein_dtaf_ {
    background-color: lightgreen !important;
  }
  .label_dylight_549, .label_cy3, .label_rhodamine_tritc_, .label_r_phycoerythrin_pe_ {
    background-color: lightyellow !important;
  }
  .label_dylight_594, .label_alexa_594, .label_texas_red, .label_rhodamine_red_x {
    background-color: orange !important;
  }

  .label_alexa_647, .label_alexa_680, .label_dylight_649, .label_cy5, 
  .label_percp, .label_allophycocyanin_apc_, .label_alexa_790{
    background-color: red !important;
  }
  .odd {
    background-color: #EEE;
  }
  .even, .odd *[rowspan] {
    background-color: #FFF;
  }
  <?php if (is_preview()): ?>
  body {border: solid 2px red !important;}
  <?php endif; ?>
  </style>
  <title>二次抗体検索</title>
</head>
<body>
  <?php include('header.php'); ?>
  <h3>二次抗体検索</h3>
  <form method="get" class="query_form" accept-charset="UTF-8">
    <div class="query_menu">
      <label for="cat_no">カタログ番号 <span style="font-size:11px">(xxx-xxx-xxx)</span></label>
      <?php echo text_field("cat_no") ?>
    </div>
    <div class="query_menu">
      <label for="reactivity">交差動物種</label>
      <?php if ($data_source->total_rows()): ?>
        <?php select_tag_with_facet('reactivity', $facets['reactivity']) ?>
      <?php else: ?>
        <?php select_tag('reactivity', $data_source->field_values('reactivity')) ?>
      <?php endif; ?>
    </div>
    <div class="query_menu">
      <label for="label">標識</label>
      <?php if ($data_source->total_rows()): ?>
        <?php $label_facets = $facets['label'] ?>
        <?php uksort($label_facets, "label_sort_for_menu") ?>
        <?php select_tag_with_facet('label', $label_facets) ?>
      <?php else: ?>
        <?php $label_options = $data_source->field_values('label') ?>
        <?php usort($label_options, "label_sort_for_menu") ?>
        <?php // http://flowcyt.salk.edu/fluo.html ?>
        <?php select_tag('label', $label_options) ?>
      <?php endif; ?>
    </div>
    <div class="query_menu">
      <label for="host">宿主動物種（種由来）</label>
      <?php if ($data_source->total_rows()): ?>
        <?php select_tag_with_facet('host', $facets['host']) ?>
      <?php else: ?>
        <?php select_tag('host', $data_source->field_values('host')) ?>
      <?php endif; ?>
    </div>
    <div class="query_menu">
      <label for="type">形態</label>
      <?php if ($data_source->total_rows()): ?>
        <?php select_tag_with_facet('form', $facets['form']) ?>
      <?php else: ?>
        <?php select_tag('form', $data_source->field_values('form'), null, 'Whole IgG') ?>
      <?php endif; ?>
    </div>
    <div class="query_menu">
      <label for="type">ターゲット (クラス)</label>
      <?php if ($data_source->total_rows()): ?>
        <?php select_tag_with_facet('target', $facets['target']) ?>
      <?php else: ?>
        <?php select_tag('target', $data_source->field_values('target'), null, 'IgG (H+L)') ?>
      <?php endif; ?>
    </div>
    <div class="query_menu">
      <label for="type">価格</label>
      <?php select_tag_with_ranged_facet('price', $data_source->field_values('price'), $facets['price'], null) ?>
    </div>
    <div class="query_menu">
      <label for="kyushu">吸収処理済品のみ</label>
      <input type="checkbox" name="kyushu" value="any" <?php if (isset($_GET['kyushu']) && $_GET['kyushu']) {echo "checked";} ?>>
      <?php if ($data_source->total_rows()): ?>
        (<?php if (isset($facets['kyushu']['any'])) {echo $facets['kyushu']['any'];} else {echo 0;}?>)
      <?php endif; ?>
    </div>
    <div class="query_menu">
      <label for="multi_label">多重染色適用品のみ</label>
      <input type="checkbox" name="multi_label" value="yes" <?php if (isset($_GET['multi_label']) && $_GET['multi_label']) {echo "checked";} ?>>
      <?php if ($data_source->total_rows()): ?>
        (<?php if (isset($facets['multi_label']['yes'])) {echo $facets['multi_label']['yes'];} else {echo 0;}?>)
      <?php endif; ?>
    </div>
    <div style="text-align:right">
      <button type="button" onclick="window.location='antibody_search.php'" style="font-size:20px">クリア</button>
      <button type="submit" name="submit" value="検索" style="font-size:20px">&nbsp;検索&nbsp;</button><br />
      <a href="http://www.iwai-chem.co.jp/products/jackson/index.html" style="font-size:smaller;">Jackson社 2次抗体製品一覧に戻る</a>
    </div>
  </form>
  <div class="price clearfix">
    <br />
    <h4>検索結果</h4>
    <?php if (count(array_keys($_GET)) == 0): ?>
      <div style="border: 1px solid #BBB;padding: 30px;margin-bottom:50px;">
        検索条件を入力してください。
      </div>
    <?php else: ?>
      <table class="product_list <?php echo is_preview() ? 'preview' : '' ?>">
        <tr class="odd">
          <th style="width:80px">交差性</th>
          <th>形態</th>
          <th style="width:80px">標識</th>
          <th>ターゲット</th>
          <th style="width:80px">宿主</th>
          <th>製品名</th>
          <th>容量</th>
          <th style="width: 100px">Cat.No.<br />価格</th>
        </tr>
        <?php foreach($data_source->rows() as $row): ?>
          <tr class="<?php echo odd_even() ?>">
            <?php echo $row->td('reactivity_line_break', array(), 'reactivity'); ?>
            <?php echo $row->td('form'); ?>
            <?php echo $row->td('label_line_breakable', array("class" => dasherize("label_".$row->get('label'))), 'label'); ?>
            <?php echo $row->td('target'); ?>
            <?php echo $row->td('host_line_break', array(),'host'); ?>
            <?php echo $row->td('name_with_kyushu', array('class' => 'Pname')); ?>
            <?php echo $row->td('size'); ?>
            <td><?php echo $row->get('cat_no') ?><br /><a href="https://www.jacksonimmuno.com/technical/spec-sheets" style="font-size:x-small" target="jackson">Datasheet</a><br /><?php echo $row->get('display_price'); ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>

  </div>
  <?php include('footer.php'); ?>

</body>
</html>

<?php
  cache_end();
