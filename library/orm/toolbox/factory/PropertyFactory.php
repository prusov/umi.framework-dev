<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\orm\toolbox\factory;

use umi\orm\metadata\field\datetime\DateTimeField;
use umi\orm\metadata\field\ICalculableField;
use umi\orm\metadata\field\IField;
use umi\orm\metadata\field\ILocalizableField;
use umi\orm\metadata\field\special\CounterField;
use umi\orm\metadata\field\special\FileField;
use umi\orm\object\IObject;
use umi\orm\object\property\calculable\ICalculableProperty;
use umi\orm\object\property\calculable\ICounterProperty;
use umi\orm\object\property\datetime\IDateTimeProperty;
use umi\orm\object\property\file\IFileProperty;
use umi\orm\object\property\localized\ILocalizedProperty;
use umi\orm\object\property\IProperty;
use umi\orm\object\property\IPropertyFactory;
use umi\toolkit\factory\IFactory;
use umi\toolkit\factory\TFactory;

/**
 * Фабрика свойств объекта.
 */
class PropertyFactory implements IPropertyFactory, IFactory
{

    use TFactory;

    /**
     * @var string $defaultClass класс свойства по умолчанию
     */
    public $defaultPropertyClass = 'umi\orm\object\property\Property';
    /**
     * @var string $defaultCalculablePropertyClass класс свойства с вычисляемым значением
     */
    public $defaultCalculablePropertyClass = 'umi\orm\object\property\calculable\CalculableProperty';
    /**
     * @var string $defaultLocalizedPropertyClass класс локализованного свойства
     */
    public $defaultLocalizedPropertyClass = 'umi\orm\object\property\localized\LocalizedProperty';
    /**
     * @var string $defaultCounterPropertyClass класс свойства-счетчика
     */
    public $defaultCounterPropertyClass = 'umi\orm\object\property\calculable\CounterProperty';
    /**
     * @var string $defaultFilePropertyClass класс свойства со значением типа файл
     */
    public $defaultFilePropertyClass = 'umi\orm\object\property\file\FileProperty';
    /**
     * @var string $defaultDateTimePropertyClass класс свойства со значением типа DateTime
     */
    public $defaultDateTimePropertyClass = 'umi\orm\object\property\datetime\DateTimeProperty';

    /**
     * {@inheritdoc}
     */
    public function createProperty(IObject $object, IField $field, $localeId = null)
    {
        switch (true) {
            case ($field instanceof CounterField):
            {
                return $this->createCounterProperty($object, $field);
            }
            case ($field instanceof FileField):
            {
                return $this->createFileProperty($object, $field);
            }
            case ($field instanceof DateTimeField):
            {
                return $this->createDateTimeProperty($object, $field);
            }
            case ($field instanceof ICalculableField):
            {
                return $this->createCalculableProperty($object, $field);
            }
            case ($field instanceof ILocalizableField && $field->getIsLocalized()):
            {
                return $this->createLocalizedProperty($object, $field, $localeId);
            }
            default:
            {
                return $this->createCommonProperty($object, $field);
            }
        }
    }

    /**
     * Создает экземпляр обычного свойства для указанного объекта
     * @param IObject $object объект
     * @param IField $field поле типа данных
     * @return IProperty
     */
    protected function createCommonProperty(IObject $object, IField $field)
    {
        $property = $this->getPrototype(
            $this->defaultPropertyClass,
            ['umi\orm\object\property\IProperty']
        )
        ->createInstance([$object, $field]);

        return $property;
    }

    /**
     * Создает экземпляр локализованного свойства для указанного объекта
     * @param IObject $object объект
     * @param ILocalizableField $field поле типа данных
     * @param string $localeId идентификатор локали для свойства
     * @return ILocalizedProperty
     */
    protected function createLocalizedProperty(IObject $object, ILocalizableField $field, $localeId)
    {
        $property = $this->getPrototype(
            $this->defaultLocalizedPropertyClass,
            ['umi\orm\object\property\localized\ILocalizedProperty']
        )
        ->createInstance([$object, $field, $localeId]);

        return $property;
    }

    /**
     * Создает экземпляр вычисляемого свойства для указанного объекта
     * @param IObject $object объект
     * @param ICalculableField $field поле типа данных
     * @return ICalculableProperty
     */
    protected function createCalculableProperty(IObject $object, ICalculableField $field)
    {
        $property = $this->getPrototype(
            $this->defaultCalculablePropertyClass,
            ['umi\orm\object\property\calculable\ICalculableProperty']
        )
        ->createInstance([$object, $field]);

        return $property;
    }

    /**
     * Создает экземпляр обычного свойства для указанного объекта
     * @param IObject $object объект
     * @param CounterField $field поле типа данных
     * @return ICounterProperty
     */
    protected function createCounterProperty(IObject $object, CounterField $field)
    {
        $property = $this->getPrototype(
            $this->defaultCounterPropertyClass,
            ['umi\orm\object\property\calculable\ICounterProperty']
        )
        ->createInstance([$object, $field]);

        return $property;
    }

    /**
     * Создает экземпляр свойства со значением типа файл для указанного объекта
     * @param IObject $object объект
     * @param FileField $field поле типа данных
     * @return IFileProperty
     */
    protected function createFileProperty(IObject $object, FileField $field)
    {
        $property = $this->getPrototype(
            $this->defaultFilePropertyClass,
            ['umi\orm\object\property\file\IFileProperty']
        )
            ->createInstance([$object, $field]);

        return $property;
    }

    /**
     * Создает экземпляр свойства со значением типа DateTime для указанного объекта
     * @param IObject $object объект
     * @param DateTimeField $field поле типа данных
     * @return IDateTimeProperty
     */
    protected function createDateTimeProperty(IObject $object, DateTimeField $field)
    {
        $property = $this->getPrototype(
            $this->defaultDateTimePropertyClass,
            ['umi\orm\object\property\datetime\IDateTimeProperty']
        )
            ->createInstance([$object, $field]);

        return $property;
    }
}
