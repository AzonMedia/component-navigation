<?php
declare(strict_types=1);

namespace GuzabaPlatform\Navigation;

use GuzabaPlatform\Components\Base\BaseComponent;
use GuzabaPlatform\Components\Base\Interfaces\ComponentInitializationInterface;
use GuzabaPlatform\Components\Base\Interfaces\ComponentInterface;

/**
 * Class Component
 * @package GuzabaPlatform\Navigation
 */
class Component extends BaseComponent implements ComponentInterface, ComponentInitializationInterface
{

    protected const CONFIG_DEFAULTS = [
        'services'      => [
            'FrontendRouter',
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    protected const COMPONENT_NAME = "Navigation";
    //https://components.platform.guzaba.org/component/{vendor}/{component}
    protected const COMPONENT_URL = 'https://components.platform.guzaba.org/component/guzaba-platform/navigation';
    //protected const DEV_COMPONENT_URL//this should come from composer.json
    protected const COMPONENT_NAMESPACE = __NAMESPACE__;
    protected const COMPONENT_VERSION = '0.0.1';//TODO update this to come from the Composer.json file of the component
    protected const VENDOR_NAME = 'Azonmedia';
    protected const VENDOR_URL = 'https://azonmedia.com';
    protected const ERROR_REFERENCE_URL = 'https://github.com/AzonMedia/component-navigation/tree/master/docs/ErrorReference/';

    /**
     * @return array
     */
    public static function run_all_initializations() : array
    {
        self::register_routes();
        return ['register_routes'];
    }


    /**
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     */
    public static function register_routes() : void
    {
        $FrontendRouter = self::get_service('FrontendRouter');
        $additional = [
            'name'  => 'Navigation',
            'meta' => [
                'in_navigation' => TRUE, //to be shown in the admin navigation
                'additional_template' => '@GuzabaPlatform.Navigation/NavigationNavigationHook.vue',//here the list of classes will be expanded
            ],
        ];
        $FrontendRouter->{'/admin'}->add('navigation', '@GuzabaPlatform.Navigation/NavigationAdmin.vue' ,$additional);
    }

}