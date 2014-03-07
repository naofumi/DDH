<?php
function double_convert($str, $encoding) {
  return mb_convert_encoding(mb_convert_encoding($str, $encoding, 'utf-8'), 'utf-8', $encoding);
}
$q = $_REQUEST['q'];
?>
<html>
<head>
  <meta charset="utf-8">
  <title>エンコーディング対応の確認</title>
  <style>
    *{
      font-family: serif;  
      font-size: 30px;
    }
    body{
      width: 600px;
      margin: 0 auto;
    }
    th {
      text-align:left;
      padding-right: 30px;
    }

  </style>
</head>
<body>
<h1>エンコーディング対応の確認</h1>
<form>
  <input type="text" name="q">
  <input type="submit" name="submit" value="対応結果を見る">
</form>
<table>
  <tr>
    <th>文字コード</th>
    <th>表示</th>
  </tr>
  <tr>
    <th>UTF-8:</th>
    <td><?php echo $q ?></td>
  </tr>
  <tr>
    <th>Shift JIS:</th>
    <td><?php echo double_convert($q, 'sjis') ?></td>
  </tr>
  <tr>
    <th>Shift JIS (Win-31J,CP932, mb 'sjis-win'):</th>
    <td><?php echo double_convert($q, 'sjis-win') ?></td>
  </tr>
  <tr>
    <th>EUC JP:</th>
    <td><?php echo double_convert($q, 'eucjp') ?></td>
  </tr>
  <tr>
    <th>EUC JP (mb 'eucjp-win'):</th>
    <td><?php echo double_convert($q, 'eucjp-win') ?></td>
  </tr>
</table>
<p>
エンコーディングに含まれていない記号を使いたい場合は HTML entitiesを使います。
<a href="http://ja.wikipedia.org/wiki/文字参照" target="_blank">Wikipediaのリスト</a>を参照
</p>
<p>
一般的なShift-JIS系は半角文字を記号化したもの、ヨーロッパ系アルファベットにかなり弱いので、そういうものが出てきたときには要注意。
例えば™(&amp;trade;), ®(&amp;reg), ©(&amp;copy;), µ (&amp;micro;)はどれもShift-JISが対応していない。
ちなみにx-mac-japaneseは™, ©などに対応するが、欧米エンコーディングとも違う非常に独特なコードで対応している。
<p>
<p>
なお"¥"については、ExcelはCSV出力(SJIS)するときは"\"として出力している。これはPHPのmb_convert_encodingではそのまま 
"<?php echo mb_convert_encoding("\\", "UTF-8", "sjis-win") ?>"になる。
</p>
</body>
</html>