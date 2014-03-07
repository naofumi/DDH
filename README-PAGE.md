# Generating new pages with DDH

DDH allows us to create nice HTML tables directly from CSV files. One way to present this HTML table is to simply generate an HTML page.

## Creating the correct URL (parameter encoding)

To let DDH generate pages, we have to send it a query that it can use to generate the page. This query often will contain non-ASCII characters so we have to be careful with the encoding.

DDH **requires** that the parameters be sent in UTF-8. We could use automatic detection, but I'm rather against it.

If the pages are in UTF-8, then we can simply put the non-ASCII characters in the `href` attribute like so;
```html
<a href="/ddh_jp/antibody_list.php?reactivity=Bovine（ウシ）">Bovine（ウシ）<a/>
```

The browser would be clever enough to urlencode the parameters and send it off to the server. On decoding, the server would receive `Bovine（ウシ）` in UTF-8.

If we did the same in a SJIS encoded page however, the browser will send the SJIS string as urlencoded. Hence the server would receive `Bovine（ウシ）` in SJIS, and have no clue about which encoding was used. It could use automatic detection to realize that it was in SJIS, but that is not completely reliable.

To ensure that we send parameters in UTF-8 in an SJIS encoded page, we have to do this;
```html
<a href="/ddh_jp/antibody_reactivity_type.php?reactivity=Bovine%EF%BC%88%E3%82%A6%E3%82%B7%EF%BC%89">Bovine（ウシ）</a>
```

To programatically generate the URL, we can use a function like this;
```php
function p_enc($string) {
  $char_encoded = mb_convert_encoding($string, 'UTF-8', 'SJIS');
  return urlencode($char_encoded);
}
```

If we are dealing with forms, then we make sure we set the `accept-charset` attribute on the form. Keep in mind [how Rails ensures UTF-8 in IE](https://github.com/rails/rails/commit/25215d7285db10e2c04d903f251b791342e4dd6a#commitcomment-118076).

```html
<form action="/ddh_jp/antibody_reactivity_type.php" accept-charset="utf8" method="get">
  Reactivity: <input type="text" name="reactivity"><br>
  <input type="submit" value="Submit">
</form>
```

## Hiding castle104.com URL

There is one large issue however; HTML pages generated on the DDH server will have a URL with `castle104.com`. This is unacceptable. We need to display HTML pages generated on the DDH server under a URL that belongs to the supplier.

This can be accomplished using reverse proxies.

### Using Apache RewriteEngine

The Apache `RewriteEngine` allows us to use the `.htaccess` file to configure a reverse proxy ([source](http://www.slicksurface.com/blog/2008-11/use-apaches-htaccess-to-accomplish-cool-and-useful-tasks)).

For the actual settings, refer to the README.md document on the JSONP implementation repository.

### Using a forwarding .aspx file

IIS servers do not allow reverse proxies unless we install plugins and change settings through the administrator interface. You cannot simply provide something like `.htaccess`.

Because it is very unlikely that we will be allowed to do this, we will use a forwarding `.aspx` file. This `.aspx` file sends an HTTP request to the DDH server and embeds the DDH response into its own response. In fact, the DDH response body is all that this forwarding `.aspx` file will return.

See `README-IIS.md` under the `samples` folder for more information.
