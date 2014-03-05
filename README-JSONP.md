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

### Preparing the core Javascript file

First make sure that the `min.js` files are up to date. `cd` to the `ddh` folder of you implementation and run `bundle exec rake minify_js`.

### Whole-fragment JSONP-embedding

To do whole-fragment JSONP-embedding, you have to create a separate PHP endpoint for each table format in the implementation server. On the other hand, the Javascript to embed the DDH response is very simple and is always the same. 

Embed the following where you want the DDH response to be embedded. The class must contain `ddhwfe` which indicates that this `div` should be processed by DDH. This `div` must have an `id` so that the DDH response will know where it should embed itself. `data-ids` tell DDH which products to show and `data-ep` sets the endpoint php file.
```HTML
<div class="price clearfix ddhwfe" id="price_table_1" data-ids="5010-21500,5010-21501,5010-21502,5010-21506,5010-21507,5010-21508,5010-21541" data-ep="price_table.php"></div>
```

Place this at the end of your last DDH embed. This will call the script that finds all DDH embed containers and will send an information request to the DDH server.
```HTML
<script src="/ddh_jp/ddh/javascripts/ddh.min.js"></script>
```


### Per-cell JSONP-embedding

Per-cell JSONP-embedding always uses the exact same PHP endpoint (`ddh/cell.php`) and the same Javascript. The difference is in the markup that we add to each cell.

Below is an example row where a DDH response should be embedded. Any element with a `ddhcell` class will be processed. If this element contains a class with a `_x_` in the middle, then a DDH request will be sent to retrieve information. In the case of `5010-21517_x_package`, the `package` field of product id `5010-21517` will be retrieved from the server, and the data will be inserted into this element.
```HTML
<tr class="odd">
    <td class="Pname">Buffer C3 <br />（細胞中和・吸着 Cell Neutralization・Adsorption）</td>
    <td class="5010-21517_x_package ddhcell"></td>
    <td>5010-21517</td>
    <td class="5010-21517_x_price ddhcell"></td>
    <td>&nbsp;</td>
</tr>
```

As with whole-fragment imbedding, insert the same `<script>` tag to call the script that processes DDH embed containers.

```HTML
<script src="/ddh_jp/ddh/javascripts/ddh.min.js"></script>
```
