<%@ language="VBScript" CODEPAGE=65001 %>
<% Session.CodePage=65001 %>

<%
  Response.Charset  = "utf-8"
  Response.Expires  = -1
  Response.addHeader "Pragma","no-cache"
  Response.addHeader "Cache-control","no-cache"
 %>
<% 
  Function push(myArray, value)
    ReDim Preserve myArray(UBound(myArray) + 1)
    myArray(UBound(myArray)) = value
    push = myArray
  End Function

  Function urlEncodeUtf8(string)
    encString = Server.UrlEncode(string)
    urlEncodeUtf8 = encString
  End Function

  Function link(label, href, query)
    Dim queries()
    ReDim queries(0)
    For each name in query.Keys
      push queries, name & "=" & urlEncodeUtf8(query.Item(name))
    Next
    href = href & "?" & Join(queries,"&")
    link = "<a href=""" & href & """>" & label + "</a>"
  End Function
 %>
 <!DOCTYPE html>
<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=shift-jis">

</head>
<body>
<% Set queries = Server.CreateObject("Scripting.Dictionary") %>
<% queries.Add "lang", "日本語" %>
<% queries.Add "price", "激安" %>

<%= link("link label", "http://mac.com/", queries) %>

</body>
</html> 