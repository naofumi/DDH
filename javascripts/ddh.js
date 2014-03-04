// Find DDH placeholder elements, send DDH request and insert response into DOM
//
// Include the following at the end of the <body> tag to initiate DDH.
// `<script src="/ddh_jp/ddh/javascripts/ddh.js" async></script>`
//
// We use reverse proxies to hide the ddh.castle104.com domain. The default 
// setting is to use `/ddh_jp/` as the reverse proxy trigger.
// If you can not use this due to insufficient permissions, you can set
// the base_path as the following;
//
// `<script src="/[base_path]/ddh/javascripts/ddh.js" id="ddh_js" data-base-path="[base_path]" async></script>`
//
// If we are on IIS, then we can't rely on a reverse proxy. For IIS, we will have
// to let `ddh.castle104.com` show in some cases.
//
// `<script src="http://ddh.castle104.com/ddh/javascripts/ddh.js" async></script>`
!function(){
  var basePath = "/ddh_jp/", 
      d = document,
      s = "script",
      fjs=d.getElementsByTagName(s)[0], // First <script> tag
      p=/^http:/.test(d.location)?'http':'https', // protocol
      pv=/[?&]preview=/.test(location.search)?'&pv=1':'', // Is this a preview run?
      jsidc = "ddhcelljs", // Id for per-cell embed <script> tag
      cn = "ddhwfe", // Class identifier for whole-fragment embed
      cl = "ddhcell", // Class identifier for per-cell embed
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
        rs=[];
    for(var i=0;i<es.length;i++){
      var e=es[i],
          cs=e.getAttribute('class').split(' ');
      for(var j=0;j<cs.length;j++){
        if (cs[j].indexOf('_x_')!=-1) {
          rs.push(cs[j]);
        }
      }
    };
    var js;
    if(!d.getElementById(jsidc)){
      js=d.createElement(s);
      js.id=jsidc;
      js.src=basePath+epc+"?reqs="+rs+pv;
      js.setAttribute('async', 'true');
      fjs.parentNode.insertBefore(js,fjs);
    }
  }();
}();
