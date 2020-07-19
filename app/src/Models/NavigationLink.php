<?php
declare(strict_types=1);


namespace GuzabaPlatform\Navigation\Models;

use Guzaba2\Base\Exceptions\InvalidArgumentException;
use Guzaba2\Orm\ActiveRecordCollection;
use Guzaba2\Orm\Store\Sql\Mysql;
use GuzabaPlatform\Platform\Application\BaseActiveRecord;
use GuzabaPlatform\Platform\Application\MysqlConnectionCoroutine;
use Guzaba2\Translator\Translator as t;

/**
 * Class NavigationLink
 * @package GuzabaPlatform\Navigation\Models
 * @property int link_id
 * @property ?int parent_link_id
 * @property ?string link_class_name
 * @property string link_class_action
 * @property ?int link_object_id
 * @property string link_name
 * @property ?string link_redirect
 * @property int link_order
 */
class NavigationLink extends BaseActiveRecord
{
    protected const CONFIG_DEFAULTS = [
        'main_table'            => 'navigation_links',
        'route'                 => '/admin/navigation/link',//to be used for editing and deleting
    ];

    protected const CONFIG_RUNTIME = [];


    public static function create(string $link_name, ?string $parent_link_uuid = NULL, ?string $link_class_name = NULL, ?string $link_class_action = NULL, ?string $link_object_uuid = NULL, ?string $link_redirect = NULL) : self
    {
        $parent_link_id = NULL;
        if ($parent_link_uuid) {
            $ParentLink = new static($parent_link_uuid);
            $parent_link_id = $ParentLink->get_id();
        }
        $link_object_id = NULL;
        if ($link_class_name && $link_object_uuid) {
            if (!class_exists($link_class_name)) {
                //throw
            }
            $Object = new $link_class_name($link_object_uuid);
            $link_object_id = $Object->get_id();
        }
        if ($link_class_name && $link_class_action) {
            //just validation
            if (!class_exists($link_class_name)) {
                //throw
            }
            //check method exists...
        }
        if ($link_class_name && !$link_class_action && !$link_object_id) {
            //throw
        }
        if (!$link_name) {
            throw new InvalidArgumentException(sprintf(t::_('No $link_name argument provided.')));
        }
        $Link = new static();
        $Link->link_name = $link_name;
        $Link->parent_link_id = $parent_link_id;
        $Link->link_class_name = $link_class_name;
        $Link->link_class_action = $link_class_action;
        $Link->link_object_id = $link_object_id;
        $Link->link_redirect = $link_redirect;
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

    public function get_parent() : ?self
    {
        return $this->parent_link_id ? new static($this->parent_link_id) : NULL ;
    }

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
            if ($siblings) {
                $this->link_order = $siblings[ count($siblings) - 1]->link_order + 1;
            } else {
                $this->link_order = 1;
            }

        }
    }
}