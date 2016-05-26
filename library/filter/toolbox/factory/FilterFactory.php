<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\filter\toolbox\factory;

use umi\filter\exception\OutOfBoundsException;
use umi\filter\IFilter;
use umi\filter\IFilterFactory;
use umi\filter\type\FilterBoolean;
use umi\filter\type\FilterInt;
use umi\filter\type\FilterNull;
use umi\filter\type\HtmlEntities;
use umi\filter\type\StringToLower;
use umi\filter\type\StringToUpper;
use umi\filter\type\StringTrim;
use umi\filter\type\StripNewLines;
use umi\filter\type\StripTags;
use umi\toolkit\factory\IFactory;
use umi\toolkit\factory\TFactory;
use umi\validation\type\Regexp;

/**
 * Фабрика фильтров.
 */
class FilterFactory implements IFilterFactory, IFactory
{

    use TFactory;

    /**
     * @var string $filterCollectionClass класс коллекции фильтров
     */
    public $filterCollectionClass = 'umi\filter\FilterCollection';

    /**
     * @var array $defaultOptions опции для фильтров по умолчанию
     */
    public $defaultOptions = [];

    /**
     * @var array $types поддерживаемые фильтры
     */
    public $types = array(
        self::TYPE_BOOLEAN         => FilterBoolean::class,
        self::TYPE_HTML_ENTITIES   => HtmlEntities::class,
        self::TYPE_INT             => FilterInt::class,
        self::TYPE_NULL            => FilterNull::class,
        self::TYPE_REGEXP          => Regexp::class,
        self::TYPE_STRING_TO_LOWER => StringToLower::class,
        self::TYPE_STRING_TO_UPPER => StringToUpper::class,
        self::TYPE_STRING_TRIM     => StringTrim::class,
        self::TYPE_STRIP_NEW_LINES => StripNewLines::class,
        self::TYPE_STRIP_TAGS      => StripTags::class,

    );

    /**
     * {@inheritdoc}
     */
    public function createFilterCollection(array $config)
    {
        $filters = [];
        foreach ($config as $type => $options) {
            $filters[$type] = $this->createFilter($type, $options);
        }

        return $this->getPrototype(
                $this->filterCollectionClass,
                ['umi\filter\IFilterCollection']
            )
            ->createInstance([$filters]);
    }

    /**
     * {@inheritdoc}
     */
    public function createFilter($type, array $options = [])
    {
        if (!isset($this->types[$type])) {
            throw new OutOfBoundsException($this->translate(
                'Filter "{type}" is not available.',
                ['type' => $type]
            ));
        }

        $options = $this->configToArray($options, true);

        if (isset($this->defaultOptions[$type])) {
            $defaultOptions = $this->configToArray($this->defaultOptions[$type], true);
            $options = $this->mergeConfigOptions($options, $defaultOptions);
        }

        /** @var IFilter $filter */
        $filter = $this->getPrototype(
            $this->types[$type],
            ['umi\filter\IFilter']
        )
            ->createInstance([$options]);

        return $filter;
    }
}
