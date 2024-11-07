<?php
/*
MIT License

Copyright (c) 2012 Danny van Kooten <hi@dannyvankooten.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

class AltaRouter
{
    /**
     * @var array Array of all routes (incl. named routes).
     */
    protected static $routes = [];

    /**
     * @var array Array of routes manually created.
     */
    protected static $routesCreatedByMap = [];

    /**
     * @var ?string name of the file to be created to cache routes.
     */
    protected ?string $cacheFile = null;
    protected mixed $handleCacheFile = null;

    /**
     * @var string Can be used to ignore leading part of the Request URL (if main file lives in subdirectory of host)
     */
    protected $basePath = '';

    /**
     * @var string Position the top level directory of controllers files
     */
    protected $controllerDirectory = '';
    protected $nameSpace = '';

    /**
     * @var array Array of default match types (regex helpers)
     */
    protected $matchTypes = [
        'i' => '[0-9]++',
        'a' => '[0-9A-Za-z]++',
        'h' => '[0-9A-Fa-f]++',
        '*' => '.+?',
        '**' => '.++',
        '' => '[^/\.]++'
    ];

    private $idx = 0;

    /**
     * Create router in one call from config.
     *
     * @param string $cacheFile
     * @param string $basePath
     * @param array $matchTypes
     */
    public function __construct(
        string $controllerDirectory,
        string $nameSpace,
        string $cacheFile = './routes.cache.php',
        string $basePath = '',
        array $matchTypes = []
    ) {
        $this->cacheFile = $cacheFile;
        if (!str_ends_with($nameSpace, '\\')) {
            $nameSpace .= '\\';
        }
        $this->nameSpace = $nameSpace;
        if (!str_ends_with($controllerDirectory, '/')) {
            $controllerDirectory .= '/';
        }
        $this->controllerDirectory = $controllerDirectory;
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
    }

    /**
     * Retrieves all routes.
     * Useful if you want to process or display routes.
     * @return array All routes.
     */
    public function getRoutes(): array
    {
        return [...self::$routesCreatedByMap, ...self::$routes];
    }

    /**
     * Set the base path.
     * Useful if you are running your application from a subdirectory.
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes(array $matchTypes): void
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     * Define a route outside attribute mechanism
     *
     * @param string $method One of 5 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     * @param string|null $name Optional name of this route. Supply if you want to reverse route this url in your application.
     * @throws Exception
     */
    public function map(string $method, string $route, mixed $target, string $name = null): void
    {

        if ($name) {
            if (isset(self::$routesCreatedByMap[$name])) {
                throw new \RuntimeException("Can not redeclare route '{$name}'");
            }
            self::$routesCreatedByMap[$name] = [$method, $route, $target, $name, null];
        } else {
            self::$routesCreatedByMap['map_' . $this->idx++] = [$method, $route, $target, $name, null];
        }
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName The name of the route.
     * @param array $params Associative array of parameters to replace placeholders with.
     * @return string The URL of the route with named parameters in place.
     * @throws Exception
     */
    public function generate(string $routeName, array $params = []): string
    {

        $routes = [...self::$routesCreatedByMap, ...self::$routes];
        // Check if named route exists
        if (!isset($routes[$routeName])) {
            throw new \RuntimeException("Route '{$routeName}' does not exist.");
        }

        // Replace named parameters
        $route = $routes[$routeName][1];

        // prepend base path to route url again
        $url = $this->basePath . $route;

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if ($pre) {
                    $block = substr($block, 1);
                }

                if (isset($params[$param])) {
                    // Part is found, replace for param value
                    $url = str_replace($block, $params[$param], $url);
                } elseif ($optional && $index !== 0) {
                    // Only strip preceding slash if it's not at the base
                    $url = str_replace($pre . $block, '', $url);
                } else {
                    // Strip match block
                    $url = str_replace($block, '', $url);
                }
            }
        }

        return $url;
    }

    /**
     * Match a given Request Url against stored routes
     * @param string|null $requestUrl
     * @param string|null $requestMethod
     * @return array|false Array with route information on success, false on failure (no match).
     */
    public function matchUncached(?string $requestUrl = null, ?string $requestMethod = null): false|array
    {

        $params = [];

        // set Request Url if it isn't passed as parameter
        if ($requestUrl === null) {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        }

        // strip base path from request url
        $requestUrl = substr($requestUrl, strlen($this->basePath));

        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        $lastRequestUrlChar = $requestUrl ? $requestUrl[strlen($requestUrl) - 1] : '';

        // set Request Method if it isn't passed as a parameter
        if ($requestMethod === null) {
            $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        }

        foreach (self::$routes as $handler) {
            list($methods, $route, $target, $name, $fullFileName) = $handler;
            $method_match = (stripos($methods, $requestMethod) !== false);

            // Method did not match, continue to next route.
            if (!$method_match) {
                continue;
            }

            if ($route === '*') {
                // * wildcard (matches all)
                $match = true;
            } elseif (isset($route[0]) && $route[0] === '@') {
                // @ regex delimiter
                $pattern = '`' . substr($route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params) === 1;
            } elseif (($position = strpos($route, '[')) === false) {
                // No params in url, do string comparison
                $match = strcmp($requestUrl, $route) === 0;
            } else {
                // Compare longest non-param string with url before moving on to regex
                // Check if last character before param is a slash, because it could be optional if param is optional
                // too (see https://github.com/dannyvankooten/AltoRouter/issues/241)
                if (strncmp(
                    $requestUrl,
                    $route,
                    $position
                ) !== 0 && ($lastRequestUrlChar === '/' || $route[$position - 1] !== '/')) {
                    continue;
                }

                $regex = $this->compileRoute($route);
                $match = preg_match($regex, $requestUrl, $params) === 1;
            }

            if ($match) {
                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) {
                            unset($params[$key]);
                        }
                    }
                }

                return [
                    'target' => $target,
                    'params' => $params,
                    'name' => $name,
                    'file' => $fullFileName
                ];
            }
        }

        return false;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     * @param string $route
     * @return string
     */
    protected function compileRoute(string $route): string
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                $optional = $optional !== '' ? '?' : null;

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . ')'
                    . $optional
                    . ')'
                    . $optional;

                $route = str_replace($block, $pattern, $route);
            }
        }
        return "`^$route$`u";
    }

    /**
     * @param string $base
     * @param string $nameSpace
     * @return void
     * @throws ReflectionException
     */
    private function listClassController(string $base, string $nameSpace): void
    {
        // On parcoure toutes les classes dur repertoire src/Controller
        // Répertoire où sont stockées les classes

        // Fichier résultat
        // Récupérer tous les fichiers PHP dans ce répertoire
        $files = opendir($base);

        // Inclure et analyser chaque fichier
        while ($file = readdir($files)) {
            // Obtenir le nom de la classe dans chaque fichier PHP
            // Supposons que le nom de la classe est le même que le nom du fichier
            if (is_dir($base . '/' . $file)) {
                if ($file !== '.' && $file !== '..') {
                    $this->listClassController($base . $file . '/', $nameSpace . $file . '\\');
                }
            } else {
                $chars = pathinfo($file, PATHINFO_ALL);
                if ($chars['extension'] === 'php') {
                    $baseFile = $chars['filename'];
                    $fullClassName = $nameSpace . $baseFile;
                    $fullFileName = $base . $file;
                    // Routes
                    $refl = new ReflectionClass($fullClassName);
                    foreach ($refl->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                        $methodAttributes = new \ReflectionMethod($method->class, $method->name);
                        $attributes = $methodAttributes->getAttributes(Route::class);
                        if (count($attributes) > 0) {
                            foreach ($attributes as $attribute) {
                                $args = $attribute->getArguments();
                                if (!is_array($args['method'])) {
                                    $methods = [$args['method']];
                                } else {
                                    $methods = $args['method'];
                                }
                                foreach ($methods as $m) {
                                    $name = isset($args['name']) ? "'{$args['name']}'" : $this->idx++;
                                    fprintf(
                                        $this->handleCacheFile,
                                        "%s=>['%s','%s',['%s','%s'],'%s','%s'],\n",
                                        $name,
                                        $m,
                                        $args['route'],
                                        str_replace($this->nameSpace, '', $method->class),
                                        $method->name,
                                        $args['name'] ?? null,
                                        str_replace($this->controllerDirectory, '', $fullFileName)
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        closedir($files);
    }

    /**
     * @param string $directory
     * @return void
     * @throws Exception
     */
    private function generateRoutesFromAttributes(string $directory, string $nameSpace): void
    {
        $this->handleCacheFile = fopen($this->cacheFile, "w+");
        if (!$this->handleCacheFile) {
            throw new Exception("Open file error");
        }
        fprintf($this->handleCacheFile, "<?php\n\nself::\$routes = [\n...self::\$routesCreatedByMap,\n");
        $this->listClassController($directory, $nameSpace);
        fprintf($this->handleCacheFile, "\n];\n");
        fclose($this->handleCacheFile);
    }

    /**
     * Try to match a route among those already defined
     *
     * @param $requestUrl
     * @param $requestMethod
     * @return array|false
     * @throws Exception
     */
    public function match($requestUrl = null, $requestMethod = null): false|array
    {
        $cacheFile = $this->cacheFile;


        // Try to load cache if exists
        if (!file_exists($cacheFile)) {
            // Cache does no exists
            $this->generateRoutesFromAttributes($this->controllerDirectory, $this->nameSpace);
            if (!file_exists($cacheFile)) {
                // Die!
                throw new RuntimeException("Route cache file error");
            }
        }
        include $cacheFile;
        $match = $this->matchUncached($requestUrl, $requestMethod);

        // Route found, check if cache created before last controller update
        if ($match &&
            $match['file'] &&
            (filemtime($this->controllerDirectory . $match['file']) > filemtime($cacheFile))
        ) {
            // The cache could be not is not up to date
            $this->generateRoutesFromAttributes($this->controllerDirectory, $this->nameSpace);
            if (!file_exists($cacheFile)) {
                // Die!
                throw new RuntimeException("Route cache file error");
            }
            include $cacheFile;
            $match = $this->matchUncached($requestUrl, $requestMethod);
        }

        return $match;
    }
}
