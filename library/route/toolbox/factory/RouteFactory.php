<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\route\toolbox\factory;

use umi\route\exception\InvalidArgumentException;
use umi\route\exception\OutOfBoundsException;
use umi\route\IRouteFactory;
use umi\route\type\IRoute;
use umi\toolkit\factory\IFactory;
use umi\toolkit\factory\TFactory;

/**
 * Фабрика правил для маршрутизатора.
 */
class RouteFactory implements IRouteFactory, IFactory
{
    use TFactory;

    /**
     * @var array $routeTypes типы правил маршрутизатора
     */
    public $types = [
        self::ROUTE_FIXED    => 'umi\route\type\FixedRoute',
        self::ROUTE_REGEXP   => 'umi\route\type\RegexpRoute',
        self::ROUTE_SIMPLE   => 'umi\route\type\SimpleRoute',
        self::ROUTE_EXTENDED => 'umi\route\type\ExtendedRoute'
    ];

    /**
     * @var string $routerClass класс маршрутизатора
     */
    public $routerClass = 'umi\route\Router';

    /**
     * {@inheritdoc}
     */
    public function createRouter(array $config)
    {
        $routes = $this->createRoutes($config);

        return $this->getPrototype(
                $this->routerClass,
                ['umi\route\IRouter']
            )
            ->createInstance([$routes]);
    }

    /**
     * Возвращает правило маршрутизации на основе массива конфигурации.
     * @param array $config конфигурация
     * @throws InvalidArgumentException если тип маршрута не передан
     * @throws OutOfBoundsException если заданный тип маршрута не существует
     * @return IRoute правило маршрутизатора
     */
    protected function createRoute(array $config)
    {
        if (!isset($config[self::OPTION_TYPE])) {
            throw new InvalidArgumentException($this->translate(
                'Route type is not specified.'
            ));
        }

        $type = $config[self::OPTION_TYPE];
        if (!isset($this->types[$type])) {
            throw new OutOfBoundsException($this->translate(
                'Route type "{type}" is not available.',
                ['type' => $type]
            ));
        }

        $subroutes = [];

        if (isset($config[self::OPTION_SUBROUTES])) {
            $subroutes = $this->createRoutes($config[self::OPTION_SUBROUTES]);
        }

        unset($config[self::OPTION_TYPE]);
        unset($config[self::OPTION_SUBROUTES]);

        return $this->getPrototype(
                $this->types[$type],
                ['umi\route\type\IRoute']
            )
            ->createInstance([$config, $subroutes]);
    }

    /**
     * Возвращает правила маршрутизации на основе массива конфигурации.
     * @param array $config конфигурация
     * @return IRoute[]
     */
    protected function createRoutes(array $config)
    {
        $routes = [];

        $defaultPriority = 1;
        foreach ($config as $name => $routeConfig) {

            $route = $this->createRoute($routeConfig);
            $priority = isset($routeConfig[IRoute::OPTION_PRIORITY]) ? $routeConfig[IRoute::OPTION_PRIORITY] : $defaultPriority;
            $route->setPriority($priority);
            $routes[$name] = $route;
            $defaultPriority++;
        }

        return $routes;
    }
}