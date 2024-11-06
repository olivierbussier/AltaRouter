<?php



class TestController
{
    #[Route(method: 'get', route: '/test')]
    public function test()
    {
    }

    #[Route(method: 'get', route: '/test2', name: 'myTest')]
    public function test2()
    {
    }
}