# Castle104 DDH (Dokodemo Hyou) Service

This is a sample DDH service implementation. Each DDH service will be run on a separate implementation.

This document describes how each implementation is structured and how you need to configure it to provide a service.

We first describe how to create a custom implementation that will receive a request from the vendor's website and generate a JSONP response (or HTML response) dynamically generated from the CSV file.

Secondly, we describe some modifications that need to be made to the vendor's website so that the requests will be sent correctly.

## Setup the implementation server

### Naming convention

All the implementations for castle104.com will run on a server with the domain name "ddh.castle104.com". 

## Setup the implementation folder

Create a folder with the necessary structure. The simplest way would be to copy a preexisting one. Make sure that you remove the `.git` folder, especially if you are planning to deploy on a client machine. The `.git` folder will contain password information from the previous implementation, which is obviously not good.

Manage the implementation in Git.

Load the DDH libarary as a Git submodule from the Bitbucket repository.

### Naming convention

The name of the implementation folder should be `[vendor_symbol]_[cryptic_key]_ddh`. The implementation folder should be on the top level so that a request to `http://name.of.implementation.domain/[vendor_symbol]_[cryptic_key]_ddh` will be the top level of the implementation directory.

The [cryptic_key] makes it very unlikely that the JSONP implementation will be accessed directly (without going through the vendor's reverse proxy). Although not a security issue, it is better to obscure the fact that some of the vendor's pages are actually served by Castle104 through the reverse proxy. The [cryptic_key] allows this. It is not a hard restriction like a `deny` in `.htaccess`, but should be sufficient.

We generally use [A-Za-z0-9_-] for the cryptic_key. Allowed characters are described [here](http://stackoverflow.com/questions/1856785/characters-allowed-in-a-url).

To easily and consistently generate a key, use a [md5 Hash Generator](http://www.miraclesalad.com/webtools/md5.php) and enter [vendor_symbol]\_ddh\_[date_in_numbers] and use the first 6 letters.

### DDH library

The DDH library contains the common code for a Castle104 DDH service.

### Configuration (config.php)

`config.php` contains the configuration information for this implementation. You can find a sample within the DDH library.

Read to comments inside this file to understand what to configure.

### Custom PHP views

If your implementation has some PHP views (for example, tables or HTML pages that you want to custom design), then you write them in the top-level directory. Take a look at some examples.

You can access all the data that has been extracted from the CSV files and processed by the DDH library. You can also use some helpers from the DDH library.

### .htaccess

Below is an example of an `.htaccess` file.

    # Ensure that magic_quotes are off
    php_flag magic_quotes_gpc Off

    # restrict access to the vendor's IP address
    #Order Deny, Allow
    #Deny from All
    #Allow from [vendor's IP address]
    #Allow from [our IP address]

We make sure magic_quotes are off. This may not be the default for servers running older versions of PHP.

We restrict access from the vendor's IP address once the reverse proxy on the vendor's website has been set up. Once this has been set, the only way to get to this website is through the reverse proxy (or from [our IP address]). This will block any visitors from directly accessing the implementation.

Currently, we are not implementing the IP address block (that's why it's commented out) but instead using a cyptic URL. This URL will only be visible if you do a man-in-the-middle attack between the vendor's server and the DDH implementation server, so it is very unlikely that somebody would stumble upon the URL.

## Modifying the vendor's website

### Naming convention

All requests to the DDH implementation will we be to `/ddh_jp/...`. "ddh_jp" stands for "DDH JSONP"

### Setting up a reverse proxy

We set up a reverse proxy so that the server on the vendor's website will relay JSONP requests to our implementation. This means that all URLs related to the Castle104 JSONP service will have the vendor's domain and not `castle104.com`. Users of the vendor's website won't know that castle104 is involved in any way.

Setting up a reverse proxy requires that we edit the `.htaccess` file on the vendor's website. A more detailed description is available at [this website](http://www.slicksurface.com/blog/2008-11/use-apaches-htaccess-to-accomplish-cool-and-useful-tasks).

The following instructions are for a static website. The instructions will be different if you are using a CMS.

Note that if you are developing using a local copy of the vendor's website, you still have to create an `.htaccess` file and set up a reverse proxy. Instead of `http://ddh.castle104.com/`, we would be using `http://localhost:8890/` or similar in the .htaccess file. This is because we don't want to expose the [cryptic_key].

**.htaccess file placed at the root level directory**

    RewriteEngine On
    RewriteRule ^ddh_jp/(.+)$ http://ddh.castle104.com/[vendor_symbol]_[cyptic_key]_ddh/$1 [P,L]

**.htaccess file placed at a sub level directory**

Create a folder named `ddh_jp` and place the following `.htaccess` file in it.

    RewriteEngine On
    RewriteRule ^(.+)$ http://ddh.castle104.com/[cryptic_key]_iwai_ddh/$1 [P,L]

These `.htaccess` files will intercept a request to `/ddh_jp`. Then Apache will send a request to `http://ddh.castle104.com/[cryptic_key]_iwai_ddh/` and after recieving a response, it will forward that response to the client.

### Changing the webpages

To send the requests to the JSONP service in the first place, we have to embed tags and create some custom links. How to do this is described elsewhere.

### Encoding issues

When displaying a page with DDH, we often send a query in the URL for the page. This URL may often contain non-ASCII characters since the original CSV file will often contain the same. We have to understand how the characters are going to be converted, and if we need some conventions.

#### The complications

In Safari and Firefox, if the web page is in Shift-JIS and a URL contains non-ASCII characters, then the Browser will send the URL in Shift-JIS. This means that the target script, after '%' decoding the URL, will receive the content in Shift-JIS.

If, for example, we have another page that has a URL directed to the same script but this time with a page encoding of UTF-8, then the Browser will send the URL in UTF-8, meaning the recipient will receive the content in UTF-8. 

If we allow this to be the case, then the recipient will have to auto-detect the encoding of the query, which is obviously not ideal.

As a solution, we will make DDH always requires that browser send queries in UTF-8. We could implement some auto-detection, but I think that this kind of accomodation only makes things complicated. 

To ensure that the queries are sent in UTF-8, make sure that we set `accept-charset="UTF-8"` for `<form>` tags. For URLs, we will prepare a script that does the conversion automatically so that you can easily find out what the URL should look like.