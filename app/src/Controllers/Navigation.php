<?php
declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Controllers;


use Guzaba2\Http\Method;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;
use Guzaba2\Translator\Translator as t;

class Navigation extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/navigation' => [
                Method::HTTP_GET => [self::class, 'main'],
                Method::HTTP_PATCH => [self::class, 'update'],
            ],
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Returns the navigation tree.
     * @return ResponseInterface
     */
    public function main() : ResponseInterface
    {
        //$links = \GuzabaPlatform\Navigation\Models\Navigation::get_links();
        $links = \GuzabaPlatform\Navigation\Models\Navigation::get_all_links();
        print_r($links);
        $struct = ['links' => $links];
        return self::get_structured_ok_response($struct);
    }

    public function update(array $links) : ResponseInterface
    {
        print_r($links);
        //print_r($this->get_request()->getBody()->getContents());
        \GuzabaPlatform\Navigation\Models\Navigation::update_all_links($links);
        $struct = ['message' => sprintf(t::_('The navigation structure is updated.'))];
        return self::get_structured_ok_response($struct);
    }
}