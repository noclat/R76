# R76 router
R76 is a light-weight and lightning fast PHP router that can hold any kind of project. It does not require any design pattern.

R76 is shared under the [MIT license](http://opensource.org/licenses/MIT). 

Special thanks to [dhoko](http://github.com/dhoko) for his feedbacks.

# Documentation
- [Getting started](#getting-started)
- [Routes](#routes)
	- [Wrappers](#wrappers)
	- [Parameters](#parameters)
- [Tips](#tips)
	- [Paths](#paths)
	- [Callbacks](#callbacks)
	- [GET parameters](#get-parameters)
	- [Before and after route callbacks](#before-and-after-route-callbacks)
- [Helpers](#helpers)
	- [root()](#root-helper)
	- [url( …? )](#url-helper)
	- [uri( $key? )](#uri-helper)
	- [verb()](#verb-helper)
	- [async()](#async-helper)
	- [load( $path )](#load-helper)
	- [go( $url? )](#go-helper)

## Getting started
Start creating an `index.php` file at the top level. Include `r76.php`.

All requests are redirected to `index.php`. Here is an example of what it should looks like:

	<?php
	include 'r76.php';
	
	get('/', function() {
		echo 'Home page.';
	});
	
	get('/about', function() {
		echo 'About page.';
	});
	
	// other routes here
	
	run(function() { 
		echo 'No route matched.'; 
	});

The `run()` function displays the result, and gets a callback in parameter, called when the URL doesn’t match any route configuration. See the [Callbacks section](#callbacks) below to know more about what is posssible to do with.

## Routes
After including `r76.php`, and before calling `run()` that will display your page, you may need to configure routes callback:

	on('GET', '/', function() { … });
	on('GET', '/@section', function( $section ) { … }); // $section is a paramater
	on('GET|POST|PUT|DELETE', '/form', function() { … }); // combined verbs

The **URL** respects the [path syntax](#paths). Anyway, to match the root, you’ll need to set the URL as ‘/’. Note that [GET parameters](#get-parameters) aren’t part of the route.  
Combine verbs using the `|` separator.
	
### Wrappers
You can also configure routes by using the wrappers:

	get('/route/path', $callback);
	post('/route/path', $callback);
	put('/route/path', $callback);
	delete('/route/path', $callback);
	
### Parameters

URL parameters are passed to the callback function in the order they are specified.

	// current URL: //yoursite.com/projects/articles/134/
	get('@category/articles/@id', function( $category, $id ) {
		echo $category; // displays "projects", local scope
		echo $id; // displays “134”, local scope 
		echo uri('id'); // displays “134”, global scope
	});
	
`uri( $key )` lets you access the parameters values from anywhere in the code. See [URI helper](#uri-helper) for more information.

	
## Tips
### Paths
Any path you will have to write (in `url()` helper and the configuration methods) are parsed to prevent from any bug occuring with the ‘slash’ character confusing use. So you can both write `/path/` or `path/`, and even `path`.  

**Note:** the URLs could be written both **with or without any extension**—`//example.com/sitemap` and `//example.com/sitemap.xml` would be equaly regarded. Make sure the extension doesn't appear in routing functions.

### Callbacks
Callbacks could be files, anonymous functions, and function or method names. If it’s a file, it will just be included (and executed). If it’s a function or a method name, just pass the name, without the parenthesis. Examples: `article.php`, `readArticle`, `articles::read`, `article->read` — in this last case, the ‘article’ class will be instanciated and the `__construct()` method will be triggered.

**Note**: use `return false;` in a callback to cancel it and trigger the default callback instead.

### GET parameters
GET parameters aren’t part of the route. Any callback of `/articles/archives` url and `/articles/archives/sort:year%20asc` will be the same. You can access those parameters in your code to make changes according to their values.

**Note**: GET parameters will be automatically rewrited from `?key=value&key2=value2` to `/key:value/key2:value2`, but still available using `$_GET` superglobal.

### Before and after route callbacks
Anything above `run()` will be executed before the route callback, and anything below `run()` will be executed after the route callback.

## Helpers
Some values and functions are available to manipulate anything related to URLs and template files. Those functions are avaible in all your files.

<a name="root-helper"/>
### root()
Returns the complete adress of your website.

<a name="url-helper"/>
### url( …? ) 
#### 0 parameter
Returns the complete current URL.

#### 1 string parameter
Returns the absolute protocol-free URL of a relative path. `url(‘articles/archives’)` will return `//yourdomain.com/articles/archives`. This works both for http and https URLs.

#### 1 associative array parameter
Returns the same URL, but changes the specified parameters. Example: the current URL is `article/read/4/showcomments:true/commentspage:3`.

	echo url(array(
		'commentspage' => 5
	));

will return the absolute URL of `article/read/4/showcomments:true/commentspage:5`.

#### 1 string + 1 associative array parameters
Returns the absolute URL, adding the parameters.

	echo url('article/read/4', array(
		'showcomments' => 'true'
		'commentspage' => 1
	));

will return `//yourdomain.com/article/read/4/showcomments:true/commentspage:1`.

<a name="uri-helper"/>
### uri( $key? )
#### 0 parameter
Returns the current URI, which is the URL freed from root and GET parameters.

#### 1 parameter
If `$key` is a string, it returns the value of the variable set in the root:

	// current URL: //yoursite.com/articles/read/134/
	get('articles/read/@id', function($id) {
		echo $id; // displays “134”
		echo uri('id'); // displays “134”
	});

If `$key` is numeric, it returns the nth part (zero-based) of the URI.

	// current uri: articles/read/134/
	echo uri(0); // displays “articles”
	echo uri(1); // displays “read”
	echo uri(2); // displays “134”

<a name="verb-helper"/>
### verb()
Returns the current verb (GET, POST, PUT, DELETE). Note: it returns the value of the `$_SERVER[‘REQUEST_METHOD’]` server variable.
  
<a name="async-helper"/>
### async()
Returns true if you’re using an AJAX request, and false if not. What defines an AJAX request is the value of the `X_REQUESTED_WITH` header set to `XMLHttpRequest`, used in nearly all of the JavaScript libraries that send AJAX requests.

<a name="load-helper"/>
### load( $path )
Includes all the php files located in the given folder path.

<a name="go-helper"/>
### go( $url? )
Redirects to the specified URL. If `$url` parameter is ommited, it will refresh the current page, using `url()` value.
