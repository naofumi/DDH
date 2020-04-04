// Find DDH placeholder elements, send DDH request and insert response into DOM
//
// Reverse proxy version
// The IIS/no-reverse proxy version is identical except for the basePath setting.
// If you can use a reverse proxy, then you can use the reverse proxy version even on IIS.
//
// Include the following at the end of the <body> tag to initiate DDH.
// `<script src="/ddh_jp/ddh/javascripts/ddh.js" async></script>`
//
!function(){
  var basePath = ddhBasePath || "/ddh_jp/", // This is the base http request path of the DDH implementation directory.
      d = document,
      s = "script",
      fjs=d.getElementsByTagName(s)[0], // First <script> tag
      p=/^http:/.test(d.location)?'http':'https', // protocol
      pv=/[?&]preview=/.test(location.search)?'&pv=1':'', // Is this a preview run?
      jsidc = "ddhcelljs", // Id for per-cell embed <script> tag
      cn = "ddhwfe", // Class identifier for whole-fragment embed
      cl = "ddhcell", // Class identifier for per-cell embed
      nc = "ddhnc", // Class identifier to prevent caching
      epc = "ddh/cell.php" // End point fo per-cell embed

  /* IE polyfill */ 
  if(!document.getElementsByClassName){
    document.getElementsByClassName=function(cn){
      var es=document.getElementsByTagName("*"),
          p=new RegExp("(^|\\s)"+cn+"(\\s|$)"),
          r=[];
      for(i=0;i<es.length;i++){
        if(p.test(es[i].className)){
          r.push(es[i])
        }
      }
      return r
    }
  }  
  // Whole-fragment embed
  !function(){
    var tables = d.getElementsByClassName(cn);
    for(i=0; i < tables.length; i++) {
      var js,
          table=tables[i],
          id=table.id,
          jsid=id+"-js",
          ids=table.getAttribute('data-ids'),
          ep=table.getAttribute('data-ep');
      if(!d.getElementById(jsid)){
        js=d.createElement(s);
        js.id=jsid;
        js.src=basePath+ep+"?ids="+ids+"&loc="+id+pv;
        js.setAttribute('async', 'true');
        fjs.parentNode.insertBefore(js,fjs)
      }
    }
  }();

  // Per-cell embed
  !function(){
    var es=d.getElementsByClassName(cl),
        rs=[],
        cr;
    for(var i=0;i<es.length;i++){
      var e=es[i],
          cs=e.getAttribute('class').split(' ');
      for(var j=0;j<cs.length;j++){
        if (cs[j].indexOf('_x_')!=-1) {
          rs.push(cs[j]);
        }
      }
      cr=e.classList.contains(nc)?"&nc=1":"";
    };
    if (rs.length > 0) {
      var js;
      if(!d.getElementById(jsidc)){
        js=d.createElement(s);
        js.id=jsidc;
        js.src=basePath+epc+"?reqs="+rs+cr+pv;
        js.setAttribute('async', 'true');
        fjs.parentNode.insertBefore(js,fjs);
      }      
    }
  }();
}();
