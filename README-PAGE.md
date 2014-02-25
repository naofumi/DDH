# Castle104 DDH によるページの作成

README.mdにはDDHを使って既存のウェブページに情報を埋め込む方法を紹介している。それとは別にDDHを使って新たにウェブページを作ることも可能である。

DDHというのは2つの側面を持っている。1つは任意のページに表を埋め込むことができる点。もう一つはCSVファイルでデータを管理できる点である。このウェブページを作る方法は、CSVファイルでデータを管理できる点に重きを置いた使い方である。

ウェブページを作る方法は埋め込みよりも簡単で、単にPHPからHTMLを書き出せば良い。しかし固有の問題もあるので、ここではその対処方法を紹介する。

## URLの問題

埋め込みをする場合はURLはエンドユーザからは隠れている（特別に探そうと思わなければ見つからない）。しかしウェブページを作る方法の場合、実際にそのページに飛ぶので、DDHのプログラムファイルが掲載されているウェブサイトのURLが表示される。例えば jsonp.castle104.com というドメインになっていたりする。

そこでApacheの`RewriteEngine`を使う。この[ブログ](http://www.slicksurface.com/blog/2008-11/use-apaches-htaccess-to-accomplish-cool-and-useful-tasks)に書いてあるように、`RewriteEngine`を使えば`.htaccess`ファイルからでもreverse proxy機能が設定できる。

例えば以下のように設定した場合を考える。ここでは以下を`.htaccess`に記述し、http://vendor.com/ のルートディレクトリーに置くとする。

  RewriteEngine On
  RewriteRule ^jsonp(.+)$ http://3643-jsonp.castle104.com/jsonp$1 [P,L]

そうすると http://vendor.com/jsonp/antibody_search.php などのURLにアクセスrが行われれば、reverse proxyが動いて http://3643-jsonp.castle104.com/jsonp/antibody_search.php にリクエストされるようになる。

このようにすれば http://3643-jsonp.castle104.com というドメインは完全に隠すことが可能になる。
