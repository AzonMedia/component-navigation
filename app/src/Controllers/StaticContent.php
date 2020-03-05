<?php
declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Controllers;


use Guzaba2\Http\Method;
use Guzaba2\Routing\RoutingMiddleware;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class StaticContent extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/navigation/static-content' => [
                Method::HTTP_GET => [self::class, 'main'],
            ],
        ],
        'services'      => [
            'Middlewares'
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * @return ResponseInterface
     */
    public function main() : ResponseInterface
    {
        $content = [];


        $Middlewares = self::get_service('Middlewares');
        $RoutingMiddleware = $Middlewares->get_middleware(RoutingMiddleware::class);
        $Router = $RoutingMiddleware->get_router();

        $content['routes'] = $Router->get_routes(Method::HTTP_GET);

        $struct = [ 'content' => $content];

        return self::get_structured_ok_response($struct);
    }
}