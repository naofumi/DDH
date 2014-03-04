# JSONP embedding of DDH

DDH allows you to easily embed information (stored in CSV files) into web pages that you already have. DDH has two ways of doing this. The first is JSONP-style embedding and the other is server-side embedding. Here we describe JSONP-style embedding.

The advantage of JSONP-style embedding is that it is easy to set up, and modifications to your current website are minimal.

The downside is SEO. If you are going to embed a significant amount of information which you want Google-bots to pick up, then JSONP-style embedding is not good. Google-bot does not typically read content in JSONP.

Because of this, if you can use server-side embedding, we recommend this over JSONP embedding. The only case where JSONP embedding would not be an issue is when you are only embedding price and campaign information, etc.; content that you don't need Googlebot to see.

If you cannot use server-side embedding, either because your server cannot run scripts like PHP, ASP.NET, classic ASP or SSL, or because you want to preseve the old site as much as you can, then JSONP-embedding becomes your only choice.

In DDH, there are two types of JSONP-embedding, whole-fragment and per-cell.

## Whole-fragment JSONP-embedding

Whole-fragment embedding is where the DDH-server returns the HTML for a whole HTML table, and this is embedded into an element on the target web page. 

Typically, we will create an end-point PHP file that will receive a list of product IDs and return an HTML table containing information for these products. If we want tables with different formats, then we will need different end-point PHP files.

To use this embedding type, the CSV-files have to include all the information that we want to show in the table. If this is the case, then this type is generally easier to set up compared to per-cell embedding because there is less markup to add for each page where we want to display the table.

The downside of this type is that product information is only retrieved on a JSONP request, and is hence invisible to Googlebot.

## Per-cell JSONP-embedding

Per-cell embedding is where we embed cells of data to multiple locations in the page (specified by markup). If we had a product table, we could create the table in static HTML and then add markup to the cells where we want to put the prices. The per-cell JSONP-embedding Javascript will find these cells, send a DDH request to retrieve this information, and then insert this into each cell.

When we use this type, most of the product information is already present in the original web page. Per-cell JSONP-embedding typically only adds prices or campaign information. Therefore we can use per-cell embedding when the CSV-files contain only basic pricing information. Furthermore, most of the product information is already on the original web page which will be visible to Googlebot.

The downside is that these tables are harder to manage. It is generally easier to manage product information inside Excel rather than an HTML table. Furthermore, you need to add a lot of markup; you need to individually add markup to each cell.

## How do do JSONP-embedding

### Whole-fragment JSONP-embedding

To do whole-fragment JSONP-embedding, you have to create a separate PHP endpoint for each table format. On the other hand, the Javascript to embed the DDH response is very simple and is always the same. The endpoint PHP filename and the products IDs are specified in the container `<div>`.

```Javascript
<div id="price_table_1" data-ids="201234,201235,201236,201237,201238,201239,201240,201241,201242" data-endpoint="price_table" data-host="" class="ddh-wf"></div>
<script>!function(d,s,h,id,ep,ids){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https',jsid=id+"-js",pv=/[?&]preview=/.test(location.search)?'&pv=1':'';if(!d.getElementById(jsid)){js=d.createElement(s);js.id=jsid;js.src=p+"://"+h+ep+"?ids="+ids+"&loc="+id+pv;js.setAttribute('async', 'true');fjs.parentNode.insertBefore(js,fjs)}}(document,"script","localhost:8890","price_table_1","/jsonp/price_table.php","201234,201235,201236,201237,201238,201239,201240,201241,201242")</script>
```


### Per-cell JSONP-embedding

Per-cell JSONP-embedding typically uses the exact same PHP endpoint (`ddh/cell.php`) and the same Javascript. The difference is in the markup that we add to each cell.

