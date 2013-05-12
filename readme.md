# R76 router
R76 is a light-weight PHP framework that can hold any kind of project. It only provides [a router](site/core/) and an extended config file to help the files tree and code organization. You still the real master of your project, since R76 is optimized for both procedural or POO environments, and does not require any specific intern organization. Just make sure the `.htaccess` paths are correct. 

R76 is shared under a [CC BY-SA license](http://creativecommons.org/licenses/by-sa/3.0). 

See [Eye Fracture source](http://github.com/noclat/eyefracture.com) to get an advanced exemple of R76 usage.

# Documentation
- [Load the system](#load-the-system)
- [Configuration file](#configuration-file)
	- [UI](#ui)
	- [LOAD](#load)
	- [ROUTE](#route)
	- [DEFINE](#define)
	- [CUSTOM](#custom)
- [Tips](#tips)
	- [Syntax sensibility](#syntax-sensibility)
	- [Callbacks](#callbacks)
	- [GET parameters](#get-parameters)
- [Helpers and R76 public methods](#helpers-and-r76-public-method)
	- [root() or R76::root()](#root-helper)
	- [url() or R76::url()](#url-helper)
	- [verb() or R76::verb()](#verb-helper)
	- [uri() or R76::uri()](#uri-helper)
	- [path() or R76::path()](#path-helper)
	- [param() or R76::param()](#param-helper)
	- [params() or R76::params()](#params-helper)
	- [arg() or R76::arg()](#arg-helper)
	- [args() or R76::args()](#args-helper)
	- [ui() or R76::ui()](#ui-helper)
	- [render() or R76::render()](#render-helper)
	- [async()](#async-helper)
	- [go()](#go-helper)

# Load the system
The index.php needs to load the system. Here is an example of what it should looks like:

	<?php
	$site = include 'site/core/r76.php';
	$site->config('site/core/CONFIG');
	$site->run(function() { go(url('404')); });
	
The `config()` method could be called using both an array of commands or a file (one command per line).  
The `run()` method displays the result, and gets a callback in parameter, called when the URL doesn’t match any route configuration. See the [Callbacks section](#callbacks) below to know more about what is posssible to do with.

# Configuration file
The syntax is pretty simple: ‘command parameters’, one command per line, and you can comment a line by starting it with a ‘#’. Commands available: **LOAD**, **UI**, **ROUTE**, **DEFINE**, **CUSTOM**. This is a sample config file:

	# System
  		LOAD    site/core
  		UI      site/templates
  		
	# Routes
  		ROUTE   GET       /               site/templates/default
  		ROUTE   GET       /@section       site/templates/@section

## UI
Defines the path to the template files. It’s required if you want to use the inner [render system](#render-helper) (and trust me, you do want).

## LOAD
Loads all the php files located in the given folder path. You can set multiple folders to load, simply use the ‘;’ separator. E.g.:

	LOAD site/core;site/helpers;site/custom

## ROUTE
Route syntax is the most tricky, but still intuitive as hell.

	ROUTE verbs url callbacks arguments
	
You can allow any **verb** (GET, POST, PUT, DELETE) you want to access an url, and combine them by using the ‘|’ separator. The most common usage is: `GET|POST`.

The **url** respect the [path syntax](#syntax-sensibility). Anyway, to match the root, you’ll need to set the url as ‘/’. Note that [GET parameters](#get-parameters) aren’t part of the route. You can use variables in it for dynamic urls, just put an ‘@‘ before the name of the variable you want. To get their value, see [path() method](#path-helper). E.g.: 

	ROUTE GET /articles/@id/comments/page/@page callback
	
The **callback** can be a file, a function or a method (see the [Callbacks section](#callbacks) for more information), and is able to use the variables in its name:

	ROUTE GET       /@section               site/templates/@section  
	ROUTE GET|POST  /articles/@action/@id   articles->@action  
	ROUTE GET       /@section/@feature      @section->@feature
	
It’s 98% useless, but you can call multiple callbacks using the ‘;’ separator.
    
**Arguments** are free to ommit or set whether you want to transmit some extra information or not. It could be used to differentiate two paths that call the same callback.

	ROUTE GET       /articles/@id     site/templates/read   type:article
	ROUTE GET       /notes/@id        site/templates/read   type:note
	
There are other ways to do this without using arguments, for example, using the [path() helper](#path-helper), but it could be useful for the global legibility of your code. The syntax is `key:value`. Note that you can set multiple arguments using the ‘;’ separator. To get their value, see [arg() method](#arg-helper).

## DEFINE
Sets a global constant. It’s practical, simply because you can gather all your configuration constants in the same configuration file, like paths, passwords (hashed), services & API codes (google analytics, typekit, etc.) and so on.

	DEFINE key value

## CUSTOM
You can do what you want with the CUSTOM command. This is the syntax:

	CUSTOM callback parameters

It’s just calling a callback (function or method) with the given the parameters. The parameters syntax is simple, just use any spacing character between them: `parameter1 parameter2 parameter3…`. It could help if you need to parse some configuration values, for example an external URL (from any API or service like Google Documents), or set a bunch of related ‘constants’.

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
Any path you’ll have to write (in url() and render() functions and the configuration file) are parsed to prevent from any bug occuring with the ‘slash’ character confusing use. So you can both write `/path/` or `path/`, and even `path`.

In the configuration command lines, you can use as much inline spacing/tabs characters as you want between the values, and around ‘;’ separators. You are **not** allowed to put spaces when combining ROUTE verbs (GET, POST, PUT, DELETE).

Commands are not case sensitive, but paths are.

The URLs could be write both **with or without any extension**. `//example.com/sitemap` and `//example.com/sitemap.xml` are equaly regarded by the framework.

Any PHP file you call using in the render() function or the configuration file doesn’t need the `.php` extension. 

## Callbacks
Callbacks could be files, functions or methods. If it’s a file, it will just be included (and executed). If it’s a function or a method, just give the name, without the parenthesis. Examples: `load`, `article->read`, `articles::read`.

## GET parameters
GET parameters aren’t part of the route. Any The callback of `/articles/archives` url and `/articles/archives/sort:year%20asc` will be the same. You can access those parameters in your code to make changes according to their values.

Note: GET parameters will be rewrited from `?key=value&key2=value2` to `/key:value/key2:value2`, but still available using $_GET superglobal.
 

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

<a name="verb-helper"/>
## verb() or R76::verb()
Returns the current verb (GET, POST, PUT, DELETE).

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
## param($key) or R76::param($key)
Return the value of the GET parameter `$key`.

	// current url: articles/tag:webdesign
	echo param(‘tag’); // will return ‘webdesign’
	
<a name="params-helper"/>
## params() or R76::params()
Return an associative array of the GET parameters, strickly he same as `$_GET` values.

<a name="arg-helper"/>
## arg($key) or R76::arg($key)
Return the value of the argument `$key` set in the ROUTE command macthing the current URL.
	
	// config: ROUTE GET /articles/@id site/templates/read type:article
	echo arg(‘type’); // will return ‘article’
	
<a name="args-helper"/>
## args() or R76::args()
Return an associative array of the arguments set in the ROUTE command macthing the current URL.

<a name="ui-helper"/>
## ui() or R76::ui()
Return the UI path value set in the configuration.

<a name="render-helper"/>
## render($filename(, $data)) or R76::render($filename(, $data))
Include the file situated in the UI folder. You don’t need to specify the `.php` extension, and you can call files located in a subdirectory. In the example provided in this repository:

	render(‘default’); // includes ‘site/templates/default.php’
	render(‘snippets/header’); // includes ‘site/templates/snippets/header.php’
	
You can pass data as an associative array to the included file: 
	
	render(‘snippets/header’, array(
		‘title’ => ‘Eye Fracture’,
		‘description’ => ‘Discover the best trailers and cutscenes of your favorite games, all in one place.’
	));
	
They will be extracted as native variables which are only available on that specific file. So, in ‘site/templates/snippets/header.php’, we can use those information like this:

	<!doctype html>
	<html>
	<head>
		<title><?php echo $title ?></title>
		<meta name=“description” value=“<?php echo $description ?>”>
	</head>
	<body>
	</body>
	</html>
	
<a name="async-helper"/>
## async()
Returns true if you’re using an AJAX request, and false if not. What defines an AJAX request is the value of the `X_REQUESTED_WITH` header set to `XMLHttpRequest`, used in nearly all of the JavaScript libraries that send AJAX requests.

<a name="go-helper"/>
## go($location)
Redirect to the specified url. If `$location` parameter is ommited, it will refresh the current page, using `url()` value.
