# Getting DDH to work with IIS

My memos on working with IIS can be found from the `Notes on Working with IIS` section.

Here I present a summary of the current solution.

## Setting up the IIS server

1. Configure IIS to allow execution of `.aspx` scripts. This means allowing ASP.NET to run. ASP.NET may be either 2.0 or 4.0. We have tested with both. You can do this via "コントロールパネル" > "管理ツール" > "インターネット インフォーメーション サービス", then right-clicking on the web application to select properties, and then on the "virtual directory" settings, set the application execution settings to "script only".
2. Copy the `App_Code` folder from the `IIS Samples` folder in DDH library. Place this in the root-level of the web application folder. This contains the common classes that will be used in all `.aspx` files.
3. Copy the `ddh_jp` folder from the `IIS Samples` folder in DDH library. This folder includes the `reverse_proxy.aspx` file that redirects responses to the DDH server whilst hiding the DDH server URL. This also contains `ddh.min.js` which is responsible for triggering JSONP embedding.
4. Edit `reverse_proxy.aspx` to point to the DDH server for this particular client.

## Change the URLs for DDH-generated pages

Change all URLs for DDH-generated pages (in the files with links to these pages) and DDH server-side embedding (in the files in which embedding will occur). The Apache versions typically looked like `/ddh_jp/[endpoint]?[params]`. The IIS versions should look like `/ddh_jp/reverse_proxy.aspx?ep=[endpoint]&[params]`

In this setup, the URL for the implementation server is set only in `reverse_proxy.aspx` and is totally invisible to the browser.

## Creating `.aspx` ASP.NET files

If you want to embed some ASP.NET code into your static `.html` files, do the following.

1. Resave the `.html` file to UTF-8 with BOM. It is possible to use a different encoding, but it requires careful extra configuration and is not recommended.
2. Change the file extension to `.aspx`
3. Change the `<meta>` tag charset to something like `<meta charset=utf-8>`.
4. At the top of the `.aspx` file, add a `Page` directive like `<%@ Page language="VB" codePage=65001 %>`.
5. Add ASP.NET code.

### DDH-generated pages link example

```VB
<%@ Page language="VB" codePage=65001 %>
'...
<head>
<meta charset=utf-8>
'...
<% Dim d As New Ddh(Page, Server) %>
<%= d.link("Bovine（ウシ）", "antibody_reactivity_type.php", _
                             New String(,) {{"type", "Whole IgG"}, _
                                            {"reactivity", "Bovine（ウシ）"}, _
                                            {"title", "Bovine Whole IgG secondary-antibodies"}}) %>
```

### Server-side embedding example

```VB
<%@ Page language="VB" codePage=65001 %>
'...
<head>
<meta charset=utf-8>
'...
<% Dim d As New Ddh(Page, Server) %>
<%= d.ServerSideEmbed("price_table.php",
                      New String(,) {{"ids", "5010-21840,5010-21841,5010-21842"}}) %>
```

In both examples, we first create an instance of the `Ddh` class. The `Ddh` class is where we store the DDH-related helpers. This uses the `Page` and `Server` instances so we provide them as arguments.

Another thing to note is that Visual Basic does not have array literals. We create arrays using a special syntax that makes it easier to create arrays. 

### Using `.aspx` pages that output SJIS

It is possible to create `.aspx` pages that output SJIS. This is useful in cases where the rest of the website is in SJIS, and the webmaster insists that all pages have the same encoding.

1. Resave the `.html` file to UTF-8 with BOM. Even when ASP.NET should output in SJIS, ASP.NET will first convert all page content to internal Unicode. It is possible to use a different encoding, but it is safer to write the code in UTF-8 from the beginning.
2. Specify that we will output SJIS in the `Page` directive. This is the same as `responseEncoding` in `web.config` > `globalization` or `Page.ResponseEncoding = "sjis"` with the difference that you can use strings like "sjis" instead of "932".

```VB
<%@ Page language="VB" codePage=932 %>
'...
<head>
<meta charset=shift-jis>
'...
```

Note that you can also set `requestEncoding` in ASP.NET which specifies how ASP.NET will decode parameters sent to it via GET and POST requests. The default is set to UTF-8. In DDH (and in Ruby on Rails as well), we require that all parameters are sent in UTF-8 so the default is perfectly OK.

### Creating `.aspx` files in SJIS

We might want our `.aspx` files to be in SJIS. This might be the case if the website is standardized on SHIFT-JIS with all having `<meta charset=shift-jis>` set. BBEdit by default automatically synchronizes the meta tag to the file encoding; it changes the charset to `<meta charset=utf-8>` when we save a file as UTF-8. If this is the case, we might want to save files in SJIS.

This is however, not the recommended solution.

If this is the case, we have to set this in `web.config`. `web.config` affects all subdirectories. Below is an example.

```xml
<?xml version="1.0" encoding="utf-8"?>
<configuration>
  <system.web>
    <globalization fileEncoding="shift-jis"/>
  </system.web>
</configuration>
```

The default for IIS is "shift-jis" (or at least the Windows version of shift-jis), so in most cases, we leave is at it is.


---------
# Notes on Working with IIS

Working with IIS has proven to be a huge challenge for me, very much due to the fact that I don't have much experience with Microsoft web technologies.

## Code that assists coding of UTF-8 encoded URLs in links

`link_table.asp`, `link_table.aspx`, `index.asp` and `index.aspx` are works in progress to get this done.

For `.asp` I prefer to use Javascript as the language since it is concise and I am more familar with it. Futhermore, VBScript lacks a hash structure and a dynamically sized array which both really hurt (and the Dictionary object isn't a good replacement).

As for `.aspx`, you can use nicer data structures like `ArrayLists` and `SortedLists` which help a lot. The syntax is rather unfamiliar for me, but I don't expect C# to be better in this regard. For me, I struggle with type safety.

With both `.asp` and `.aspx`, managing the output character encoding is done through the `.CodePage` property. With `.asp`, `.CodePage` is available on the `Session` and `Response` objects. With `.aspx`, `.CodePage` is available on the `Page` object.

In `.asp`, the source file encoding is managed through the `CODEPAGE=65001` attribute on the `<%@ language="VBScript" CODEPAGE=65001 %>` directive at the top of the page. In `.aspx` however, although you can set a `CodePage` directive in the same location, it has a different meaning. In `.aspx` this directive only affects the output encoding and does not affect source file encoding.

To manage source file encoding for `.aspx`, you have to create and edit a `web.config` file which you have to add to the virtual directory ([source](http://stackoverflow.com/questions/10572314/do-i-need-web-config-for-non-ascii-characters)). Another way to do it if your files are UTF-8, is to set the BOM. The problem with setting the `web.config` file is that you are no longer setting the encoding in the source file, and so you cannot mix SJIS source files and UTF-8 source files.

This is a serious problem that we have to be aware of. I don't have a good idea for fixing this (we don't want to really go setting BOM for all the pages that we have to work on).

Below is an example of the `web.config` file.

```xml
<?xml version="1.0" encoding="utf-8"?>
<configuration>
  <system.web>
    <globalization fileEncoding="utf-8"/>
  </system.web>
</configuration>
```

## Reverse Proxy

IIS does not have good support for reverse proxies. Even with the IIS 7.5, you have to install plugins and it is not a simple configuration like with Apache and `.htaccess`. Hence we will probably use the work that we did with `server_side_embed.php`, and do reverse proxies by extending server-side embedding. What we do is we will create a `ddh_jp.aspx` file which sends off an HTTP request to the DDH server, and replaces the whole HTML page with the response. Since we don't really need to work with cookies and stuff, we won't bother with the HTTP headers. To make it a really good substitute for a reverse proxy though, we should replicate the response headers from the DDH response to those on `ddh_js.aspx`.

I'm not sure if we can do `server_side_embed` for classic ASP. I have gotten a simple version running on ASP.NET.

### Install a reverse proxy with IIS 5.1, IIS 6.0

Microsoft does not provide plugins to allow reverse proxies for IIS 5.1 and IIS 6.0. They only provide these for IIS 7.5 and above. For these servers, we can use [Ionic's Isapi Rewrite Filter](http://iirf.codeplex.com/). A writeup of how to use this filter is provided [here](http://mabushiisign.hatenablog.jp/entry/2013/05/05/094725).

I have confirmed that this filter works on IIS 5.1 on Windows XP, but I seriously doubt that any sane IT administrator would allow me to install this on their machines. Instead, we use the server-side embed mechanism for IIS 5.1 and IIS 6.0.

### Install a reverse proxy with IIS 7.5

Even with IIS 7.5, it seems that we need to adjust the configuration of the IIS 7.5 server to allow for a reverse proxy. I plan to check this in the near future.

## The many versions of IIS and ASP

There are quite a few versions of IIS which do not seem to share the same capabilities (whereas with Apache, my experience is that you can mostly assume any Apache to have the same set of features). The feature that we are currently interested in is a reverse proxy. Rewriting is also a potential issue. A report of the market share of each version is given [here](http://w3techs.com/technologies/details/ws-microsoftiis/all/all). It seems that we can mostly assume that servers will be running version 6 or above. IIS 5.1 is only 1.2%. As a sidenote, for Apache, only 3.7% use Apache 1 and the vast majority (96.3%) use Apache 2.

As for ASP, the versions that we are concerned with are classic ASP, ASP.NET 2.0 and ASP.NET 4.0. I think that the way to think about these is that IIS versions == Apache versions and ASP versions == PHP versions. What I mean is that IIS and ASP are relatively independent. You can run ASP.NET 4.0 on IIS 5.1 and you can run classic ASP on IIS 7.5.

If you look at the properties for the virtual directory and inside 「アプリケーションの設定」 > 「構成」, you can see how the file extensions are mapped to the various `.dll` files. For example on the DELL tower with Win XP, `.asp` and `.asa` are mapped to `D:¥WINDOWS¥System32¥inetsrv¥asp.dll` and `.aspx` and `.asax` are mapped to `D:¥WINDOWS¥Microsoft.NET¥Framework¥v2.0.50727¥aspnet_isapi.dll`. Stuff like `.shtml` is mapped to `D:¥WINDOWS¥System32¥inetsrv¥ssinc.dll`. This is very much like how we map stuff in Apache. If you have installed .NET 4.0 (the "full" and not the "client" version) on your server, the properties panel allow you to switch the ASP.NET version and I expect that this will change the version number in the mappings for `.aspx` and `.asax`.

As I understand it, ASP.NET 4.0 is backwards compatible with ASP.NET 2.0. However, classic ASP and ASP.NET 2.0 are not compatible. `.asp` files cannot be simply modified to `.aspx` files. Moreover, we are hugely unlikely to find a IIS site that can run classic ASP but not ASP.NET 2.0. On the other hand, IIS 7.5 cannot run classic ASP out of the box (you need to download a separate file). Hence, to allow IIS sites to run server-side DDH features (like server-side embedding), it is sufficient to provide the libraries and instructions for ASP.NET 2.0. We will test on both ASP.NET 2.0 and 4.0.

## Configuration files

Apache allows per-directory configuration with `.htaccess` files. You can configure many Apache options as well as PHP options. You can also configure PHP settings inside of the PHP source code. Not all settings are available, but you can do quite a lot. This means that you can configure a lot your environment by simply FTP uploading the `.htaccess` files.

With IIS, it seems that configuration is done per-application (not per-directory) and the configurations are done through the IIS administration control panel. Hence configuration requires admin access.

The only configuration that you can do, I think, is configuring the ASP.NET environment by way of the `web.config` file at the root of the application. You may also configure ASP.NET through `global.asax` at the root, but this requires a restart of the server.

## Testing environment



## `web.config`

ASP.NET applications use the `web.config` file at the root level of the application to set some configurations (the name and location of the file can be changed). The `web.config` overrides server-wide settings set elsewhere. The configuration options are described [here](http://msdn.microsoft.com/en-us/library/b5ysx397(v=vs.85).aspx).

`web.config` can also be placed in sub-directories to scope to only that directory and its decendants. You can also use a `location` directive to scope settings (["ASP.NET 文字セットの異なるページからPOSTを受付けるには"](http://kametaro.wordpress.com/2008/06/12/asp-net-文字セットの異なるページからpostを受付けるに/)).

```xml
<configuration>
  <location path="external/kame.aspx">
    <system.web>
      <globalization requestEncoding="shift-jis" responseEncoding="shift-jis" />
    </system.web>
  </location>
</configuration>
```

Currently, the only option that I think is important is the `fileEncoding` setting in the `globalization` element ([documentation](http://msdn.microsoft.com/ja-jp/library/hy4kkhe0(v=vs.100).aspx)). 

> Specifies the default encoding for .aspx, .asmx, and .asax file parsing. Unicode and UTF-8 files that are saved with the byte order mark prefix are automatically recognized, regardless of the value for this attribute.

What this means is that if the `.aspx` source code files are encoded in EUC-JP, then we need to set this in `web.config`. Otherwise, ASP.NET will not recognize this. This is not a problem in PHP-Apache because it sends the source code file as is to the browser; that is if the source code file is in EUC-JP, then it will be sent to the browser as EUC-JP. It is the responsibility of the coder to ensure that the response headers and the meta-tag specify EUC-JP so that the browser doesn't get confused. However with ASP.NET, the server will convert the source code file from `fileEncoding` to `responseEncoding` (default UTF-8) and send the result to the browser. Hence if the `fileEncoding` is screwed, the response is screwed.

In general, we should only edit the root `web.config` if we are the only ASP.NET application inside the application folder. If the web site has `.aspx` files other than the DDH-related ones that we prepare, then changing the `web.config` configuration will change the original applications behavior.

Another thing to bear in mind is that using multiple encodings within the site is bound to complicate. The maintainers may often forget what encoding should be used for which portions of the site.

More on this below.

## How ASP.NET handles encodings

ASP.NET encoding handling is rather complicated, mainly because it is very different from how classic ASP and PHP handle encodings.

The only documentation I could find that sufficiently explains that stuff is [here](http://quickstart.developerfusion.co.uk/QuickStart/aspnet/doc/localization/encoding.aspx) (It is apparently taken from "Microsoft .NET Framework SDK QuickStart Tutorials Version 2.0").

> Like other components in the .NET Framework ASP.NET processes strings internally as Unicode, more specifically its 16-bit encoding form UTF-16. Most web protocols are byte-based though which is why requests from and responses to browsers are converted to the byte-based form of Unicode UTF-8 by default.

This states that ASP.NET internally processes strings as Unicode. It also says that the default for ASP.NET is to handle requests and responses in UTF-8 (both `responseEncoding` and `requestEncoding` are UTF-8 by default). (Note that `requestEncoding` will be [overridden](http://msdn.microsoft.com/en-us/library/hy4kkhe0(v=vs.85).aspx) by the request header's `Accept-Charset` if available. This can be set in form tags with the `accept-charset` attribute but not for normal links.)

> These configuration settings except for fileEncoding can also be set in the @ Page declaration. The reason of course is that the directive is in the page file and the file encoding has to be known when the page is begin to be read. 

This specifically mentions that the `fileEncoding` setting cannot be set in the `@ Page` declaration inside each page. Therefore, the `fileEncoding` setting is applied application-wide (in the `web.config` file at the root of the application); you cannot change `fileEncoding` for each file independently. This article does however forgets to mention that UTF-8 or UTF-16 encoded files with a BOM signature will be treated as such regardless of the `fileEncoding` setting.

> If no fileEncoding declaration is available the ASP.NET runtime is determining the file encoding by detecting any Unicode signatures at the begin of the file and is using these to distinguish between UTF-8 and UTF-16 encoded pages. Unicode signatures are added by Visual Studio and Notepad automatically when saving a file as UTF-16, Notepad also adds a signature for UTF-8 and it can be specified in Visual Studio. If no signature is present the runtime will interpret the source file in the current system ANSI codepage of the system the page is run on. The recommendation is to always save files in Unicode with a signature. 

If no `fileEncoding` declaration is set and no BOM is available, then the source file will be interpreted as the current system ANSI codepage of the system. For Japanese systems, this will be SJIS. Another way of saying this is that the default `fileEncoding` setting is SJIS.

I'll give some examples that I ran;

Without a `fileEncoding` declaration (the default for `fileEncoding` is SJIS);

1. Source files encoded in Shift-JIS and with no `@ Page codePage` declaration will be internally converted successfully to UTF-8. The response body will be sent as UTF-8 and the `Content-Type` header will indicate UTF-8, because the default `codePage`  is UTF-8. 
2. Source files encoded in Shift-JIS and with a `@ Page codePage=932` declaration will be internally converted successfully to UTF-8. The response body will be sent as SJIS and the `Content-Type` header will indicate SJIS because of the `codePage` setting.  
When we do `Server.UrlEncode()` within the source code, the string is interpreted as SJIS and then url-encoded. ASP.NET is clever enough to use the `codePage` setting. This means that if we want to url-encode in UTF-8 instead of SJIS, then we have to change the output encoding to UTF-8 with `Page.CodePage=65001`, then `Server.UrlEncode([some string])` and then restore with `Page.CodePage=932`.
3. Source files encoded in UTF-8 without BOM will not be internally converted successfully to UTF-8. Instead, ASP.NET will try to convert the UTF-8 source file with a SJIS->UTF-8 algorithm, and make the result unreadable. No matter what encoding we set on the browser, this will be unreadable.
4. Source files encoded in UTF-8 with BOM will be internally converted successfully. The response body will be sent as UTF-8 and the `Content-Type` header will indicate UTF-8, because the default `codePage`  is UTF-8. `Server.UrlEncode()` will encode using UTF-8.
5. Source files encoded in UTF-8 with BOM and with `@ Page codePage=932` will be internally converted successfully to UTF-8. The response body will be sent as SJIS and the `Content-Type` header will indicate SJIS. `Server.UrlEncode()` will encode using SJIS.
6. Source files encoded in EUC-JP will be unreadable. ASP.NET converts a SJIS->UTF-8 algorithm to the EUC-JP source file, completely messing it up. In fact, I got a compile-time error.
7. Source files encoded in either SJIS or UTF-8 with BOM, with a `@ Page codePage=20932` (EUC-JP) work perfectly OK. They are successfully converted to UTF-8 internally, and the response body is sent as EUC-JP and the `Content-Type` is correctly set as well. `Server.UrlEncode()` will encode using EUC-JP.

## How to modify pre-existing IIS websites to use DDH server-side embedding

To use DDH server-side embedding, we need to convert pre-existing static HTML files to `.aspx` and insert code that does the server-side embedding. This in itself is rather straightforward, and the ASP.NET code to do this is under preparation.

Encoding of the files is not so straightforward. These are the things that we have to consider. We assume that the static HTML was encoded with SJIS and follow up with dicussion on how to manage if the static HTML was in a different encoding.

1. If the static files were coded using SJIS, then the `.aspx` files should also be maintained as SJIS so that the web-designer will not inadvertently screw encoding (asking the web-designer to encode `.aspx` files in UTF-8 whilst adding a `meta` tag with `charset=sjis` is too complicated). This means that we either set `responseEncoding=shift-jis` and `requestEncoding=shift-jis` in `web.config` or we set `@ Page codePage=932` on each page (or `Page.codePage=932` in a common library).
2. When we run the function that does server-side embedding, the DDH server will return a UTF-8 encoded response. ASP.NET will correctly handle this internally and embed it into the page. Then it will be outputted according to the encoding in `responseEncoding` or `@ Page codePage=932` which will be SJIS. In other words, we don't have to think much about it. The only thing that we should be careful about is to strip the `<meta charset=utf-8>` tag from the DDH response.

### Encoding for DDH server-side embedding based reverse-proxy

When we use DDH server-side embedding as the mechanism for a reverse proxy, then we will have to leave in the `<meta charset=utf-8>` tag generated from the DDH response. Therefore, this case will be the exception where the response will be in UTF-8 and not SJIS.

To do this, we encode the `.aspx` file that serves as the reverse proxy in any encoding (web-designers should not touch this code, nor should it include non-ascii chars, so encoding is not an issue). This should have `@ Page codePage=65001` to ensure that the output is in UTF-8.

### If the static HTML was in UTF-8

If the static HTML was in UTF-8, then we should set `fileEncoding=UTF-8` in `web.config`. This is because we cannot reliably expect the web-designer to add a BOM to all of their HTML files.

### If the static HTML was in EUC-JP

If the static HTML was in EUC-JP, then we should set `fileEncoding=EUC-JP` in `web.config`. 

### If the website uses ASP.NET in other files.

In this case, it would be hazardous to meddle with the `web.config` file. We will probably have to approach this on a case-by-case basis.

## How classic ASP handles encodings

We probably won't provide classic ASP code. I'm just documenting this because I want to take it off of my head.

With classic ASP, you set the source file encoding in `@ CODEPAGE=65001`. Otherwise, ASP assumes that the file is in SJIS. If this is not set accurately, the functions that take strings as input (I think these assume UTF-8) will not work correctly. Strings in other parts of the source file are not touched; only the strings that are fed into functions. In other words, classic ASP will only use `@ CODEPAGE=65001` for the strings fed into functions whereas ASP.NET will use it to encode the entire page.

The encoding for output is set in either `Session.CodePage=932` or `Reqeust.CodePage=932`. To make `Server.UrlEncode()` use UTF-8, then you do `Request.CodePage=65001` before calling `Server.UrlEncode()` and then do `Request.CodePage=932` to restore.

Similar to `@ CODEPAGE=65001`, `Session.CodePage=932` will also only touch strings generated from functions. It won't touch the raw strings within the source file that are displayed as is. This contrasts with ASP.NET which encodes everything.

Whereas ASP.NET sets the body encoding and the `Content-Type` header simultaneously, classic ASP requires that you set them seperately. For example, Classic ASP requires that you set `Response.Charset = "shift-jis"` if you want to set the `Content-Type` header.

What this means is that encoding handling is much more like regular web servers. There will be cases where the `Content-Type` header is not set, in which case, the `<meta charset="sjis">` tag will come into effect (this tag generally has lower priority than the response header). Web servers don't really care what's in their response body or what encoding its in; they simply send it as a stream of bytes. This is how classic ASP handles it, until it has to manipulate the data.

As a result, if you are modifying a website that is written in UTF-8 static HTML files, you first change the extension to `.asp`. Unlike `.aspx`, nothing will change until you use a function that takes a string as input. Only when you use string functions will you need to set `@ CODEPAGE=65001`. You can freely mix source files with different encodings by simply setting the appropriate `@ CODEPAGE=`. However, you will have to set `Content-Type` headers or `<meta charset>` tags manually and accurately.

As a web-developer, I prefer the classic ASP way since it gives me more freedom. I can however understand why ASP.NET tries to make encoding more consistent.