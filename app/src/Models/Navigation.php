<?php


namespace GuzabaPlatform\Navigation\Models;


use Guzaba2\Base\Base;
use Guzaba2\Orm\Store\Sql\Mysql;
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
     * @return array
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     */
    public static function get_all_links() : array
    {

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
        return $Function(NULL);
    }
}