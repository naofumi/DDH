<?php
  $suppress_reverse_proxy_requirement = true;
  require(dirname(__FILE__).'/jsonp.php');

  authenticate();
  if ($_GET['param_to_url']) {
    $parameters = $_GET;

    if ($_GET['server'] != 'iis') {
      $encoded_url = "/ddh_jp/".$_GET['endpoint'];
    } else {
      $encoded_url = "/ddh_jp/reverse_proxy.aspx?ep=".$_GET['endpoint'];
    }

    for ($i=0; $i < count($_GET['symbols']); $i++) { 
      $symbol = $_GET['symbols'][$i];
      if (strlen($symbol) > 0) {
        if ($i == 0 && ($_GET['server'] != 'iis'))
          $encoded_url .= "?";
        else
          $encoded_url .= "&";

        $encoded_value = urlencode($_GET['values'][$i]);

        $encoded_url .= $symbol."=".$encoded_value;
      }
    }
  } else if ($_GET['url_to_param']) {
    $parsed_url = parse_url($_GET['encoded_url']);
    $path = $parsed_url['path'];
    $query = $parsed_url['query'];
    $parameters = array();
    $parameters['symbols'] = array();
    $parameters['values'] = array();
    parse_str($query, $query_list);
    foreach ($query_list as $symbol => $value) {
      array_push($parameters['symbols'], $symbol);
      array_push($parameters['values'], $value);
    }
    if (preg_match("/ddh_jp/", $path)) {
      $encoded_url = $_GET['encoded_url'];
      $parameters['endpoint'] = $path;
    } else {
      $encoded_url = $_GET['encoded_url'];
      $parameters['endpoint'] = "ERROR: not a DDH path.";
    }
  }

  include('header.php');
?>
<?php echo_flash(); ?>
<fieldset class="url_encoder">
  <legend>DDH用URLの変換</legend>
  <p>
    DDHサーバに問い合わせをするためのURLはUTF-8文字エンコーディングされた上でURLエンコーディング(%エンコーディング)されている必要があります。元のウェブページがUTF-8で作成されている場合は必ずしも必要ありませんが、特に元のウェブページがShift-JISやEUC-JPの場合は必須です。そこでこのページを使って変換を行います。
  </p>
  <form method="get">
    <fieldset>
      <legend>サーバ</legend>
      <input type="radio" value="iis" name="server" id="is_iis" <?php echo $_GET['server'] == 'iis' ? 'checked' : '' ?>>
      <label for="is_iis">IIS</label>
      <input type="radio" value="apache" name="server" id="is_apache" <?php echo $_GET['server'] != 'iis' ? 'checked' : '' ?>>
      <label for="is_apache">Apache</label>
    </fieldset>
    <fieldset>
      <legend>パラメータ</legend>
      <table>
        <tr>
          <th colspan="2">エンドポイント</th>
          <td><input type="text" name="endpoint" value="<?php echo $parameters['endpoint'] ?>"/></td>
        </tr>
        <?php 
          for ($i=0; $i < 5; $i++) { 
            ?>
            <tr>
              <th rowspan="2"><?php echo $i + 1 ?></th>
              <th>symbol</th>
              <td><input type="text" name="symbols[]" value="<?php echo $parameters['symbols'][$i] ?>"/></td>
            </tr>
            <tr>
              <th>value</th>
              <td><input type="text" name="values[]" value="<?php echo $parameters['values'][$i] ?>"/></td>
            </tr>
            <?php
          }
        ?>
      </table>
      <p>
        <input type=submit name="param_to_url" value="パラメータからURLへ変換"> | 
        <a href="?">Reset</a>
      </p>
    </fieldset>

    <fieldset>
      <legend>URL</legend>
      <label>エンコード後</label><br />
      <textarea name="encoded_url" style="width: 600px;height: 6em;"><?php echo $encoded_url ?></textarea><br />
      <a href="<?php echo $encoded_url ?>" target="_blank">Test generated URL</a>
      <p>
        <input type=submit name="url_to_param" value="URLからパラメータへ変換"> | 
        <a href="?">Reset</a>
      </p>
    </fieldset>
  </form>
</fieldset>

<?php include('footer.php') ?>
