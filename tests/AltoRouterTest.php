<?php

require 'vendor/autoload.php';

class AltaRouterDebug extends AltaRouter
{
    public function __construct(string $controllerDirectory = 'tests/Controller', string $nameSpace = '', string $cacheFile = './routes.cache.php', string $basePath = '', array $matchTypes = [])
    {
        parent::__construct($controllerDirectory, $nameSpace, $cacheFile, $basePath, $matchTypes);
    }

    public function getNamedRoutes()
    {
        return $this->getRoutes();
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function clearRoutes()
    {
        self::$routes = [];
        self::$routesCreatedByMap = [];
    }
}

include_once 'tests/Controller/TestController.php';

class AltoRouterTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var AltaRouter
     */
    protected $router;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->router = new AltaRouterDebug('./tests/Controller', '', './routes.cache.php');
        $this->router->clearRoutes();
        //unlink('./routes.cache.php');
    }

    /**
     * @covers AltaRouter::getRoutes
     */
    public function testGetRoutes()
    {
        $method = 'POST';
        $route = '/[:controller]/[:action]';
        $target = static function () {
        };

        $this->assertIsArray($this->router->getRoutes());
        // $this->assertIsArray($this->router->getRoutes()); // for phpunit 7.x
        $this->router->map($method, $route, $target);
        $this->assertEquals(['map_0' => [$method, $route, $target, null, null]], $this->router->getRoutes());
    }

    /**
     * @covers AltaRouter::setBasePath
     */
    public function testSetBasePath()
    {
        $this->router->setBasePath('/some/path');
        $this->assertEquals('/some/path', $this->router->getBasePath());

        $this->router->setBasePath('/some/path');
        $this->assertEquals('/some/path', $this->router->getBasePath());
    }

    /**
     * @covers AltaRouter::map
     */
    public function testMap()
    {
        $method = 'POST';
        $route = '/[:controller]/[:action]';
        $target = static function () {
        };

        $this->router->map($method, $route, $target);

        $routes = $this->router->getRoutes();

        $this->assertEquals([$method, $route, $target, null, null], $routes['map_0']);
    }

    /**
     * @covers AltaRouter::map
     */
    public function testMapWithName()
    {
        $method = 'POST';
        $route = '/[:controller]/[:action]';
        $target = static function () {
        };
        $name = 'myroute';

        $this->router->map($method, $route, $target, $name);

        $routes = $this->router->getRoutes();
        $this->assertEquals([$method, $route, $target, $name, null], $routes['myroute']);

        $named_routes = $this->router->getNamedRoutes();
        $this->assertEquals($route, $named_routes[$name][1]);

        try {
            $this->router->map($method, $route, $target, $name);
            $this->fail('Should not be able to add existing named route');
        } catch (Exception $e) {
            $this->assertEquals("Can not redeclare route '{$name}'", $e->getMessage());
        }
    }


    /**
     * @covers AltaRouter::generate
     */
    public function testGenerate()
    {
        $params = [
            'controller' => 'test',
            'action' => 'someaction'
        ];

        $this->router->map('GET', '/[:controller]/[:action]', static function () {
        }, 'foo_route');

        $this->assertEquals(
            '/test/someaction',
            $this->router->generate('foo_route', $params)
        );

        $params = [
            'controller' => 'test',
            'action' => 'someaction',
            'type' => 'json'
        ];

        $this->assertEquals(
            '/test/someaction',
            $this->router->generate('foo_route', $params)
        );
    }

    /**
     * @covers AltaRouter::generate
     */
    public function testGenerateWithOptionalUrlParts()
    {
        $this->router->map('GET', '/[:controller]/[:action].[:type]?', static function () {
        }, 'bar_route');

        $params = [
            'controller' => 'test',
            'action' => 'someaction'
        ];

        $this->assertEquals(
            '/test/someaction',
            $this->router->generate('bar_route', $params)
        );

        $params = [
            'controller' => 'test',
            'action' => 'someaction',
            'type' => 'json'
        ];

        $this->assertEquals(
            '/test/someaction.json',
            $this->router->generate('bar_route', $params)
        );
    }

    /**
     * GitHub #98
     * @covers AltaRouter::generate
     */
    public function testGenerateWithOptionalPartOnBareUrl()
    {
        $this->router->map('GET', '/[i:page]?', static function () {
        }, 'bare_route');

        $params = [
            'page' => 1
        ];

        $this->assertEquals(
            '/1',
            $this->router->generate('bare_route', $params)
        );

        $params = [];

        $this->assertEquals(
            '/',
            $this->router->generate('bare_route', $params)
        );
    }

    /**
     * @covers AltaRouter::generate
     */
    public function testGenerateWithNonexistingRoute()
    {
        try {
            $this->router->generate('nonexisting_route');
            $this->fail('Should trigger an exception on nonexisting named route');
        } catch (Exception $e) {
            $this->assertEquals("Route 'nonexisting_route' does not exist.", $e->getMessage());
        }
    }

    /**
     * @covers AltaRouter::match
     * @covers AltaRouter::compileRoute
     */
    public function testMatch()
    {
        $this->router->map('GET', '/foo/[:controller]/[:action]', 'foo_action', 'foo_route');

        $this->assertEquals([
            'target' => 'foo_action',
            'params' => [
                'controller' => 'test',
                'action' => 'do'
            ],
            'name' => 'foo_route',
            'file' => null
        ], $this->router->match('/foo/test/do', 'GET'));

        $this->assertFalse($this->router->match('/foo/test/do', 'POST'));

        $this->assertEquals([
            'target' => 'foo_action',
            'params' => [
                'controller' => 'test',
                'action' => 'do'
            ],
            'name' => 'foo_route',
            'file' => null
        ], $this->router->match('/foo/test/do?param=value', 'GET'));
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithNonRegex()
    {
        $this->router->map('GET', '/about-us', 'PagesController#about', 'about_us');

        $this->assertEquals([
            'target' => 'PagesController#about',
            'params' => [],
            'name' => 'about_us',
            'file' => null
        ], $this->router->match('/about-us', 'GET'));

        $this->assertFalse($this->router->match('/about-us', 'POST'));
        $this->assertFalse($this->router->match('/about', 'GET'));
        $this->assertFalse($this->router->match('/about-us-again', 'GET'));
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithFixedParamValues()
    {
        $this->router->map('POST', '/users/[i:id]/[delete|update:action]', 'usersController#doAction', 'users_do');

        $this->assertEquals([
            'target' => 'usersController#doAction',
            'params' => [
                'id' => 1,
                'action' => 'delete'
            ],
            'name' => 'users_do',
            'file' => null
        ], $this->router->match('/users/1/delete', 'POST'));

        $this->assertFalse($this->router->match('/users/1/delete', 'GET'));
        $this->assertFalse($this->router->match('/users/abc/delete', 'POST'));
        $this->assertFalse($this->router->match('/users/1/create', 'GET'));
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithPlainRoute()
    {
        $router = $this->getMockBuilder('AltaRouterDebug')
            ->setMethods(['compileRoute'])
            ->getMock();

        // this should prove that compileRoute is not called when the route doesn't
        // have any params in it
        $router->expects($this->never())
            ->method('compileRoute');

        $router->map('GET', '/contact', 'website#contact', 'contact');

        // exact match, so no regex compilation necessary
        $this->assertEquals([
            'target' => 'website#contact',
            'params' => [],
            'name' => 'contact',
            'file' => null
        ], $router->match('/contact', 'GET'));

        // no prefix match, so no regex compilation necessary
        $this->assertFalse($router->match('/page1', 'GET'));
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithServerVars()
    {
        $this->router->map('GET', '/foo/[:controller]/[:action]', 'foo_action', 'foo_route');

        $_SERVER['REQUEST_URI'] = '/foo/test/do';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertEquals([
            'target' => 'foo_action',
            'params' => [
                'controller' => 'test',
                'action' => 'do'
            ],
            'name' => 'foo_route',
            'file' => null
        ], $this->router->match());
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithOptionalUrlParts()
    {
        $this->router->map('GET', '/bar/[:controller]/[:action].[:type]?', 'bar_action', 'bar_route');

        $this->assertEquals([
            'target' => 'bar_action',
            'params' => [
                'controller' => 'test',
                'action' => 'do',
                'type' => 'json'
            ],
            'name' => 'bar_route',
            'file' => null
        ], $this->router->match('/bar/test/do.json', 'GET'));

        $this->assertEquals([
            'target' => 'bar_action',
            'params' => [
                'controller' => 'test',
                'action' => 'do'
            ],
            'name' => 'bar_route',
            'file' => null
        ], $this->router->match('/bar/test/do', 'GET'));
    }

    /**
     * GitHub #98
     * @covers AltaRouter::match
     */
    public function testMatchWithOptionalPartOnBareUrl()
    {
        $this->router->map('GET', '/[i:page]?', 'bare_action', 'bare_route');

        $this->assertEquals([
            'target' => 'bare_action',
            'params' => [
                'page' => 1
            ],
            'name' => 'bare_route',
            'file' => null
        ], $this->router->match('/1', 'GET'));

        $this->assertEquals([
            'target' => 'bare_action',
            'params' => [],
            'name' => 'bare_route',
            'file' => null
        ], $this->router->match('/', 'GET'));
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithWildcard()
    {
        $this->router->map('GET', '/a', 'foo_action', 'foo_route');
        $this->router->map('GET', '*', 'bar_action', 'bar_route');

        $this->assertEquals([
            'target' => 'bar_action',
            'params' => [],
            'name' => 'bar_route',
            'file' => null
        ], $this->router->match('/everything', 'GET'));
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithCustomRegexp()
    {
        $this->router->map('GET', '@^/[a-z]*$', 'bar_action', 'bar_route');

        $this->assertEquals([
            'target' => 'bar_action',
            'params' => [],
            'name' => 'bar_route',
            'file' => null
        ], $this->router->match('/everything', 'GET'));

        $this->assertFalse($this->router->match('/some-other-thing', 'GET'));
    }

    /**
     * @covers AltaRouter::match
     */
    public function testMatchWithUnicodeRegex()
    {
        $pattern = '/(?<path>[^';
        // Arabic characters
        $pattern .= '\x{0600}-\x{06FF}';
        $pattern .= '\x{FB50}-\x{FDFD}';
        $pattern .= '\x{FE70}-\x{FEFF}';
        $pattern .= '\x{0750}-\x{077F}';
        // Alphanumeric, /, _, - and space characters
        $pattern .= 'a-zA-Z0-9\/_\-\s';
        // 'ZERO WIDTH NON-JOINER'
        $pattern .= '\x{200C}';
        $pattern .= ']+)';

        $this->router->map('GET', '@' . $pattern, 'unicode_action', 'unicode_route');

        $this->assertEquals([
            'target' => 'unicode_action',
            'name' => 'unicode_route',
            'params' => [
                'path' => '大家好'
            ],
            'file' => null
        ], $this->router->match('/大家好', 'GET'));

        $this->assertFalse($this->router->match('/﷽‎', 'GET'));
    }

    public function testMatchWithSlashBeforeOptionalPart()
    {
        $this->router->map('GET', '/archives/[lmin:category]?', 'Article#archives');
        $expected = [
            'target' => 'Article#archives',
            'params' => [],
            'name' => null,
            'file' => null
        ];

        $this->assertEquals($expected, $this->router->match('/archives/', 'GET'));
        $this->assertEquals($expected, $this->router->match('/archives', 'GET'));
    }

    /**
     * @covers AltaRouter::addMatchTypes
     */
    public function testMatchWithCustomNamedRegex()
    {
        $this->router->addMatchTypes(['cId' => '[a-zA-Z]{2}[0-9](?:_[0-9]++)?']);
        $this->router->map('GET', '/bar/[cId:customId]', 'bar_action', 'bar_route');

        $this->assertEquals([
            'target' => 'bar_action',
            'params' => [
                'customId' => 'AB1',
            ],
            'name' => 'bar_route',
            'file' => null
        ], $this->router->match('/bar/AB1', 'GET'));

        $this->assertEquals([
            'target' => 'bar_action',
            'params' => [
                'customId' => 'AB1_0123456789',
            ],
            'name' => 'bar_route',
            'file' => null
        ], $this->router->match('/bar/AB1_0123456789', 'GET'));

        $this->assertFalse($this->router->match('/some-other-thing', 'GET'));
    }

    /**
     * @covers AltaRouter::addMatchTypes
     */
    public function testMatchWithCustomNamedUnicodeRegex()
    {
        $pattern = '[^';
        // Arabic characters
        $pattern .= '\x{0600}-\x{06FF}';
        $pattern .= '\x{FB50}-\x{FDFD}';
        $pattern .= '\x{FE70}-\x{FEFF}';
        $pattern .= '\x{0750}-\x{077F}';
        $pattern .= ']+';

        $this->router->addMatchTypes(['nonArabic' => $pattern]);
        $this->router->map('GET', '/bar/[nonArabic:string]', 'non_arabic_action', 'non_arabic_route');

        $this->assertEquals([
            'target' => 'non_arabic_action',
            'name' => 'non_arabic_route',
            'params' => [
                'string' => 'some-path'
            ],
            'file' => null
        ], $this->router->match('/bar/some-path', 'GET'));

        $this->assertFalse($this->router->match('/﷽‎', 'GET'));
    }

    public function testAttributeGeneratedRoute()
    {
        $this->assertEquals([
            'target' => [TestController::class, 'test'],
            'name' => null,
            'params' => [],
            'file'  => './tests/Controller/TestController.php'
        ], $this->router->match('/test', 'get'));

        $this->assertEquals([
            'target' => [TestController::class, 'test2'],
            'name' => 'myTest',
            'params' => [],
            'file'  => './tests/Controller/TestController.php'
        ], $this->router->match('/test2', 'get'));
        echo "";
    }
}