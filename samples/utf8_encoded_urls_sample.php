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
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?type=Fab&reactivity='.p_enc($react).'&title=Anti-'.p_enc($react).'%20Fab%20�t���O�����g�R�Ɖu�O���u����&supplier=Jackson" target="ab_list">Anti-'.$react.'</a>';
}
function reactivity_link_flow($react) {
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?application=FC&reactivity='.p_enc($react).'&title=Anti-'.p_enc($react).'%20�t���[�T�C�g���g���[�p2���R��&supplier=Jackson" target="ab_list">Anti-'.$react.'</a>';
}
function reactivity_application_link($react, $application) {
  return '<a href="../../ddh_jp/antibody_reactivity_type.php?application='.p_enc($application).'&FC&reactivity='.p_enc($react).'&title=Anti-'.urlencode($react).'%20'.urlencode($application).'�p�R��&supplier=Jackson" target="ab_list">Anti-'.$react.' '.$application.'</a>';
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<title>��䉻�w��i������Ёb���i���bJackson�� 2���R�̐��i</title>
<link href="../../css/main.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="container">
  <div id="headimg"><a href="../../index.html"><img src="../../images/common/rogo.jpg" alt="��䉻�w��i�������" width="300" height="39" border="0"></a></div>
  <div id="gnavi">
    <ul>
      <li><a href="../../company/index.html">��Јē�</a></li>
      <li><a href="../index.html">���i���</a></li>
      <li><a href="../../dealer/index.html">�戵���[�J�[</a></li>
      <li><a href="../../scientificsociety/index.html">�w��o�W���</a></li>
      <li><a href="../../catalog/index.html">��������</a></li>
    </ul>
  </div>
  <div class="clearfix" id="contentbase">
    <div id="pankuzunavi"><a href="../../index.html">Home</a> &gt; <a href="../index.html">���i���</a> &gt; Jackson�� 2���R�̐��i</div>
    <div id="productblock" class="jackson_2antibody">
      <h1>Jackson Immuno Research�� 2���R�̃J�^���O</h1>
      <div class="catchobx">
        <p class="progo"><img src="images/jackson_logo.gif" alt="Jackson logo" width="171" height="52" border="0"></p>
        <h2 class="imgcenter4"><img src="images/title.jpg" alt="2���R�̃J�^���O" width="550" height="178"></h2>
        <div class="btn_jacksoncatalog"><a href="pdf/use.pdf" target="_blank">�A�t�B�j�e�B�[�����񎟍R�̂̑I�ѕ��ƃJ�^���O�̎g����
        </a></div>
      </div>
      <h3><span class="titlebglight">2���R�̐��i</span></h3>
      <h4 class="catchcolor_pink"><a href="pdf/2antibody1.pdf" target="_blank">Whole IgG �R�Ɖu�O���u����</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_whole("Bovine�i�E�V�j") ?></li>
      	<li><?php echo reactivity_link_whole("Cat�i�l�R�j") ?></li>
      	<li><?php echo reactivity_link_whole("Chicken�i�j���g���j") ?></li>
      	<li><?php echo reactivity_link_whole("Dog�i�C�k�j") ?></li>
      	<li><?php echo reactivity_link_whole("Goat�i���M�j") ?></li>
      	<li><?php echo reactivity_link_whole("Guinea Pig�i�������b�g�j") ?></li>
      	<li><?php echo reactivity_link_whole("Syrian hamster�i�n���X�^�[�j") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_whole("Horse�i�E�}�j") ?></li>
      	<li><?php echo reactivity_link_whole("Human�i�q�g�j") ?></li>
      	<li><?php echo reactivity_link_whole("Mouse�i�}�E�X�j") ?></li>
      	<li><?php echo reactivity_link_whole("Rabbit�i�E�T�M�j") ?></li>
      	<li><?php echo reactivity_link_whole("Rat�i���b�g�j") ?></li>
      	<li><?php echo reactivity_link_whole("Sheep�i�q�c�W�j") ?></li>
      	<li><?php echo reactivity_link_whole("Swine�i�u�^�j") ?></li>
      </ul>
      <h4 class="catchcolor_green"><a href="pdf/2antibody9.pdf" target="_blank">F(ab')<sub>2</sub>�t���O�����g�R�Ɖu�O���u����</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fab2("Bovine�i�E�V�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Chicken�i�j���g���j") ?></li>
      	<li><?php echo reactivity_link_fab2("Goat�i���M�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Guinea Pig�i�������b�g�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Syrian hamster�i�n���X�^�[�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Horse�i�E�}�j") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fab2("Human�i�q�g�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Mouse�i�}�E�X�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Rabbit�i�E�T�M�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Rat�i���b�g�j") ?></li>
      	<li><?php echo reactivity_link_fab2("Sheep�i�q�c�W�j") ?></li>
      </ul>
      <h4 class="catchcolor_skyblue">Fab �t���O�����g�R�Ɖu�O���u����</h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fabmono("Goat�i���M�j") ?></li>
      	<li><?php echo reactivity_link_fabmono("Human�i�q�g�j") ?></li>
      	<li><?php echo reactivity_link_fabmono("Mouse�i�}�E�X�j") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_fabmono("Rabbit�i�E�T�M�j") ?></li>
      	<li><?php echo reactivity_link_fabmono("Rat�i���b�g�j") ?></li>
      	<li><?php echo reactivity_link_fabmono("Sheep�i�q�c�W�j") ?></li>
      </ul>
      
      <h4 class="catchcolor_gray"><a href="pdf/2antibody14.pdf" target="_blank">�t���[�T�C�g���g���[�p2���R��</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_flow("Chicken�i�j���g���j") ?></li>
      	<li><?php echo reactivity_link_flow("Goat�i���M�j") ?></li>
      	<li><?php echo reactivity_link_flow("Guinea Pig�i�������b�g�j") ?></li>
      	<li><?php echo reactivity_link_flow("Syrian hamster�i�n���X�^�[�j") ?></li>
      	<li><?php echo reactivity_link_flow("Human�i�q�g�j") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_link_flow("Mouse�i�}�E�X�j") ?></li>
      	<li><?php echo reactivity_link_flow("Rabbit�i�E�T�M�j") ?></li>
      	<li><?php echo reactivity_link_flow("Rat�i���b�g�j") ?></li>
      	<li><?php echo reactivity_link_flow("Sheep�i�q�c�W�j") ?></li>
      </ul>
      
      <h4 class="catchcolor_gray"><a href="pdf/2antibody17.pdf" target="_blank">���w�������iLM�j�p���R���C�h�W��2���R��</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Bovine�i�E�V�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Donkey�i���o�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Chicken�i�j���g���j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Dog�i�C�k�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Goat�i���M�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Guinea Pig�i�������b�g�j", "LM�i���w�������j") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Horse�i�E�}�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Human�i�q�g�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Mouse�i�}�E�X�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Rabbit�i�E�T�M�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Rat�i���b�g�j", "LM�i���w�������j") ?></li>
      	<li><?php echo reactivity_application_link("Sheep�i�q�c�W�j", "LM�i���w�������j") ?></li>
      </ul>
      <h4 class="catchcolor_gray"><a href="pdf/2antibody17.pdf" target="_blank">�d�q�������iEM�j�p���R���C�h�W��2���R��</a></h4>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Chicken�i�j���g���j", "EM�i�d�q�������j") ?></li>
      	<li><?php echo reactivity_application_link("Goat�i���M�j", "EM�i�d�q�������j") ?></li>
      	<li><?php echo reactivity_application_link("Guinea Pig�i�������b�g�j", "EM�i�d�q�������j") ?></li>
      	<li><?php echo reactivity_application_link("Human�i�q�g�j", "EM�i�d�q�������j") ?></li>
      </ul>
      <ul class="leftfloat6">
      	<li><?php echo reactivity_application_link("Mouse�i�}�E�X�j", "EM�i�d�q�������j") ?></li>
      	<li><?php echo reactivity_application_link("Rabbit�i�E�T�M�j", "EM�i�d�q�������j") ?></li>
      	<li><?php echo reactivity_application_link("Rat�i���b�g�j", "EM�i�d�q�������j") ?></li>
      	<li><?php echo reactivity_application_link("Sheep�i�q�c�W�j", "EM�i�d�q�������j") ?></li>
      </ul>
      
      <h3><span class="titlebglight">���̑��R�̐��i</span></h3>
      <ul>
        <li><a href="pdf/2antibody18.pdf" target="_blank">Anti-Digoxin �i�R�W�S�L�V���j</a></li>
        <li><a href="pdf/2antibody18.pdf" target="_blank">Anti-Biotin �i�R�r�I�`���j</a></li>
        <li><a href="pdf/2antibody19.pdf" target="_blank">Anti-Fluorescein(FITC)�i�R�t���I���Z�C���j</a></li>
        <li><a href="pdf/2antibody19.pdf" target="_blank">Anti-Horseradish Peroxidase (HRP)
          �i�R�y���I�L�V�_�[�[�j</a></li>
      </ul>
      <h3><span class="titlebglight">���̑��֘A���i</span></h3>
      <ul>
        <li><a href="pdf/2antibody20.pdf" target="_blank">Purified Immunoglobulins �i�����C���m�O���u�����j</a></li>
        <li><a href="pdf/2antibody22.pdf" target="_blank">Normal Serums and Gamma Globulins �i���파���A�K���}�O���u�����j</a></li>
        <li><a href="pdf/2antibody22.pdf" target="_blank">Streptavidin �i�X�g���v�g�A�r�W���j</a></li>
        <li><a href="pdf/2antibody23.pdf" target="_blank">Bovine Serum Albumin�iIgG-Free, Protease-Free�j �i�E�V�����A���u�~���j</a></li>
        <li><a href="pdf/2antibody23.pdf" target="_blank">Peroxidase-Anti-Peroxidase�iPAP�j Immune Complexes<br>
          �i �y���I�L�V�_�[�[�E�R�y���I�L�V�_�[�[�n���Ɖu�����́j</a></li>
      </ul>
      <div class="catalog_jackson clearfix">
        <div class="imgfloatright2"><img src="images/2antibody.jpg" alt="" width="120" height="172"></div>
        <div class="leftfloat">
          <h4>���W���N�\����2���R�̃J�^���O�D�]�z�z���ł���</h4>
          <p>���E�L���̂Q���R�̃��C���i�b�v�����W���N�\���Ђ�<br>���{��J�^���O���쐬���܂����B
            ���񂲗��������B<br><br>
            �J�^���O�̂�������<a href="../../catalog/index.html">������</a>����</p>
        </div>
      </div>
      <div class="inquiry">
        <h3>���i�Ɋւ��邨�₢���킹</h3>
        <p>����T�|�[�g�O���[�v�@[TEL] 03-3864-1431 [FAX] 03-3864-1497</p>
      </div>
      <p class="textalign-center09em"><a href="../index.html">��䉻�w��i ���i���g�b�v�y�[�W</a></p>
    </div>
    <div id="footernavi"><a href="../../privacy/index.html">�v���C�o�V�[�|���V�[</a> | <a href="../../sitemap/index.html">�T�C�g�}�b�v</a> | <a href="../../contactus/index.html">���₢���킹</a></div>
  </div>
  <div id="copyright">
    <p>All Rights resarved, Copyright(C) 2000-2010, IWAI CHEMICALS COMPANY<br>
      ����Web Site���Ɍf�ڂ̕��́E�ʐ^�̖��f�]�ڂ��ւ��܂��B</p>
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
