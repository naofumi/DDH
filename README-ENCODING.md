#  Encoding

Using Shift-JIS as the data-exchange format is very dangerous. We have to be very aware of potential issues.

1. Excel CSV files are in Shift-JIS. If they are from a Windows machine, it will be in Win-31J (Shift-JIS with Windows extensions). If the are from a Mac, then it will bein Shift-JIS-mac. This means that if characters like `®` are included in the Excel file, they will be lost on Excel export.
2. If we exchange encodings with the PHP `mb_convert_encodings` function, then characters that cannot be converted will be ignored. On the other hand, iconv will allow us to transcode (e.x. `®` => `(R)`).
3. When we use server-side embedding, we have to transfer content in the encoding that the target page is in. If the target page is in UTF-8, then there is no problem. However, if the target page is in "sjis", then we have to send "sjis" ("sjis-win" seems to be acceptable too, even on Macs). This means that even if the original CSV files were in Unicode and we were able to get characters like `®` into the internal UTF-8 encoding, we would not be able to send it to the browser, unless we converted these characters to html entities. This is certainly possible, but I think we are going much too far.
4. Note that the encoding that we use to send a page to the browser is only an issue for server-side embedding. It is not an issue when we generate whole new pages because we would normally encode these pages in UTF-8. It is not an issue with JSONP, because we send the JSONP response in `\u` Unicode encoding and the browser treats the Unicode content as is, without any reference to the document encoding.

## Encoding recommendation

**Scenario 1:** If we are sure that the Excel file does not contain characters that cannot be represented in Shift-JIS (Win-31J dialect).
1. Export data from Excel in Shift-JIS. In `config.php`, specify `sjis-win`. This ensures that all characters in the original Excel file will be converted correctly to UTF-8 internally.
2. When we use server-side embedding and the target page is encoded in Shift-JIS, then convert the DDH response (always in UTF-8) using `sjis-win`.
3. For whole page responses, use UTF-8 as the page encoding (no conversion necessary).
4. For JSONP responses, no encoding is necessary.

**Scenario 2:** If the Excel file may contain characters that cannot be represented in Shift-JIS (Win-31J dialect).
1. Export data from Excel in UTF16 text. In `config.php`, specify `UTF-16` and `tab-delimited`. This ensures that all characters in the original Excel file will be converted correctly to UTF-8 internally.
2. When we use server-side embedding, the target page must be encoded in UTF-8. If the target page is in Shift-JIS, then we cannot assure that it will be diplayed correctly. No conversion of the DDH response is necessary.
3. For whole page responses, use UTF-8 as the page encoding (no conversion necessary).
4. For JSONP responses, no encoding is necessary.

If at all possible, we should go with **Scenario 2**. Encoding is hard, and the effort really is **not** worth it. Furthermore, there are quite a number of commonly used characters that are often used on documents (by virtue of Unicode), but which case problems in SJIS. It is quite likely that we will bump into some issues.

**Extra 1:**
In Scenario 2, it is possible to convert to html entities (which are ASCII and encoding independent) and have the browser interpret them. We think this is going too far, and won't implement it.

**Extra 2:**
Below is a list of common characters that may cause trouble. Make sure that they are not included in the Excel files unless you have a full end-to-end Unicode system.
* ™(&amp;trade;) : This is not part of any SJIS except x-mac-japanese. x-mac-japanese encodes it in a very peculiar code. It won't display on SJIS.
* ®(&amp;reg) : This is not part of any SJIS except x-mac-japanese. x-mac-japanese encodes it in a very peculiar code. It won't display on SJIS.
* ©(&amp;copy;) : This is not part of any SJIS. It won't display on SJIS.
* µ (&amp;micro;) : This is not part of any SJIS. It won't display on SJIS.
* ¥ (&amp;yen;) : This is not part of SJIS. `mb_convert_encoding` will convert it to the 全角 version which is included in SJIS. This is acceptable. `iconv` will convert it to "yen" which is unacceptable. ASP.NET gives up and show as "?" which is unacceptable. It's safer to not use this.

**More on the ¥ mark** 
When creating a CSV from Excel, the "¥" is converted to the byte `5c` which is the backslash character.  This won't be converted back to "¥" with `mb_convert_encoding`.

## Converting SJIS documents to UTF-8

### Regular HTML files

With Apache, simply save the files in the encoding that you want. Adjust the `meta charset` tag (BBEdit automatically does this for you). Apache does not seem to mind if the BOM is set or not.

If necessary set the charset in the http headers by configuring `.htaccess`.

### PHP files

Simply save the files in the encoding that you want, adjusting the `meta charset` tag as necessary.

Apache PHP does not seem to mind if the BOM is set or not. If the file is doing server-side embedding, set the output encoding as the third parameter of the `server_side_embed()` method.

### .aspx files

IIS automatically converts any `.aspx` file to Unicode. The issue is what IIS assumes the encoding of `.aspx` to be. If the BOM is available, this is straightforward. If the BOM is not available, IIS looks up the `web.config` file which specifies the encoding for all `.aspx` files.
```xml
<?xml version="1.0" encoding="utf-8"?>
<configuration>
  <system.web>
    <globalization fileEncoding="utf-8"/>
  </system.web>
</configuration>
```
Importantly, the value in `web.config` cannot be overridden. All `.aspx` files must be in this encoding. 

If `web.config` is not set, then all `.aspx` files without a BOM are assumed to be Shift-JIS (CP932, Win-31J).

Additionally `.aspx` files must set `codePage=65001` in the `@ Page` directive. This specifies the encoding in which the page will be output. UTF-8 is "65001" and Shift-JIS (CP932) is "932".