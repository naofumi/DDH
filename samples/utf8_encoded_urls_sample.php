<?php
// This is an example of a PHP function we could use to assist generation of UTF8 encoded URLs
// contained in a Shift-JIS encoded web page.

function p_enc($string) {
  $char_encoded = mb_convert_encoding($string, 'UTF-8', 'SJIS');
  return urlencode($char_encoded);
}

function reactivity_link_whole($react) {
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?type=Whole%20IgG&reactivity='.p_enc($react).'&title=Anti-'.p_enc($react).'%20Whole%20IgG&supplier=Jackson" target="ab_list">Anti-'.$react.'</a>';
//  return '<a href="../../ddh_jp/antibody_reactivity_type.php?type=Whole%20IgG&reactivity='.urlencode($react).'&title=Anti-'.urlencode($react).'%20Whole%20IgG&supplier=Jackson" target="ab_list">Anti-'.$react.'</a>';
}
function reactivity_link_fab2($react) {
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?type=F%28ab%27%292&reactivity='.p_enc($react).'&title=Anti-'.p_enc($react).'%20F%28ab%27%292&supplier=Jackson" target="ab_list">Anti-'.$react.'</a>';
}
function reactivity_link_fabmono($react) {
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?type=Fab&reactivity='.p_enc($react).'&title=Anti-'.p_enc($react).'%20Fab%20フラグメント抗免疫グロブリン&supplier=Jackson" target="ab_list">Anti-'.$react.'</a>';
}
function reactivity_link_flow($react) {
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?application=FC&reactivity='.p_enc($react).'&title=Anti-'.p_enc($react).'%20フローサイトメトリー用2次抗体&supplier=Jackson" target="ab_list">Anti-'.$react.'</a>';
}
function reactivity_application_link($react, $application) {
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?application='.p_enc($application).'&FC&reactivity='.p_enc($react).'&title=Anti-'.urlencode($react).'%20'.urlencode($application).'用抗体&supplier=Jackson" target="ab_list">Anti-'.$react.' '.$application.'</a>';
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<title>岩井化学薬品株式会社｜製品情報｜Jackson社 2次抗体製品</title>
<link href="../../css/main.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="container">
  <div id="headimg"><a href="../../index.html"><img src="../../images/common/rogo.jpg" alt="岩井化学薬品株式会社" width="300" height="39" border="0"></a></div>
  <div id="gnavi">
    <ul>
      <li><a href="../../company/index.html">会社案内</a></li>
      <li><a href="../index.html">商品情報</a></li>
      <li><a href="../../dealer/index.html">取扱メーカー</a></li>
      <li><a href="../../scientificsociety/index.html">学会出展情報</a></li>
      <li><a href="../../catalog/index.html">資料請求</a></li>
    </ul>
  </div>
  <div class="clearfix" id="contentbase">
    <div id="pankuzunavi"><a href="../../index.html">Home</a> &gt; <a href="../index.html">商品情報</a> &gt; Jackson社 2次抗体製品</div>
    <div id="productblock" class="jackson_2antibody">
      <h1>Jackson Immuno Research社 2次抗体カタログ</h1>
      <div class="catchobx">
        <p class="progo"><img src="images/jackson_logo.gif" alt="Jackson logo" width="171" height="52" border="0"></p>
        <h2 class="imgcenter4"><img src="images/title.jpg" alt="2次抗体カタログ" width="550" height="178"></h2>
        <div class="btn_jacksoncatalog"><a href="pdf/use.pdf" target="_blank">アフィニティー精製二次抗体の選び方とカタログの使い方
        </a></div>
      </div>
      <h3><span class="titlebglight">2次抗体製品</span></h3>
      <h4 class="catchcolor_pink"><a href="pdf/2antibody1.pdf" target="_blank">Whole IgG 抗免疫グロブリン</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_whole("Bovine（ウシ）") ?></li>
      	<li><?php echo reactivity_link_whole("Cat（ネコ）") ?></li>
      	<li><?php echo reactivity_link_whole("Chicken（ニワトリ）") ?></li>
      	<li><?php echo reactivity_link_whole("Dog（イヌ）") ?></li>
      	<li><?php echo reactivity_link_whole("Goat（ヤギ）") ?></li>
      	<li><?php echo reactivity_link_whole("Guinea Pig（モルモット）") ?></li>
      	<li><?php echo reactivity_link_whole("Syrian hamster（ハムスター）") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_whole("Horse（ウマ）") ?></li>
      	<li><?php echo reactivity_link_whole("Human（ヒト）") ?></li>
      	<li><?php echo reactivity_link_whole("Mouse（マウス）") ?></li>
      	<li><?php echo reactivity_link_whole("Rabbit（ウサギ）") ?></li>
      	<li><?php echo reactivity_link_whole("Rat（ラット）") ?></li>
      	<li><?php echo reactivity_link_whole("Sheep（ヒツジ）") ?></li>
      	<li><?php echo reactivity_link_whole("Swine（ブタ）") ?></li>
      </ul>
      <h4 class="catchcolor_green"><a href="pdf/2antibody9.pdf" target="_blank">F(ab')<sub>2</sub>フラグメント抗免疫グロブリン</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fab2("Bovine（ウシ）") ?></li>
      	<li><?php echo reactivity_link_fab2("Chicken（ニワトリ）") ?></li>
      	<li><?php echo reactivity_link_fab2("Goat（ヤギ）") ?></li>
      	<li><?php echo reactivity_link_fab2("Guinea Pig（モルモット）") ?></li>
      	<li><?php echo reactivity_link_fab2("Syrian hamster（ハムスター）") ?></li>
      	<li><?php echo reactivity_link_fab2("Horse（ウマ）") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fab2("Human（ヒト）") ?></li>
      	<li><?php echo reactivity_link_fab2("Mouse（マウス）") ?></li>
      	<li><?php echo reactivity_link_fab2("Rabbit（ウサギ）") ?></li>
      	<li><?php echo reactivity_link_fab2("Rat（ラット）") ?></li>
      	<li><?php echo reactivity_link_fab2("Sheep（ヒツジ）") ?></li>
      </ul>
      <h4 class="catchcolor_skyblue">Fab フラグメント抗免疫グロブリン</h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fabmono("Goat（ヤギ）") ?></li>
      	<li><?php echo reactivity_link_fabmono("Human（ヒト）") ?></li>
      	<li><?php echo reactivity_link_fabmono("Mouse（マウス）") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fabmono("Rabbit（ウサギ）") ?></li>
      	<li><?php echo reactivity_link_fabmono("Rat（ラット）") ?></li>
      	<li><?php echo reactivity_link_fabmono("Sheep（ヒツジ）") ?></li>
      </ul>
      
      <h4 class="catchcolor_gray"><a href="pdf/2antibody14.pdf" target="_blank">フローサイトメトリー用2次抗体</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_flow("Chicken（ニワトリ）") ?></li>
      	<li><?php echo reactivity_link_flow("Goat（ヤギ）") ?></li>
      	<li><?php echo reactivity_link_flow("Guinea Pig（モルモット）") ?></li>
      	<li><?php echo reactivity_link_flow("Syrian hamster（ハムスター）") ?></li>
      	<li><?php echo reactivity_link_flow("Human（ヒト）") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_flow("Mouse（マウス）") ?></li>
      	<li><?php echo reactivity_link_flow("Rabbit（ウサギ）") ?></li>
      	<li><?php echo reactivity_link_flow("Rat（ラット）") ?></li>
      	<li><?php echo reactivity_link_flow("Sheep（ヒツジ）") ?></li>
      </ul>
      
      <h4 class="catchcolor_gray"><a href="pdf/2antibody17.pdf" target="_blank">光学顕微鏡（LM）用金コロイド標識2次抗体</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Bovine（ウシ）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Donkey（ロバ）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Chicken（ニワトリ）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Dog（イヌ）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Goat（ヤギ）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Guinea Pig（モルモット）", "LM（光学顕微鏡）") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Horse（ウマ）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Human（ヒト）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Mouse（マウス）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Rabbit（ウサギ）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Rat（ラット）", "LM（光学顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Sheep（ヒツジ）", "LM（光学顕微鏡）") ?></li>
      </ul>
      <h4 class="catchcolor_gray"><a href="pdf/2antibody17.pdf" target="_blank">電子顕微鏡（EM）用金コロイド標識2次抗体</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Chicken（ニワトリ）", "EM（電子顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Goat（ヤギ）", "EM（電子顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Guinea Pig（モルモット）", "EM（電子顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Human（ヒト）", "EM（電子顕微鏡）") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Mouse（マウス）", "EM（電子顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Rabbit（ウサギ）", "EM（電子顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Rat（ラット）", "EM（電子顕微鏡）") ?></li>
      	<li><?php echo reactivity_application_link("Sheep（ヒツジ）", "EM（電子顕微鏡）") ?></li>
      </ul>
      
      <h3><span class="titlebglight">その他抗体製品</span></h3>
      <ul>
        <li><a href="pdf/2antibody18.pdf" target="_blank">Anti-Digoxin （抗ジゴキシン）</a></li>
        <li><a href="pdf/2antibody18.pdf" target="_blank">Anti-Biotin （抗ビオチン）</a></li>
        <li><a href="pdf/2antibody19.pdf" target="_blank">Anti-Fluorescein(FITC)（抗フルオレセイン）</a></li>
        <li><a href="pdf/2antibody19.pdf" target="_blank">Anti-Horseradish Peroxidase (HRP)
          （抗ペルオキシダーゼ）</a></li>
      </ul>
      <h3><span class="titlebglight">その他関連製品</span></h3>
      <ul>
        <li><a href="pdf/2antibody20.pdf" target="_blank">Purified Immunoglobulins （精製イムノグロブリン）</a></li>
        <li><a href="pdf/2antibody22.pdf" target="_blank">Normal Serums and Gamma Globulins （正常血清、ガンマグロブリン）</a></li>
        <li><a href="pdf/2antibody22.pdf" target="_blank">Streptavidin （ストレプトアビジン）</a></li>
        <li><a href="pdf/2antibody23.pdf" target="_blank">Bovine Serum Albumin（IgG-Free, Protease-Free） （ウシ血清アルブミン）</a></li>
        <li><a href="pdf/2antibody23.pdf" target="_blank">Peroxidase-Anti-Peroxidase（PAP） Immune Complexes<br>
          （ ペルオキシダーゼ・抗ペルオキシダーゼ可溶性免疫複合体）</a></li>
      </ul>
      <div class="catalog_jackson clearfix">
        <div class="imgfloatright2"><img src="images/2antibody.jpg" alt="" width="120" height="172"></div>
        <div class="leftfloat">
          <h4>＜ジャクソン社2次抗体カタログ好評配布中です＞</h4>
          <p>世界有数の２次抗体ラインナップをもつジャクソン社の<br>日本語カタログを作成しました。
            是非ご覧下さい。<br><br>
            カタログのご請求は<a href="../../catalog/index.html">こちら</a>から</p>
        </div>
      </div>
      <div class="inquiry">
        <h3>製品に関するお問い合わせ</h3>
        <p>試薬サポートグループ　[TEL] 03-3864-1431 [FAX] 03-3864-1497</p>
      </div>
      <p class="textalign-center09em"><a href="../index.html">岩井化学薬品 製品情報トップページ</a></p>
    </div>
    <div id="footernavi"><a href="../../privacy/index.html">プライバシーポリシー</a> | <a href="../../sitemap/index.html">サイトマップ</a> | <a href="../../contactus/index.html">お問い合わせ</a></div>
  </div>
  <div id="copyright">
    <p>All Rights resarved, Copyright(C) 2000-2010, IWAI CHEMICALS COMPANY<br>
      弊社Web Site内に掲載の文章・写真の無断転載を禁じます。</p>
  </div>
</div>
</body>
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-311533-1";
urchinTracker();
</script>
</html>
