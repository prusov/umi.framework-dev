<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\hmvc\component;

use umi\acl\IAclManager;
use umi\hmvc\controller\IController;
use umi\hmvc\dispatcher\IDispatchContext;
use umi\hmvc\exception\OutOfBoundsException;
use umi\hmvc\exception\RuntimeException;
use umi\hmvc\widget\IWidget;
use umi\hmvc\view\IViewRenderer;
use umi\http\Request;
use umi\http\Response;
use umi\route\IRouter;

/**
 * Интерфейс MVC компонента системы.
 */
interface IComponent
{
    /**
     * Разделитель пути компонента
     */
    const PATH_SEPARATOR = '.';

    /**
     * Опция для конфигурирования класса компонента
     */
    const OPTION_CLASS = 'componentClass';
    /**
     * Опция для конфигурирования маршрутизатора
     */
    const OPTION_ROUTES = 'routes';
    /**
     * Опция для конфигурирования моделей
     */
    const OPTION_MODELS = 'models';
    /**
     * Опция для конфигурирования отображения
     */
    const OPTION_VIEW = 'view';
    /**
     * Опция для конфигурирования контроллеров
     */
    const OPTION_CONTROLLERS = 'controllers';
    /**
     * Опция для конфигурирования виджетов
     */
    const OPTION_WIDGET = 'widget';
    /**
     * Опция для конфигурирования дочерних компонентов
     */
    const OPTION_COMPONENTS = 'components';
    /**
     * Опция для конфигурирования ACL
     */
    const OPTION_ACL = 'acl';

    /**
     * Имя параметра маршрута, для передачи управления дочернему компоненту
     */
    const MATCH_COMPONENT = 'component';
    /**
     * Имя параметра маршрута, для передачи управления контроллеру
     */
    const MATCH_CONTROLLER = 'controller';

    /**
     * Имя контроллера для обработки исключений
     */
    const ERROR_CONTROLLER = 'error';
    /**
     * Контроллер для отображения сетки компонента
     */
    const LAYOUT_CONTROLLER = 'layout';

    /**
     * Имя виджета для отображения ошибок работы виджетов
     */
    const ERROR_WIDGET = 'error';

    /**
     * Возвращает иерархический путь компонента.
     * @return string
     */
    public function getPath();

    /**
     * Проверяет, существует ли дочерний компонент с заданным именем.
     * @param string $name имя компонента
     * @return bool
     */
    public function hasChildComponent($name);

    /**
     * Возвращает дочерний MVC компонент.
     * @param string $name имя компонента
     * @return IComponent
     */
    public function getChildComponent($name);

    /**
     * Возвращает маршрутизатор компонента.
     * @return IRouter
     */
    public function getRouter();

    /**
     * Проверяет, существует ли контроллер в компоненте.
     * @param string $controllerName имя контроллера
     * @return bool
     */
    public function hasController($controllerName);

    /**
     * Возвращает контроллер компонента.
     * @param string $controllerName имя контроллера
     * @param array $args аргументы для создания контроллера
     * @throws OutOfBoundsException если контроллер не существует
     * @return IController
     */
    public function getController($controllerName, array $args = []);

    /**
     * Проверяет, существует ли виджет в компоненте.
     * @param string $widgetName имя виджета
     * @return bool
     */
    public function hasWidget($widgetName);

    /**
     * Возвращает виджет компонента.
     * @param string $widgetName имя виджета
     * @param array $params параметры вызова виджета
     * @throws OutOfBoundsException если виджет не существует
     * @throws RuntimeException если виджет не callable
     * @return IWidget
     */
    public function getWidget($widgetName, array $params = []);

    /**
     * Возвращает рендерер шаблонов компонента.
     * @return IViewRenderer
     */
    public function getViewRenderer();

    /**
     * Возвращает ACL-менеджер компонента.
     * @return IAclManager
     */
    public function getAclManager();

    /**
     * Может выполнять дополнительные действия при обработке HTTP-запроса.
     * Если возвращает Response, диспетчирезиция запроса останавливается, запускается диспетчеризация ответа.
     * @param IDispatchContext $context
     * @param Request $request
     */
    public function onDispatchRequest(IDispatchContext $context, Request $request);

    /**
     * Может выполнять дополнительные действия при обработке HTTP-ответа.
     * @param IDispatchContext $context
     * @param Response $response
     */
    public function onDispatchResponse(IDispatchContext $context, Response $response);

}