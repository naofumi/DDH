# Server-side embedding of DDH

Embedding DDH information inside a supplier provided web-page has the advantage that the supplier maintains the freedom to manage the content of their web-page any way they want, using any technology they wish. DDH provides two ways of embedding information from CSV files. The first is JSONP-style embedding and the second is server-side embedding, which we discuss below.

Server-side embedding of DDH allows us to insert the DDH response inside the HTML source code. This allows Google-bots to see the content and is hence advantageous for SEO. With JSONP-style embedding, the DDH content is generally invisible to Google-bots.

The downside of server-side embedding is that it is more complicated to set up, and it requires more meddling with the target-page. It may also slow down page loading significantly depending on the network latency between the supplier's server and Castle104 server although this is unlikely to be an issue inside Japan.

Despite the downside, we generally recommend the server-side embedding approach if the supplier is using DDH to generate whole tables containing product information. These tables may contain keywords that we want Google-bot to index. On the other hand, JSONP is preferrable if we are only embedding prices and campaign info; stuff that we don't really need Google-bot to index.

If technical issues make sever-side embedding challenging however, then we use JSONP embedding.

We will provide server-side embedding code for a variety of platforms. Some will require extra charge.

## Server-side embedding as a replacement for reverse proxies

When we use DDH to generate pages (instead of embedding), the page is generated on Castle104 servers and hence the URL will have a `castle104.com` domain. To hide this domain, we can use reverse proxies which forward requests to the supplier's website to the DDH server on `ddh.castle104.com`. 

For Apache, this works very well. Apache allows you to configure a revese proxy within the `.htaccess` file, so configuration is as simple as uploading an `.htaccess` file via FTP.

For IIS, it is not nearly as simple. In fact, it is a potential road block. For IIS 5.1 and 6.0, you have to download a third-partly plugin. Even for IIS 7.5, you have to download an additional component from Microsoft. Either way is far from ideal.

Server-side embedding can achieve basically the same result as reverse proxies. Server-side embedding requires a page encoded in either PHP, ASP or similar to work, hence reverse proxies are easier to set up. If however, you cannot set up a reverse proxy, then server-side embedding is a good option.

## Server-side embedding in PHP

Use the `server_side_embed.php` file in the samples folder in the DDH library. Put this in a convenient location on the supplier's website and edit the file to add some configurations.

The target page should be edited to look like the following;
```php
<?php 
require_once(dirname(__FILE__)."/ddh_server_side_embed.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<title>Server side embed test</title>
<link href="../../css/main.css" rel="stylesheet" type="text/css">
</head>
<body>
    <?php echo server_side_embed("antibody_reactivity_type.php", array('type' => 'Whole IgG',
                                                                      'reactivity' => 'Bovine（ウシ）',
                                                                      'title' => "Anti-Bovine（ウシ） Whole IgG",
                                                                      'supplier' => 'Jackson'), 'SJIS') ?>
</body>
</html>
```

The `server_side_embed.php` file should be included in the `require_once` statement.

The actual embedding happens when `server_side_embed` is called. `server_side_embed` takes the endpoint as the first argument. The second argument is an associative array for the query. The third argument sets the encoding of the current source file. DDH responses are almost always in UTF-8, so `server_side_embed.php` has to convert this to match the encoding of the current page.

The `server_side_embed` function returns the body of the DDH response converted to the encoding in the third argument. Furthermore, if the DDH response contains `<!-- DDH Embed start -->` and `<-- DDH Embed end -->` tags,
then only the content sandwiched between these tags will be returned. This allows us to use the same endpoint PHP file for full-page DDH and server-side embedded DDH.

This approach works well if the supplier's website is static and it is running on Apache. This approach is also applicable to Wordpress or Joomla driven sites. In these cases, we would use a plugin (shortcodes) to embed the `server_side_embed` code and require the `ddh_server_side_embed.php` inside the plugin file.

### Server-side embedding in ASP classic websites

Classic ASP is not installed by default on IIS 7.5 and newer. On the other hand, ASP.NET (2.0 or higher) can be run on IIS 5.1 servers as long as the ASP.NET framework is available. Hence we should not really need classic ASP; providing ASP.NET files should be sufficient.

If we have to use classic ASP, this is possible. ASP classic has the functionality as shown in this [Stack Overflow answer](http://stackoverflow.com/a/1887522/592808).

### Server-side embedding in ASP.NET

We are preparing `.aspx` programs for server-side embedding. Note that ASP.NET can mess-up encoding. Read about ASP.NET encodings and recommendations in README-IIS.md.

