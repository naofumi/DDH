# Castle104 DDH

## Outline

DDH (Dokodemo Hyo: どこでも表) allows suppliers to easily take all the product information that they possess in Excel spreadsheets, and put it onto their web sites.

DDH provides multiple ways to put the information onto the web pages.

1. Embedding on the browser via JSONP
2. Embedding on the server
3. Generating new pages (we recommend using reverse proxies to make them look as if they are coming from the supplier's domain)

## Notes

Some quick notes that should be inserted into the proper documentation afterwards.

### Admin pages

Admin pages should be accessed directly and not through reverse proxy setups. Hence they should set;

```
$suppress_reverse_proxy_requirement = true;
```

This is already done for you if you require basic authentication with `basic_auth()`.

A proper reverse proxy would probably work so we should be able to access admin pages via reverse proxy on an Apache setup. However, this would be a problem on IIS setups. Safer to just allow direct access.

## Server Setup

The current DDH code has been tested on the following.
1. PHP: 5.5.9
2. OS: Ubuntu 14.04 Trusty
3. MongoDB: 3.4.23

## Documentation

Documenation is available on this file and also in the Wiki pages on BitBucket. Additional documentation may be available in the DDH implementation folder.

## Admin 

To access the administration screen for the MongoDB version, point to `ddh/snapshots.php`.

## 概要

Castle104 JSONPはCSVファイルの情報をJSONPに類似の方法で、任意のウェブサイトに埋め込む仕組みである。

なお、埋め込まずに新たなウェブページを作る使い方もあるので、そっちはREADME-PAGE.mdを参照。

現在C104JSONPでは2種類の埋め込み方を用意している。

1. **表を丸ごと埋め込む方法 :** 例えば一定の書式の価格表があるときにはこの方法が便利である。製品IDを設定するだけで、いろいろな情報をまとめた表が作成できる。
2. **任意のセルに一つの情報を埋め込む方法 :** これは一定の書式に表があるわけは無く、ウェブページの任意の箇所に特定の情報、例えば製品IDの価格とか製品IDのサイズなどを個別に埋め込むのに使う。

## 設定

設定は`config.php`に記載する。データソースであるCSVの場所、エンコーディング、フィールドの名前の他、管理画面のパスワード、タイムゾーン、キャッシュなども設定する。詳しくはconfig.phpのコメント参照。

また`class DataRow extends DataRowBase`の箇所でDataRow classを記述するより、データの表示のされ方をカスタマイズできる。例えばCSVファイルのデータを元に新しいフィールドを計算することができるし、書式を変更することができる。例えば通常価格とキャンペーン価格を元にキャンペーンメッセージを書いたり、あるいはキャンペーン期間中だけキャンペーン価格を表示したりということは、この`DataRow class`で記載する。

## 表の丸ごと埋め込みの仕組み

丸ごと埋め込みの場合は、表の書式に合わせてPHPのファイルを用意する。例えば価格表を表示したい場合はprice_table.php、キャンペーン付きの価格表が欲しい場合はprice_table_with_camp.php、在庫を表示したい場合はprice_table_with_inventory.phpなど、それぞれの用途に合わせる。

手順としては以下のようになる；

1. `tests/test_page.html` に呼び出し用のscript tagを書く。
2. 必要なphpファイルを作りながら、`test_page.html`の結果を確認する。
3. 必要であれば、jsonpソースのページをアクセスするときに`test_jsonp`のパラメータをつければ、JSONPにする前の、挿入されるそのままのHTMLを確認することができる。これは途中でechoなどを使ってデバッグするのに便利。

## 個別埋め込みの仕組み

個別埋め込みの場合は、データを埋め込みたいDOM elementに`c104jsonp`というclassを目印として付け、さらに`[製品ID]_x_[フィールド]`のclassを付けて、表示させたい内容を示す。PHPファイルはとしては`cell.php`を使う。PHPファイルのカスタマイズは必要ない。

またCSVファイルのフィールドそのままでは目的の表示ができないことがある。例えば複数のフィールドを複合的に計算して、新たなフィールドを作りたい場合がある。これは`config.php`の`DataRow class`定義で記載する。

手順としては以下のようになる；

1. `tests/test_page.html` に目的のデータを埋め込むDOM elementを作り、適切なclassを付ける。
2. 複合的に計算されたフィールドが必要であれば、`DataRow class`定義を書き換える。
3. デバッグが必要なときは、Web Inspectorを使って`cell.php`の呼び出し及び帰ってきたレスポンスを確認すると良い。

## データのアップロード

データのアップロードは以下の方法を提供する。

1. ウェブサイトにアップロードする方法
2. FTP

ウェブサイトにアップロードする方法では、データのプレビューなどを確認したり、前のバージョンへのrollbackなどができるので圧倒的にこれがお勧め。

データ保存用のフォルダは`current`の他、`preview`と`previous`を用意する。`preview`のファイルの中身は、URLに`&preview=1`を付けると確認できる。データをアップロードし、liveにする前に確認したい場合にはこれは有効である。

## 文字エンコード

`config.php`にはCSVファイルで使われている文字エンコーディングを記載する。

PHPの`json_encode()`はUnicode以外には対応しないため、PHPの内部エンコーディングはUTF-8を使用する。CSVファイルはすべてUTF-8にいったん変換されて、内部で処理される。

Castle104 JSONPのレスポンスはすべてASCIIである。UTF-8文字列はすべてPHPの`json_encode()`によりエスケープされるので、レスポンスそのもののエンコーディングは問題にならない。したがって埋め込み先ウェブページの文字エンコーディングが何であっても、そのままで問題なく対応できる。

## CSVファイルの書式

製品IDは必ずCSVファイルの最も左の列でなければならない。

UNIXのgrepとかsedを使うため、CSVファイルは必ず`\n`を改行記号に持っている必要がある。したがってMac版のMicrosoft OfficeのCSVファイル出力は使えない。

Windows版のMicrosoft OfficeでCSVファイルを出力した場合、文字エンコーディングは`SJIS`になる。これは`config.php`に記載する。

## キャッシュ

レスポンススピードの向上とサーバ負担の軽減のために、Castle104 JSONPではレスポンスをキャッシュしている。キャッシュの期間はデフォルトで3600秒に設定されているので、1時間経つとキャッシュは更新される。またCSVファイルの更新日時も確認し、更新されたものがあればキャッシュは更新される。

`config.php`でキャッシュのon/offとキャッシュの期間が設定できる。

開発中などはキャッシュをoffにしたいことが多いだろう。

## Javascript offへの対応

Castle104 JSONPはJavascriptがオフだと動作しない。つまり価格などの情報が表示されない。

Javascriptがオフのユーザに対してメッセージを与える目的で、埋め込み先のウェブページで`<noscript>`タグを使ってメッセージを表示するべきである。例えば埋め込む`<script>`タグの上に`<noscript>`タグを作り、この中にメッセージを書き込む。

## APIs

### 表を丸ごと埋め込む

表を丸ごと埋め込む場合は以下のタグを埋め込む。

    <div id="price_table_1"></div>
    <script>!function(d,s,h,id,ep,ids){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https',jsid=id+"-js",pv=/[?&]preview=/.test(location.search)?'&pv=1':'';if(!d.getElementById(jsid)){js=d.createElement(s);js.id=jsid;js.src=p+"://"+h+ep+"?ids="+ids+"&loc="+id+pv;js.setAttribute('async', 'true');fjs.parentNode.insertBefore(js,fjs)}}
    (document,"script","localhost:8890","price_table_1","/jsonp/price_table.php","201234,201235,201236,201237,201238,201239,201240,201241,201242")</script>

* `localhost:8890`はJSONPサーバのhost:port
* `price_table_1`は価格表が挿入されるDOM要素のID
* `/jsonp/price_table.php`は価格表を生成するエンドポイント
* `201234`は価格表に挿入される製品の製品ID

`price_table_1`に挿入される表のHTMLは`/jsonp/price_table.php`が作成する。価格表の細かい書式はウェブサイトによって異なることが多いので、このPHPファイルは埋め込み先ウェブサイトごとにカスタムで作成する。

TODO: 今のところ、製品IDは`script`タグの方に書いている。しかし埋め込み先の`price_table_1`に変えた方が良い。こうすると`script`タグの中身は多くのページで完全に共有されるので、例えばDreamWeaverのライブラリー機能で埋め込むことが簡単になるし、Wordpressのshortcodeも書きやすくなる。`data-ids`のようなattributeを使うのが良いと思う。

### 個別埋め込み（DOM elementに特定の情報を埋め込む）

この場合は大きく分けて2つの要素をHTMLに埋め込む。1つは埋め込み先のDOM elementに特別なclassを追加し、どういう情報をそこに埋め込むかを示すもの。もう一つはこのようなDOM elementのための情報を一括してサーバに要求するJavascriptである。

#### DOM elementにつける特別なclass

DOM elementには`class="12345_x_price c104jsonp"`のようなclassを付ける。この場合は製品ID `12345`のフィールド `price`の情報がこのDOM elementのinnerHTMLに埋め込まれる。

`c104jsonp`は個別埋め込み先のDOM elementの標識で、これが無いと埋め込みは行われない。`c104jsonp`の他に、別のclass名を使うこともできる（後述のJavascriptのパラメータを変更することで可能）。

`12345`は製品ID。多くの場合、これは製品番号と同じである。HTMLのスペックによると[classはスペース以外の文字なら何を含んでも良い](http://stackoverflow.com/questions/2617170/what-characters-are-widely-supported-in-css-class-names)となっている。製品番号にスペースが含まれることはまず無いので、製品番号をそのままIDに使用して良い。

蛇足ではあるが、CSS class selectorを使う場合にはエスケープしなければならない場合が発生する。つまりJavascriptのdocument.getElementsByClassName()ではそのままclass名が使えるが、document.querySelectorAll()の場合はエスケープしなければならないケースが出てくる。詳しくは[こちら](http://mathiasbynens.be/notes/css-escapes)。

#### 個別埋め込みのためのJavascript

埋め込み先のDOM elementを探すのにdocument.getElementsByClassNameを使っているが、これはIE8などではサポートされていないので、polyfillをしている。

    <!-- Castle104 JSONP START -->
    <script>
    /* IE polyfill */ if(!document.getElementsByClassName){document.getElementsByClassName=function(cn){var es=document.getElementsByTagName("*"),p=new RegExp("(^|\\s)"+cn+"(\\s|$)"),r=[];for(i=0;i<es.length;i++){if(p.test(es[i].className)){r.push(es[i])}}return r}}
    !function(d,s,h,id,ep,cl){var es=d.getElementsByClassName(cl),rs=[];for(var i=0;i<es.length;i++){var e=es[i],cs=e.getAttribute('class').split(' ');for(var j=0;j<cs.length;j++){if (cs[j].indexOf('_x_')!=-1) {rs.push(cs[j]);}}};var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https',jsid=id+"-js",pv=/[?&]preview=/.test(location.search)?'&pv=1':'';if(!d.getElementById(jsid)){js=d.createElement(s);js.id=jsid;js.src=p+"://"+h+ep+"?reqs="+rs+pv;js.setAttribute('async', 'true');fjs.parentNode.insertBefore(js,fjs);}}
    (document,"script","192.168.0.22:8890","cell-1","/gls_jsonp/ddh/cell.php","c104jsonp");</script>
    <!-- Castle104 JSONP END -->

* `localhost:8890`はJSONPサーバのhost:port
* `cell-1`はJavascriptタグのID
* `/jsonp/cell.php`はJSONPを生成するエンドポイント。ほとんど変えることはない。
* `c104jsonp`は置換されるDOM要素の標識となるclass名

## 性能

### サーバプログラムの性能

Castle104 JSONPではセットアップと保守、さらにデータアップロードのシンプルさを重視したので、データベースは一切使っていない。代わりにCSVファイルをその都度全部読み込んでいる。

いくつかの最適化の結果、データベースを使わなくても十分なスピードが得られるようになった。我々のテストでは、例えば900,000の製品価格があっても、900msで製品価格を検索できる。通常のメーカーの製品数は1万点以下なので10ms以下で製品価格が検索可能なはずである。

さらにキャッシュを導入することで、頻繁にリクエストされるものについては超高速で結果を返すようになっている。

### 呼び出しのJavascriptの性能

呼び出しのJavascriptはnon-blockingになるように（つまり埋め込み先ウェブページの表示速度に一切影響を与えないように）工夫している。またjQueryなどのframeworkに依存しないように作ってあるので、どのようなウェブサイトにも使える。

[参考](http://stackoverflow.com/questions/1834077/which-browsers-support-script-async-async)

## データの確認

埋め込み先ウェブサイトのURLに`&preview=1`を付けるとプレビューができる。プレビューの特徴は以下の通り；

1. `preview`フォルダのデータが表示される。したがって公開直前のデータがちゃんとCastle104 JSONPに正しく認識されているかどうかを確認できる。
2. 個別埋め込みの場合、個々のデータがどの製品IDのどのフィールドから来ているかが確認できる。これを見れば、埋め込み先DOM elementのclass名を正しく設定したかどうかが簡単に確認できる。

## ウェブサイトごとの具体的な設置方法

### 静的HTMLで作ったウェブサイト

個々のHTMLファイルに埋め込みコードを直接書き込む。`<script>`の部分はDreamWeaverのライブラリー機能などを使って入力の省力化を行うのが良いだろう。

### Wordpressで作ったウェブサイト

WordpressのようなCMSは、ダイレクトに`<script>`を入れられないことが多い。Wordpressの場合は簡単なshortcodeを使ったpluginを作り、埋め込むことができる。

### Drupal, Joomlaなどで作ったウェブサイト

Wordpress同様に、カスタムのプラグインを作り、`<script>`を埋め込む。

### その他のオンラインカタログシステム

個別に確認しながら`<script>`を埋め込む方法を検討する。

## ワークフロー

新規にC104 JSONPを導入する際には以下の作業が必要になる。

1. 元のウェブサイトを確認し、価格表の種類を確認する。何回も使われている価格表の書式があるならば、「表の丸ごと埋め込み」で対応するのが適切なので、CSVファイルで情報が得られるかどうかを確認しながら、なるべく「表の丸ごと埋め込み」で対応するように薦める。
2. 静的HTML以外のCMSを使うのであれば、そのCMSにJavascriptを埋め込む方法を確認し、必要ならばプラグインを開発する。
3. キャンペーンや在庫の表示の仕方を確認する。特にキャンペーンの場合、普通のカタログページの価格表での表示の仕方と、キャンペーンページでの表示の仕方が変わる可能性がある。それに合わせて、fieldなどの設計をする。
4. タグ埋め込み作業を行うために、使用するエディタの便利な機能（snippetなど）を準備する。
5. 「表丸ごと埋め込み」のendpointのphpファイルをプログラミングする。
6. 静的HTMLファイルのタグ付けを行う。

