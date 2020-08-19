<?php
declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Controllers;


use Guzaba2\Authorization\Role;
use Guzaba2\Http\Method;
use Guzaba2\Kernel\Kernel;
use Guzaba2\Mvc\Controller;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;
use Guzaba2\Translator\Translator as t;

class Navigation extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/navigation'         => [
                Method::HTTP_GET            => [self::class, 'main'],
                Method::HTTP_PATCH          => [self::class, 'update'],//used for the reordering
            ],
            '/navigation/{role_uuid}'   => [
                Method::HTTP_GET            => [self::class, 'navigation_for_role'],
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
        $links = \GuzabaPlatform\Navigation\Models\Navigation::get_all_links($root_link_id = null, $include_type_description = true);
        $struct = ['links' => $links];
        return self::get_structured_ok_response($struct);
    }

    /**
     * @param array $links
     * @return ResponseInterface
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     */
    public function update(array $links) : ResponseInterface
    {
        //print_r($this->get_request()->getBody()->getContents());
        \GuzabaPlatform\Navigation\Models\Navigation::update_all_links($links);
        $struct = ['message' => sprintf(t::_('The navigation structure is updated.'))];
        return self::get_structured_ok_response($struct);
    }

    /**
     * Returns the navigation for the provided role.
     * @param string $role_uuid
     * @return string
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    public function navigation_for_role(string $role_uuid): ResponseInterface
    {
        //$Role = new Role($role_uuid);
        $links = \GuzabaPlatform\Navigation\Models\Navigation::get_all_links(7);
        $struct = ['links' => $links];
        return self::get_structured_ok_response($struct);
    }

}