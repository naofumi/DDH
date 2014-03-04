<%@ language="javascript" CODEPAGE=932 %>
<% Session.CodePage=932 %>

<%
   Response.Charset  = "shift-jis";
   Response.Expires  = -1;
   Response.addHeader("Pragma","no-cache");
   Response.addHeader("Cache-control","no-cache");
 %>
<% 
   function urlEncodeUtf8(string) {
     Session.CodePage = 65001;
     var encString = Server.UrlEncode(string);
     Session.CodePage = 932;
     return encString;
   }
   function link(label, href, query) {
     var queries = [];
     for(name in query) {
       queries.push(name + "=" + urlEncodeUtf8(query[name]));
     }
     href = href + "?" + queries.join('&');
     return "<a href=\"" + href + "\">" + label + "</a>";   }
 %>
 <!DOCTYPE html>
<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=shift-jis">

</head>
<body>

<%= link("link label", "http://mac.com/", {lang: "日本語", price: "激安"}) %>

</body>
</html> 