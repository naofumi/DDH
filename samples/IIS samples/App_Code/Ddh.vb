Imports Microsoft.VisualBasic
Imports System.Net
Imports System.IO

Public Class Ddh
  Private Page
  Private Server

  'Public Shared implementationServer As String = "http://192.168.0.30:8890/ddh_iwai-chem_15ff4e/"
  Public Shared implementationServer As String = "http://192.168.0.30:8890/ddh_gls_uh5y3x/"
  'Public Shared implementationServer As String = "http://ddh.castle104.com/ddh_gls_uh5y3x/"

  Public Sub New(myPage As Page, myServer As HttpServerUtility)
    Page = myPage
    Server = myServer
  End Sub

  Private Function push(myArray() As String, value As String) As Array
    ReDim Preserve myArray(0 To UBound(myArray) + 1)
    myArray(UBound(myArray)) = value
    push = myArray
  End Function

  Private Function urlEncodeUtf8(myString As String) As String
    Dim encString As String
    Page.CodePage=65001
    encString = Server.UrlEncode(myString)
    Page.CodePage=932
    urlEncodeUtf8 = encString
  End Function

  Public Function link(label As String, endpoint As String, query As String(,)) as String
    Dim tuples As New ArrayList()
    Dim i As Integer
    For i = LBound(query) To UBound(query)
      tuples.Add(query(i, 0) & "=" & urlEncodeUtf8(query(i, 1)))
    Next
    Dim href As String = "/ddh_jp/reverse_proxy.aspx?ep=" & endpoint & "&" & Join(tuples.ToArray(), "&")
    link = "<a href=""" & href & """>" & label + "</a>"
  End Function

  ' http://msdn.microsoft.com/en-US/library/1t38832a%28v=vs.80%29.aspx
  '
  Public Function getFromImplementationServer(queryString As NameValueCollection) As String
    Dim implementationServer = Ddh.implementationServer
    Dim myQueryString = new NameValueCollection(queryString)

    Dim endpoint = myQueryString.get("ep")
    myQueryString.remove("ep")
    
    Dim request As HttpWebRequest  = WebRequest.Create(implementationServer & endpoint & "?" & toQueryString(myQueryString))
    request.Method = "GET"
    request.Accept = "text/html"
    request.Timeout = 2000

    Dim myResponse As WebResponse = request.GetResponse()
    Dim dataStream As Stream = myResponse.GetResponseStream()
    Dim reader As New StreamReader(dataStream)
    Dim responseFromServer As String = reader.ReadToEnd()

    reader.Close()
    dataStream.Close()
    myResponse.Close()
    getFromImplementationServer = responseFromServer
  End Function

  Function toQueryString(query As NameValueCollection) As String
      Dim tuples As New ArrayList()
      For each name As String in query.AllKeys
        tuples.Add(name & "=" & Server.UrlEncode(query.get(name)))
      Next
      toQueryString = Join(tuples.ToArray(), "&")
  End Function


  Public Function ServerSideEmbed(ep as String, _
                                  query As String(,)) as String
    Dim queryString As New NameValueCollection()
    Dim i As Integer
    queryString.Add("ep", ep)
    queryString.Add("html_only", "1")
    For i = LBound(query) To UBound(query)
      queryString.Add(query(i, 0), query(i, 1))
    Next
    ServerSideEmbed = getFromImplementationServer(queryString)
  End Function


End Class
