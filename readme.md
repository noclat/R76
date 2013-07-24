# R76 router
R76 is a light-weight PHP framework that can hold any kind of project. It only provides a **RESTful router** and some extended configuration features to help the code organization. You are still the real master of your project, since R76 is optimized for both procedural or POO environments, and does not require any specific intern organization or design pattern.

R76 is shared under a [CC BY-SA license](http://creativecommons.org/licenses/by-sa/3.0). 

See [Eye Fracture source](http://github.com/noclat/eyefracture.com) to get a full exemple of R76 usage.

# Documentation
- [Start](#start)
- [Load the system](#load-the-system)
- [Configuration](#configuration)
	- [load](#load)
	- [route, get, post, put, delete](#route-get-post-put-delete)
	- [Config file](#config-file)
		- [define](#define)
		- [custom](#custom)
- [Tips](#tips)
	- [Syntax sensibility](#syntax-sensibility)
	- [Callbacks](#callbacks)
	- [GET parameters](#get-parameters)
	- [Before and after route callbacks](#before-and-after-route-callbacks)
- [Helpers and R76 public methods](#helpers-and-r76-public-methods)
	- [root() or R76::root()](#root-helper)
	- [url() or R76::url()](#url-helper)
	- [uri() or R76::uri()](#uri-helper)
	- [path() or R76::path()](#path-helper)
	- [param() or R76::param()](#param-helper)
	- [params() or R76::params()](#params-helper)
	- [verb()](#verb-helper)
	- [async()](#async-helper)
	- [go()](#go-helper)


# Start
You can relocate the `r76.php` and `helpers.php` files. Start creating an `index.php` file at the top level. Don’t forget to update the protected directory path on the `.htaccess`. An example of file tree:

	./
		.htaccess
		index.php
		public/    (anything public like images, stylesheets and scripts)
		site/    (protected directory)
			core/     
				r76.php
				helpers.php
				CONFIG
				(and any other helpers)
			templates/    (or any design pattern you want to use)

# Load the system
The index.php needs to load the system. Here is an example of what it should looks like:

	<?php
	$site = include 'site/core/r76.php';
	// configuration here
	$site->run(function() { exit('404 error'); });

The `run()` method displays the result, and gets a callback in parameter, called when the URL doesn’t match any route configuration. See the [Callbacks section](#callbacks) below to know more about what is posssible to do with.

# Configuration
After including r76.php, and before calling `$site->run(…)` that will display your page, you may need to configure routes and load any king of PHP files (libs, helpers…).

## load
Loads all the php files located in the given folder path. E.g.:

	$site->load('site/core');

## route, get, post, put, delete
Configure callbacks for the routes:

	$site->route('GET', '/', 'site/templates/default.php');
	$site->route('GET|POST', '/@section', 'site/templates/@section.php');

You can allow any **verb** (GET, POST, PUT, DELETE) you want to access an url, and combine them by using the ‘|’ separator. E.g.: `GET|POST`.

The **url** respects the [path syntax](#syntax-sensibility). Anyway, to match the root, you’ll need to set the url as ‘/’. Note that [GET parameters](#get-parameters) aren’t part of the route. 

You can use variables in it for dynamic urls, just put an ‘@‘ before the name of the variable you want. To get their value, see [path() method](#path-helper). E.g.: 

	$site->route('GET', '/articles/@id/comments/page/@page', $callback);

The **callback** can be a file, a function or a method (see the [Callbacks section](#callbacks) for more information), and is able to use the variables in its name:

	$site->route('GET', '/@section', 'site/templates/@section.php');
	$site->route('GET', '/@section/@feature', '@section->@feature');
	
You can also configure routes by using the wrappers:

	$site->get('/route/path', $callback);
	$site->post('/route/path', $callback);
	$site->put('/route/path', $callback);
	$site->delete('/route/path', $callback);
	
## Config file
Sometimes it's more convinient to gather all these commands in a single file and only write a single line of PHP to configure your website. It's possible, but the syntax changes a bit. E.g.:
	
	# This is a comment
	# Load the core files
		LOAD site/core
	
	# Configure routes
		ROUTE GET 		/			site/templates/default.php
		ROUTE GET|POST	/contact	site/templates/contact.php
		ROUTE GET		/@section	site/templates/@section.php
		
Name this file however you want, and call it using:
	
	$site->config('path/to/the/config');

To prevent from the frustration of having some extra PHP code lines out of the config file, the config file enables some specific commands:

### define
Sets a global constant. It’s practical, simply because you can gather all your configuration constants in the same configuration file, like paths, passwords (hashed), services & API codes (google analytics, typekit, etc.) and so on.

	DEFINE key value

### custom
You can do what you want with the CUSTOM command. This is the syntax:

	CUSTOM callback parameters

It’s just calling a callback (function or method) with the given the parameters (optional). The parameters syntax is simple, just use any spacing character between them: `parameter1 parameter2 parameter3…`. It could help if you need to parse some configuration values, for example an external URL (from any API or service like Google Documents), or set a bunch of related ‘constants’.

Here’s a real life example, used for [Eye Fracture](http://eyefracture.com), which database is stored in 4 Google Spreadsheets:

	CUSTOM  sheets::set   videos    https://docs.google.com/spreadsheet/pub?key=XXX&single=true&gid=0&output=csv
	CUSTOM  sheets::set   lists     https://docs.google.com/spreadsheet/pub?key=XXX&single=true&gid=1&output=csv
	CUSTOM  sheets::set   studios   https://docs.google.com/spreadsheet/pub?key=XXX&single=true&gid=3&output=csv
	CUSTOM  sheets::set   quotes    https://docs.google.com/spreadsheet/pub?key=XXX&single=true&gid=2&output=csv

And here’s the callback:

	class sheets {
		...
		function set($key, $url) { self::$sheets[$key] = $url; }
		...
	}

If any spreadsheet url changes, we only have to update the config file, without getting deep in the code to update the related lines.


# Tips
## Syntax sensibility
Any path you’ll have to write (in url() function and the configuration methods) are parsed to prevent from any bug occuring with the ‘slash’ character confusing use. So you can both write `/path/` or `path/`, and even `path`.  
**Warning**: paths on DEFINE values and CUSTOM parameters aren’t parsed.

In the configuration command lines, you can use as much inline spacing/tabs characters as you want between the values, except around a ‘|’ separators in ROUTE commands.

Commands are not case sensitive, but paths are.

The URLs could be written both **with or without any extension**. `//example.com/sitemap` and `//example.com/sitemap.xml` are equaly regarded by the framework. Make sure the extension doesn't appear in the ROUTE command.

## Callbacks
Callbacks could be files, functions or methods. If it’s a file, it will just be included (and executed). If it’s a function or a method, just give the name, without the parenthesis. Examples: `load`, `articles::read`, `article->read` — in this last case, the ‘article’ class will be instanciated and the `__construct()` method will be triggered.

## GET parameters
GET parameters aren’t part of the route. Any callback of `/articles/archives` url and `/articles/archives/sort:year%20asc` will be the same. You can access those parameters in your code to make changes according to their values.

Note: GET parameters will be automatically rewrited from `?key=value&key2=value2` to `/key:value/key2:value2`, but still available using $_GET superglobal.

##  Before and after route callbacks
You can simply call before and after route callbacks by using this trick:

	<?php
	$site = include 'site/core/r76.php';
	$site->load('site/core');
	
	beforeRoute(); 
	// anything above will be executed before the route callback
	
	$site->get('/', 'site/templates/default.php');
	$site->get('/@section', 'site/templates/@section.php');
	
	// anything below will be executed after the route callback
	afterRoute();
	
	$site->run(function() { exit('404 error'); });
	
Or, in a configuration file:

	CUSTOM beforeRouteCallback
	# Anything above will be executed before the route callback
	ROUTE   GET       /               site/templates/default.php
	ROUTE   GET       /@section       site/templates/@section.php
	# 	Anything below will be executed after the route callback
	CUSTOM afterRouteCallback


# Helpers and R76 public methods
Some values and functions are avaiable to manipulate anything related to URLs and template files. Those functions are avaible in all your files.

<a name="root-helper"/>
## root() or R76::root()
Returns the complete adress of your website.

<a name="url-helper"/>
## url() or R76::url() 
### 0 parameter
Returns the complete current url.

### 1 string array parameter
Returns the absolute protocle-free url of a relative path. `url(‘articles/archives’)` will return `//yourdomain.com/articles/archives`. This works both for http and https urls.

### 1 associative array parameter
Returns the same url, but changes the specified parameters. Example: the current URL is `article/read/4/showcomments:true/commentspage:3`.

	echo url(array(
		‘commentspage’ => 5
	));

will return the absolute url of `article/read/4/showcomments:true/commentspage:5`.

### 1 string array + 1 associative array parameter
Returns the absolute url, adding the parameters.

	echo url(‘article/read/4’, array(
		‘showcomments’ => ‘true’
		‘commentspage’ => 1
	));

will return `//yourdomain.com/article/read/4/showcomments:true/commentspage:1`.

<a name="uri-helper"/>
## uri() or R76::uri()
Returns the current URI, which is the URL freed from root and GET parameters.

<a name="path-helper"/>
## path($key) or R76::path($key)
If `$key` is a string, it returns the value of the variable set in the root:

	// config: ROUTE GET articles/read/@id/ article->read
	// current uri: articles/read/134/
	echo path(‘id’); // will return ‘134’

If `$key` is numeric, it returns the nth part (zero-based) of the URI.

	// current uri: articles/read/134/
	echo path(2); // will return ‘134’
  
<a name="param-helper"/>
## param($key)
Return the value of the GET parameter `$key`. It's the same than $_GET[$key].

	// current url: articles/tag:webdesign
	echo param(‘tag’); // will return ‘webdesign’
	
<a name="params-helper"/>
## params()
Return an associative array of the GET parameters, strickly the same as `$_GET` values.

<a name="verb-helper"/>
## verb()
Returns the current verb (GET, POST, PUT, DELETE). Note: it returns the value of the `$_SERVER[‘REQUEST_METHOD’]` server variable.
  
<a name="async-helper"/>
## async()
Returns true if you’re using an AJAX request, and false if not. What defines an AJAX request is the value of the `X_REQUESTED_WITH` header set to `XMLHttpRequest`, used in nearly all of the JavaScript libraries that send AJAX requests.

<a name="go-helper"/>
## go($location)
Redirect to the specified url. If `$location` parameter is ommited, it will refresh the current page, using `url()` value.
