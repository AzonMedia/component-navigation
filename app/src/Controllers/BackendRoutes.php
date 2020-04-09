<?php
declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Controllers;


use Guzaba2\Base\Exceptions\InvalidArgumentException;
use Guzaba2\Base\Exceptions\RunTimeException;
use Guzaba2\Http\Method;
use Guzaba2\Routing\RoutingMiddleware;
use GuzabaPlatform\Platform\Application\BaseController;
use GuzabaPlatform\Platform\Application\Middlewares;
use Psr\Http\Message\ResponseInterface;

class BackendRoutes extends BaseController
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
     * @throws RunTimeException
     * @throws InvalidArgumentException
     */
    public function main() : ResponseInterface
    {
        $content = [];

        /** @var Middlewares $Middlewares */
        $Middlewares = self::get_service('Middlewares');
        $RoutingMiddleware = $Middlewares->get_middleware(RoutingMiddleware::class);
        $Router = $RoutingMiddleware->get_router();

        $content['routes'] = $Router->get_routes(Method::HTTP_GET);

        $struct = [ 'content' => $content];

        return self::get_structured_ok_response($struct);
    }
}