<?php

declare(strict_types=1);

namespace GuzabaPlatform\Navigation\Models;

use Guzaba2\Authorization\Role;
use Guzaba2\Base\Exceptions\InvalidArgumentException;
use Guzaba2\Base\Exceptions\RunTimeException;
use Guzaba2\Orm\ActiveRecordCollection;
use Guzaba2\Orm\Store\Sql\Mysql;
use GuzabaPlatform\Platform\Application\BaseActiveRecord;
use GuzabaPlatform\Platform\Application\MysqlConnectionCoroutine;
use Guzaba2\Translator\Translator as t;

/**
 * Class NavigationLink
 * @package GuzabaPlatform\Navigation\Models
 * @property int link_id
 * @property null|int parent_link_id
 * @property mull|string link_class_name
 * @property string link_class_action
 * @property null|int link_object_id
 * @property string link_name
 * @property int link_order
 * @property null|string link_redirect
 * @property null|int role_id
 */
class NavigationLink extends BaseActiveRecord
{
    protected const CONFIG_DEFAULTS = [
        'main_table'            => 'navigation_links',
        //there is a separate controller for creating links
        'route'                 => '/admin/navigation/link',//to be used for editing and deleting - there is a separate route in the controller for creating (overwrites this route for the POST method)
    ];

    protected const CONFIG_RUNTIME = [];

    public const TYPE = [
        'HOLDER'        => 'holder',
        'REDIRECT'      => 'redirect',
        'CONTROLLER'    => 'controller',
        'OBJECT'        => 'object',
    ];

    /**
     * Where the link points to in the API
     * @var string
     */
    public string $link_location = '';

    /**
     * The same as $link_location but for the front end.
     * The front end links different from the backend API link by the removed GuzabaPlatform::API_ROUTE_PREFIX
     * For the links of the REDIRECT this is the same like $link_location
     * It is also the same for type HOLDER but this type is not meant to be shown in the frontend
     * @var string
     */
    public string $link_frontend_location = '';

    /**
     * @see self::TYPE
     * @var string
     */
    public string $link_type = '';

    /**
     * A human readable description of link type and location
     * @var string
     */
    public string $link_type_description = '';


    protected function _before_delete() : void
    {
        foreach ($this->get_children() as $Link) {
            $Link->delete();
        }
    }

    protected function _before_write() : void
    {
        //the newly added link must always be last
        if ($this->is_new()) {
            $siblings = $this->get_siblings();
            if (count($siblings)) {
                $this->link_order = $siblings[ count($siblings) - 1]->link_order + 1;
            } else {
                $this->link_order = 1;
            }

        }
    }

    protected function _before_get_link_location(): void
    {
        //if (!$this->link_location) {
        if (!$this->record_data['link_location']) { //this is recursion - goes through the overloading as the properties are unset in the constructor
            $this->record_data['link_location'] = $this->get_location();//do not use the __set overloading as it is disabled - it is not allowed to set link_location
        }
    }

    protected function _before_set_link_location(string $link_location): string
    {
        throw new RunTimeException(sprintf(t::_('The link_location property is read only.')));
    }

    protected function _before_get_link_frontend_location(): void
    {
        if (!$this->record_data['link_frontend_location']) {
            $this->record_data['link_frontend_location'] = $this->get_frontend_location();
        }
    }

    protected function _before_set_link_frontend_location(string $link_frontend_location): string
    {
        throw new RunTimeException(sprintf(t::_('The link_frontend_location property is read only.')));
    }

    protected function _before_get_link_type(): void
    {
        if (!$this->record_data['link_type']) {
            $this->record_data['link_type'] = $this->get_type();
        }
    }

    protected function _before_set_link_type(string $link_type): string
    {
        throw new RunTimeException(sprintf(t::_('The link_type property is read only.')));
    }

    protected function _before_get_link_type_description(): void
    {
        if (!$this->record_data['link_type_description']) {
            $this->record_data['link_type_description'] = $this->get_type_description();
        }
    }

    protected function _before_set_link_type_description(string $link_type_description): string
    {
        throw new RunTimeException(sprintf(t::_('The link_type_description property is read only.')));
    }

    /**
     * @param string $link_name
     * @param string|null $parent_link_uuid
     * @param string|null $link_class_name
     * @param string|null $link_class_action
     * @param string|null $link_object_uuid
     * @param string|null $link_redirect
     * @param string|null $role_uuid To be provided when the link should be the home link for a given role
     * @return NavigationLink
     * @throws InvalidArgumentException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    public static function create(string $link_name, ?string $parent_link_uuid = NULL, ?string $link_class_name = NULL, ?string $link_class_action = NULL, ?string $link_object_uuid = NULL, ?string $link_redirect = NULL, ?string $role_uuid = NULL) : self
    {
        $parent_link_id = NULL;
        if ($parent_link_uuid) {
            $ParentLink = new static($parent_link_uuid);
            $parent_link_id = $ParentLink->get_id();
        }
        $link_object_id = NULL;
        if (!$link_name) {
            throw new InvalidArgumentException(sprintf(t::_('No link_name argument provided.')));
        }
        if ($link_class_name && !class_exists($link_class_name)) {
            throw new InvalidArgumentException(sprintf(t::_('The provided link_class_name %1$s does not exist.'), $link_class_name));
        }
        if ($link_class_name && $link_object_uuid) {

            $Object = new $link_class_name($link_object_uuid);
            $link_object_id = $Object->get_id();
        }
        if ($link_class_name && !$link_class_action && !$link_object_id) {
            throw new InvalidArgumentException(sprintf(t::_('There is link_class_name provided but no link_class_action and no link_object_id.')));
        }
        if ($link_class_name && $link_class_action) {
            //just validation
            //check method exists...
            if (!method_exists($link_class_name, $link_class_action)) {
                throw new InvalidArgumentException(sprintf(t::_('The provided link_class_action %1$s is not a method on class %2$s.'), $link_class_action, $link_class_name));
            }
        }
        if ($role_uuid && $parent_link_uuid) {
            throw new InvalidArgumentException(sprintf(t::_('Both link_parent_uuid and role_uuid are provided. Home links for roles can only be links without parent link.')));
        }
        $role_id = null;
        if ($role_uuid) {
            $Role = new Role($role_uuid);//leave the exception to bubble
            $role_id = $Role->get_id();
        }

        $Link = new static();
        $Link->link_name = $link_name;
        $Link->parent_link_id = $parent_link_id;
        $Link->link_class_name = $link_class_name;
        $Link->link_class_action = $link_class_action;
        $Link->link_object_id = $link_object_id;
        $Link->link_redirect = $link_redirect;
        $Link->role_id = $role_id;
        $Link->write();
        return $Link;
    }

    /**
     * Returns the children of this link (one level deep, no recursively)
     * @return array
     */
    public function get_children() : ActiveRecordCollection
    {
        return self::data_to_collection(self::get_data_by(['parent_link_id' => $this->get_id()]));
    }

    /**
     * @return NavigationLink|null
     * @throws InvalidArgumentException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    public function get_parent() : ?self
    {
        return $this->parent_link_id ? new static($this->parent_link_id) : NULL ;
    }

    /**
     * @return ActiveRecordCollection
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public function get_siblings() : ActiveRecordCollection
    {
        $ret = [];
        $siblings = self::get_data_by(['parent_link_id' => $this->parent_link_id],  0,  0, FALSE, 'link_order');


        foreach ($siblings as $record) {
            if ($record['link_id'] !== $this->get_id()) {
                $ret[] = $record;
            }
        }
        $ret = self::data_to_collection($ret);
        return $ret;
    }

    /**
     * @see self::link_type_description
     * @return string
     */
    public function get_type_description(): string
    {
        return Navigation::get_link_type_description($this);
    }

    /**
     * @see self::link_type
     * @return string
     * @throws InvalidArgumentException
     */
    public function get_type(): string
    {
        return Navigation::get_link_type($this);
    }

    /**
     * @see self::link_location
     * @return string
     * @throws InvalidArgumentException
     * @throws RunTimeException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public function get_location(): string
    {
        return Navigation::get_link_location($this);
    }

    /**
     * @see self::link_frontend_location
     * @return string
     * @throws InvalidArgumentException
     * @throws RunTimeException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public function get_frontend_location(): string
    {
        return Navigation::get_link_frontend_location($this);
    }

}