<%@ Page language="VB" Debug="true"%>
<script runat="server">
  Protected Sub Page_Load(ByVal sender As Object, ByVal e As System.EventArgs)
    Response.Charset  = "sjis"
    Response.Expires  = -1
    Response.addHeader("Pragma","no-cache")
    Response.addHeader("Cache-control","no-cache")
    Page.CodePage=932
  End Sub

  Function push(myArray() As String, value As String) As Array
    ReDim Preserve myArray(0 To UBound(myArray) + 1)
    myArray(UBound(myArray)) = value
    push = myArray
  End Function

  Function urlEncodeUtf8(myString As String) As String
    Dim encString As String
    Page.CodePage=65001
    encString = Server.UrlEncode(myString)
    Page.CodePage=932
    urlEncodeUtf8 = encString
  End Function

  Function link(label As String, href As String, query As SortedList) as String
    Dim tuples As New ArrayList()
    For each name As String in query.Keys
      tuples.Add(name & "=" & urlEncodeUtf8(query.Item(name)))
    Next
    href = href & "?" & Join(tuples.ToArray(), "&")
    link = "<a href=""" & href & """>" & label + "</a>"
  End Function
</script>
 <!DOCTYPE html>
<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=shift-jis">

</head>
<body>
<% Dim queries As New SortedList() %>
<% queries.Add("lang", "“ú–{Œê") %>
<% queries.Add("price", "Gekiyasu") %>

<%= link("link label", "http://mac.com/", queries) %>
</body>
</html> 