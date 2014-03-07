<%@ Page language="VB" codePage=65001 Debug="true" %>
<script runat="server">
' Sends a get request to the implementation server
' and outputs the result. 
'
' Call as the following;
' /ddh_jp/reverse_proxy.aspx?ep=antibody_reactivity_type.php&title=Some-Title&type=some-type&host=some-host



Sub Page_Load(ByVal sender As Object, ByVal e As System.EventArgs)
    Response.Charset  = "utf8"
    Page.CodePage=65001
End Sub

</script>
<% Dim d As New Ddh(Page, Server) %>
<%= d.getFromImplementationServer(Request.QueryString) %>
