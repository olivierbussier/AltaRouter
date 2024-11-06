# AltoRouter  ![PHP status](https://github.com/dannyvankooten/AltoRouter/workflows/PHP/badge.svg) [![Latest Stable Version](https://poser.pugx.org/altorouter/altorouter/v/stable.svg)](https://packagist.org/packages/altorouter/altorouter) [![License](https://poser.pugx.org/altorouter/altorouter/license.svg)](https://packagist.org/packages/altorouter/altorouter)

AltaRouter is a fork of AltoRouter, light weighted package adding route attributes capabilities and efficient route caching and checking mechanism

Usage: 

```php

use App\Router\Route;

// map homepage controller
#[Route(method: 'get', route: 'home', name: 'home-page')]
public function home()
{
    ...
}

// Route matching
// Optional parameters 'route' and 'httpMethod', match could fetch them directly from $_SERVER

$match = $this->match($route, $httpMethod);
...
```

## AltaRouter Features

* Routes created automatically by AltaRouter using attribute mechanism
* Routes cached in a php file, this file is rebuild if the controller file of the requested route more recent than cache file
* Instead of multiple 'map' call, a single include with all routes in an array is done at init

## And of course, as AltoRouter
* Can be used with all HTTP Methods
* Dynamic routing with named route parameters
* Reversed routing
* Flexible regular expression routing (inspired by [Sinatra](http://www.sinatrarb.com/))
* Custom regexes

This doc covers only AltaRouter usage with php attributes, refer to the [AltoRouter documentation](https://dannyvankooten.github.io/AltoRouter) for everything else

## Getting started

You need PHP >= 8.0 to use AltoRouter.

## AltaRouter installation

AltaRouter respect PSR4 autoloading rules. The best way to include AltaRouter in your project is to use composer

`composer require altarouter/altarouter`

Follow [Rewrite all requests to AltoRouter](https://dannyvankooten.github.io/AltoRouter//usage/rewrite-requests.html) explanations to
redirect http requests to altarouter

## Route mapping

Route mapping could be done using 'map' method, see [Map your routes](https://dannyvankooten.github.io/AltoRouter//usage/mapping-routes.html) for usage explanations

AltaRouter provides you the capability to declare routes on top of the controller:
```php
#[Route(method: method, route: url, name: routeName)
```

### Description of parameters:
__method__
- (string) : 'get', 'post', 'put', 'delete', ...
- (array) : ['get', 'post']

route:
- (string) : url of the route, like 'account/rights'

name:
- (string optional) : name of the route. The route name is mandatory if you want to build later url routes for
buttons or anchors

### Match requests amon defined routes

The route matching is identical at those defined in Altorouter docs : [Match requests](https://dannyvankooten.github.io/AltoRouter//usage/matching-requests.html) and [Process the request your preferred way](https://dannyvankooten.github.io/AltoRouter//usage/processing-requests.html)

## 

## Contributors
- [Danny van Kooten](https://github.com/dannyvankooten)
- [Koen Punt](https://github.com/koenpunt)
- [John Long](https://github.com/adduc)
- [Niahoo Osef](https://github.com/niahoo)

## License

MIT License

Copyright (c) 2012 Danny van Kooten <hi@dannyvankooten.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
