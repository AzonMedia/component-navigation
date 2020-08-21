<?php

declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Models;

use Guzaba2\Base\Base;
use Guzaba2\Base\Exceptions\InvalidArgumentException;
use Guzaba2\Base\Exceptions\LogicException;
use Guzaba2\Database\Sql\Interfaces\ConnectionInterface;
use Guzaba2\Http\Method;
use Guzaba2\Mvc\Interfaces\ControllerInterface;
use Guzaba2\Orm\ActiveRecordDefaultController;
use Guzaba2\Orm\Interfaces\ActiveRecordInterface;
use Guzaba2\Orm\Store\Sql\Mysql;
use Guzaba2\Routing\ActiveRecordDefaultRoutingMap;
use Guzaba2\Routing\Interfaces\RoutingMiddlewareInterface;
use Guzaba2\Translator\Translator as t;
use GuzabaPlatform\Platform\Application\GuzabaPlatform;
use GuzabaPlatform\Platform\Application\Middlewares;
use GuzabaPlatform\Platform\Application\MysqlConnectionCoroutine;


/**
 * Class Navigation
 * @package GuzabaPlatform\Navigation\Models
 */
class Navigation extends Base
{

    protected const CONFIG_DEFAULTS = [
        'services'              => [
            'ConnectionFactory',
            'MysqlOrmStore',
            'Middlewares'
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Currently used only for reordering and restructuring of the links.
     * No create, update or delete.
     * @return array
     */
    public static function update_all_links(array $links) : void
    {
        $Function = static function(?int $parent_link_id, array $links) use (&$Function) : void
        {
            $links_count = count($links);
            for ($aa = 0; $aa < $links_count; $aa++) {
                $Link = new NavigationLink($links[$aa]['link_id']);
                $Link->link_order = $aa;
                $Link->parent_link_id = $parent_link_id;
                //to allow update
                //$Link->link_name = $links[$aa]['link_name'];
                $Link->write();
                $Function($links[$aa]['link_id'], $links[$aa]['children']);
            }
        };
        $Function(NULL, $links);
    }

    /**
     * Returns all links
     * @param string|null $parent_link_uuid
     * @return array
     */
    public static function get_links(?string $parent_link_uuid = NULL) : array
    {
        $parent_link_id = NULL;
        if ($parent_link_uuid) {
            $ParentLink = new static($parent_link_uuid);
            $parent_link_id = $ParentLink->get_id();
        }
        $links = self::get_all_links();
        $Function = static function (?int $parent_link_id) use ($links) : array
        {
            $ret = [];
            foreach ($links as $link) {
                if ($link['parent_link_id'] === $parent_link_id) {
                    $ret[] = $link;
                }
            }
            return $ret;
        };
        return $Function($parent_link_id);
    }

    /**
     * Returns multidimensional array with the navigation structure (tree)
     * @param int|null $root_link_id
     * @param bool $include_type_description
     * @return array
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     */
    public static function get_all_links(?int $root_link_id = null) : array
    {
        /** @var ConnectionInterface $Connection */
        $Connection = self::get_service('ConnectionFactory')->get_connection(MysqlConnectionCoroutine::class, $CON);
        $main_table = NavigationLink::get_main_table();
        $meta_table = Mysql::get_meta_table();
        $classes_table = self::get_service('MysqlOrmStore')::get_class_table();
        $q = "
SELECT
    links.*,
    meta.*,
    classes.class_uuid AS link_class_uuid,
    classes.class_id AS link_class_id,
    object_meta.meta_object_uuid AS link_object_uuid,
    '' AS  meta_object_uuid_binary, -- no need to return this as it is an internal implementation detail of MySQL DB''
    '' AS class_uuid_binary -- no need to return this either
FROM
    {$Connection::get_tprefix()}{$main_table} AS links
    INNER JOIN {$Connection::get_tprefix()}{$meta_table} AS meta ON meta.meta_object_id = links.link_id AND meta.meta_class_id = :meta_class_id
    LEFT JOIN {$Connection::get_tprefix()}{$classes_table} AS classes ON classes.class_name = links.link_class_name
    LEFT JOIN {$Connection::get_tprefix()}{$meta_table} AS object_meta ON object_meta.meta_object_id = links.link_object_id AND object_meta.meta_class_id = classes.class_id 
ORDER BY
    links.parent_link_id ASC,
    links.link_order ASC
        ";
        $meta_class_id = self::get_service('MysqlOrmStore')->get_class_id(NavigationLink::class);
        $b = [
            //'meta_class_name'   => NavigationLink::class,
            'meta_class_id'   => $meta_class_id,
        ];
        $data = $Connection->prepare($q)->execute($b)->fetchAll();
        foreach ($data as &$_row) {
            $_row['link_type'] = self::get_link_type_from_record($_row);
            $_row['link_type_description'] = self::get_link_type_description_from_record($_row);
            $_row['link_location'] = self::get_link_location_from_record($_row);
            $_row['link_frontend_location'] = self::get_link_frontend_location_from_record($_row);
        }
        unset($_row);

        $Function = static function (?int $parent_link_id) use ($data, &$Function) : array
        {
            $ret = [];
            foreach ($data as $record) {
                if ($record['parent_link_id'] === $parent_link_id) {
                    $ret[] = $record + ['children' => $Function($record['link_id']) ];
                }
            }
            return $ret;
        };
        return $Function($root_link_id);
    }

    /**
     * @param array $record
     * @return string
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     */
    public static function get_link_type_description_from_record(iterable $record): string
    {

        self::validate_link_record($record);

//        $ret = t::_('Holder/Structure');
//        if ($record['link_class_name'] && $record['link_class_action']) {
//            $ret = sprintf(t::_('Controller: %1$s::%2$s'), $record['link_class_name'], $record['link_class_action']);
//        } elseif ($record['link_class_name'] && $record['link_object_id']) {
//            /** @var ActiveRecordInterface $Object */
//            $Object = new $record['link_class_name']($record['link_object_id']);
//            $ret = sprintf(t::_('Object: %1$s %2$s "%3$s"'), $record['link_class_name'], $record['link_object_id'], $Object->get_object_name() );
//        } elseif ($record['link_redirect']) {
//            $ret = sprintf(t::_('Redirect: %1$s'), $record['link_redirect']);
//        }
        $link_type = self::get_link_type_from_record($record);
        switch ($link_type) {
            case NavigationLink::TYPE['HOLDER']:
                $ret = sprintf(t::_('Controller: %1$s::%2$s'), $record['link_class_name'], $record['link_class_action']);
                break;
            case NavigationLink::TYPE['CONTROLLER']:
                $ret = sprintf(t::_('Controller: %1$s::%2$s'), $record['link_class_name'], $record['link_class_action']);
                break;
            case NavigationLink::TYPE['OBJECT']:
                /** @var ActiveRecordInterface $Object */
                $Object = new $record['link_class_name']($record['link_object_id']);
                $ret = sprintf(t::_('Object: %1$s %2$s "%3$s"'), $record['link_class_name'], $record['link_object_id'], $Object->get_object_name() );
                break;
            case NavigationLink::TYPE['REDIRECT']:
                $ret = sprintf(t::_('Redirect: %1$s'), $record['link_redirect']);
                break;
            default:
                throw new LogicException(sprintf(t::_('An unexpected link type %1$s was found.'), $link_type));
        }
        return $ret;
    }

    /**
     * @param NavigationLink $Link
     * @return string
     */
    public static function get_link_type_description(NavigationLink $Link): string
    {
        $record = $Link->get_record_data();
        return self::get_link_description_from_record($record);
    }

    /**
     * @param iterable $record
     * @return string
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public static function get_link_location_from_record(iterable $record): string
    {
        $link_type = self::get_link_type_from_record($record);
        switch ($link_type) {
            case NavigationLink::TYPE['HOLDER']:
                $ret = '/';
                break;
            case NavigationLink::TYPE['CONTROLLER']:
                //do a lookup in the routing to find out the route based on the controller & method (the method is always GET)
                $ret = self::get_route_for_controller($record['link_class_name'], $record['link_class_action'], Method::HTTP_GET);
                break;
            case NavigationLink::TYPE['OBJECT']:
                /** @var ActiveRecordInterface $Object */
                $Object = new $record['link_class_name']($record['link_object_id']);
                //the read route must be obtained from the RoutingMiddleware and not from the $object::get_routes()
                //as the default route to ActiveRecordDefaultController may have been overwritten by a controller
                $ret = self::get_route_for_object($Object, Method::HTTP_GET);
                break;
            case NavigationLink::TYPE['REDIRECT']:
                $ret = $record['link_redirect'];//this is front-end route
                break;
            default:
                throw new LogicException(sprintf(t::_('An unexpected link type %1$s was found.'), $link_type));
        }
        if (!$ret) {
            throw new LogicException(sprintf(t::_('No link location could be retreived for link "%1$s".'), self::get_link_type_description_from_record($record)));
        }
        return $ret;
    }

    /**
     * @param NavigationLink $Link
     * @return string
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public static function get_link_location(NavigationLink $Link): string
    {
        $record = $Link->get_record_data();
        return self::get_link_location_from_record($record);
    }

    /**
     * @param iterable $record
     * @return string
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public static function get_link_frontend_location_from_record(iterable $record): string
    {
        $ret = self::get_link_location_from_record($record);
        $type = self::get_link_type_from_record($record);
        if (in_array($type, [ NavigationLink::TYPE['CONTROLLER'], NavigationLink::TYPE['OBJECT']] )) {
            $ret = substr($ret, strlen(GuzabaPlatform::API_ROUTE_PREFIX));//this is a front-end route
        }
        return $ret;
    }

    /**
     * Equivalent to @see self::get_link_location() but for the front-end.
     * The frontend link is without the GuzabaPlatform::API_ROUTE_PREFIX
     * @param NavigationLink $Link
     * @return string
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public static function get_link_frontend_location(NavigationLink $Link): string
    {
        $record = $Link->get_record_data();
        return self::get_link_frontend_location_from_record($record);
    }

    /**
     * @param iterable $record
     * @return string
     * @throws InvalidArgumentException
     */
    public static function get_link_type_from_record(iterable $record): string
    {
        self::validate_link_record($record);

        $ret = NavigationLink::TYPE['HOLDER'];
        if ($record['link_class_name'] && $record['link_class_action']) {
            $ret = NavigationLink::TYPE['CONTROLLER'];
        } elseif ($record['link_class_name'] && $record['link_object_id']) {
            $ret = NavigationLink::TYPE['OBJECT'];
        } elseif ($record['link_redirect']) {
            $ret = NavigationLink::TYPE['REDIRECT'];
        }
        return $ret;
    }

    /**
     * @param NavigationLink $Link
     * @return string
     * @throws InvalidArgumentException
     */
    public static function get_link_type(NavigationLink $Link): string
    {
        $record = $Link->get_record_data();
        return self::get_link_type_from_record($record);
    }

    /**
     * @param iterable $record
     * @throws InvalidArgumentException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     */
    protected static function validate_link_record(iterable $record): void
    {
        //$link_properties = NavigationLink::get_property_names();
        $link_properties = NavigationLink::get_column_names();
        if (count(array_intersect($link_properties, array_keys($record))) !== count($link_properties)) {
            throw new InvalidArgumentException(sprintf(t::_('The provided record data does not contain all %1$s properties.'), NavigationLink::class));
        }
    }

    /**
     * Returns the route by $class, $action & $method by doing a lookup in the RoutingMiddleware
     * @see RoutingMiddlewareInterface
     * @see ActiveRecordDefaultRoutingMap
     * @see ActiveRecordDefaultController
     *
     * @param string $class Must be an ControllerInterface
     * @param string $action Method in the provided class
     * @param int $method Http method constanct from Azonmedia\Http\Method
     *
     * @return string|null
     *
     * @throws InvalidArgumentException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     */
    public static function get_route_for_controller(string $class, string $action, int $method): ?string
    {
        if (!$class) {
            throw new InvalidArgumentException(sprintf(t::_('No class argument provided.')));
        }
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf(t::_('The provided class %1$s does not exist.'), $class));
        }
        if (!is_a($class, ControllerInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(t::_('The provided class %1$s is not a %2$s.'), $class, ControllerInterface::class ));
        }
        if (!$action) {
            throw new InvalidArgumentException(sprintf(t::_('No action argument provided.')));
        }
        if (!method_exists($class, $action)) {
            throw new InvalidArgumentException(sprintf(t::_('The provided class %1$s has no method %1$s.'), $class, $action));
        }
        Method::validate_method($method);

        $ret = null;
        /** @var Middlewares $Middlewares */
        $Middlewares = self::get_service('Middlewares');
        /** @var RoutingMiddlewareInterface $RoutingMiddleware */
        $RoutingMiddleware = $Middlewares->get_middleware(RoutingMiddlewareInterface::class);
        $Router = $RoutingMiddleware->get_router();
        $meta_data = $Router->get_all_meta_data();

        foreach ($meta_data as $route => $methods) {
            foreach ($methods as $route_method => $meta) {
                if (
                    isset($meta['class']) && $class === $meta['class']
                    && isset($meta['action']) && $action === $meta['action']
                    && ($method & $route_method)
                )
                {
                    $ret = $route;
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * Returns the route based on $class and $method by doing a search for route ending in /{uuid}
     * The action is not knwon (it may or may not be crud_action_read (from ActiveRecordDefaultController).
     * It may be overriden by another controller.
     * Instead the match will be done by the route which is known - from the ActiveRecord model (and is not expected to be changed).
     * It is possible a controller to define another route for viewing an object but instead and usually it should override the default route defined in the ActiveRecord model.
     * The match of the route is done by the ending /{uuid} section in the route.
     * It returns a link with the object UUID in it
     * @see RoutingMiddlewareInterface
     * @see ActiveRecordDefaultRoutingMap
     * @see ActiveRecordDefaultController
     *
     * @param string $class
     * @param int $method Http method constanct from Azonmedia\Http\Method
     *
     * @return string|null
     *
     * @throws InvalidArgumentException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     */
    public static function get_route_for_object(ActiveRecordInterface $Object, int $method): ?string
    {
        Method::validate_method($method);

        $alias = $Object->get_alias();
        if ($alias) {
            return GuzabaPlatform::API_ROUTE_PREFIX.'/'.$alias;
        }

        $class = get_class($Object);

        $ret = null;
        /** @var Middlewares $Middlewares */
        $Middlewares = self::get_service('Middlewares');
        /** @var RoutingMiddlewareInterface $RoutingMiddleware */
        $RoutingMiddleware = $Middlewares->get_middleware(RoutingMiddlewareInterface::class);
        $Router = $RoutingMiddleware->get_router();
        $meta_data = $Router->get_all_meta_data();

        foreach ($meta_data as $route => $methods) {
            foreach ($methods as $route_method => $meta) {
                if (
                    isset($meta['class']) && $class === $meta['class']
                    && preg_match('/\/{uuid}/', $route)
                    && ($method & $route_method)
                )
                {
                    $ret = $route;
                    break;
                }
            }
        }
        if ($ret) {
            $ret = str_replace('/{uuid}', '/'.$Object->get_uuid(), $ret);
        }
        return $ret;
    }
}