<?php
declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Controllers;

use Guzaba2\Http\Method;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;
use Guzaba2\Translator\Translator as t;

class NavigationLink extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/navigation/link' => [
                Method::HTTP_POST => [self::class, 'create_link'],
            ],
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Creates a new link.
     * A new link points to an object (requires $class_name & $object_uuid) or is a redirect to $redirect.
     * The rest of the actions are provided by the DefaultController and the route is defined in the Model
     * @param string $class_name
     * @param string $object_uuid
     * @param string $redirect
     * @return ResponseInterface
     */
    public function create_link(string $link_name, ?string $parent_link_uuid = NULL, ?string $link_class_name = NULL, ?string $link_object_uuid = NULL, ?string $link_redirect = NULL) : ResponseInterface
    {
        $Link = \GuzabaPlatform\Navigation\Models\NavigationLink::create($link_name, $parent_link_uuid, $link_class_name, $link_object_uuid, $link_redirect);
        $struct = [
            'message' => sprintf(t::_('A link %1s was created.'), $link_name)
        ];
        return self::get_structured_ok_response($struct);
    }
}