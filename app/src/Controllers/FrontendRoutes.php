<?php
declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Controllers;


use Guzaba2\Http\Method;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class FrontendRoutes extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/navigation/frontend-routes' => [
                Method::HTTP_GET => [self::class, 'main'],
            ],
        ],
        'services'      => [
            'FrontendRouter'
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    public function main() : ResponseInterface
    {
        $routes = self::get_service('FrontendRouter')->get_routes_as_array();
        $struct = ['routes' => $routes];
        return self::get_structured_ok_response($struct);
    }
}