# Generating new pages with DDH

DDH allows us to create nice HTML tables directly from CSV files. One way to present this HTML table is to simply generate an HTML page.

There is one large issue however; HTML pages generated on the DDH server will have a URL with `castle104.com`. This is unacceptable. We need to display HTML pages generated on the DDH server under a URL that belongs to the supplier.

This can be accomplished using reverse proxies.

## Using Apache RewriteEngine

The Apache `RewriteEngine` allows us to use the `.htaccess` file to configure a reverse proxy ([source](http://www.slicksurface.com/blog/2008-11/use-apaches-htaccess-to-accomplish-cool-and-useful-tasks)).

For the actual settings, refer to the README.md document on the JSONP implementation repository.

## Using a forwarding .aspx file

IIS servers do not allow reverse proxies unless we install plugins and change settings through the administrator interface. You cannot simply provide something like `.htaccess`.

Because it is very unlikely that we will be allowed to do this, we will use a forwarding `.aspx` file. This `.aspx` file sends an HTTP request to the DDH server and embeds the DDH response into its own response. In fact, the DDH response body is all that this forwarding `.aspx` file will return.

We are still testing this implementation. See `README-IIS.md` under the `samples` folder for more information.

