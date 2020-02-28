<?php
declare(strict_types=1);


namespace GuzabaPlatform\Navigation\Models;

use Guzaba2\Base\Exceptions\InvalidArgumentException;
use Guzaba2\Orm\Store\Sql\Mysql;
use GuzabaPlatform\Platform\Application\BaseActiveRecord;
use GuzabaPlatform\Platform\Application\MysqlConnectionCoroutine;

/**
 * Class NavigationLink
 * @package GuzabaPlatform\Navigation\Models
 * @property link_id
 * @property parent_link_id
 * @property link_class_name
 * @property link_object_id
 * @property link_name
 * @property link_redirect
 */
class NavigationLink extends BaseActiveRecord
{
    protected const CONFIG_DEFAULTS = [
        'main_table'            => 'navigation_links',
        'route'                 => '/admin/navigation/link',//to be used for editing and deleting
    ];

    protected const CONFIG_RUNTIME = [];


    public static function create(string $link_name, ?string $parent_link_uuid = NULL, ?string $link_class_name = NULL, ?string $link_object_uuid = NULL, ?string $link_redirect = NULL) : self
    {
        $parent_link_id = NULL;
        if ($parent_link_uuid) {
            $ParentLink = new static($parent_link_uuid);
            $parent_link_id = $ParentLink->get_id();
        }
        $link_object_id = NULL;
        if ($link_class_name && $link_object_uuid) {
            $Object = new $link_class_name($link_object_uuid);
            $link_object_id = $Object->get_id();
        }
        if (!$link_name) {
            throw new InvalidArgumentException(sprintf(t::_('No $link_name argument provided.')));
        }
        $Link = new static();
        $Link->link_name = $link_name;
        $Link->parent_link_id = $parent_link_id;
        $Link->link_class_name = $link_class_name;
        $Link->link_object_id = $link_object_id;
        $Link->link_redirect = $link_redirect;
        $Link->write();
        return $Link;
    }

    protected function _before_delete() : void
    {
        //delete all child nodes
    }
}