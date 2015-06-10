<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace umi\i18n;

use umi\i18n\exception\RequiredDependencyException;
use umi\i18n\translator\ITranslator;

/**
 * Трейт для поддержки локализации.
 */
trait TLocalizable
{
    /**
     * @var ITranslator $traitTranslator транслятор
     */
    private $traitTranslator;

    /**
     * @see ILocalizable::setTranslator()
     */
    public function setTranslator(ITranslator $translator)
    {
        $this->traitTranslator = $translator;
    }

    /**
     * Возвращает сообщение, переведенное для текущей или указанной локали.
     * Текст сообщения может содержать плейсхолдеры. Ex: File "{path}" not found
     * Если идентификатор локали не указан, будет использована текущая локаль.
     * @param string $message текст сообщения на языке разработки
     * @param array $placeholders значения плейсхолдеров для сообщения. Ex: array('{path}' => '/path/to/file')
     * @param string $localeId идентификатор локали в которую осуществляется перевод (ru, en_us)
     * @return string
     */
    public function translate($message, array $placeholders = [], $localeId = null)
    {
        if (!$message) {
            return $message;
        }

        $dictionaries = $this->getI18nDictionaryNames();
        if ($this->traitTranslator) {
            return $this->traitTranslator->translate($dictionaries, $message, $placeholders, $localeId);
        }

        $replace = [];
        foreach ($placeholders as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        return strtr($message, $replace);
    }

    /**
     * Возвращает транслятор.
     * @return ITranslator
     */
    protected function getTranslator()
    {
        if (!$this->traitTranslator) {
            throw new RequiredDependencyException(sprintf(
                'Translator is not injected in class "%s".',
                get_class($this)
            ));
        }

        return $this->traitTranslator;
    }

    /**
     * Возвращает список имен словарей в которых будет производиться поиск перевода сообщений и лейблов
     * данного компонента. Приоритет поиска соответсвует последовательности словарей в списке.
     * @return array
     */
    protected function getI18nDictionaryNames()
    {
        $classParts = explode('\\', __CLASS__);

        $dictionaries = [];
        for ($i = count($classParts); $i > 0; $i--) {
            $dictionaries[] = implode('.', array_slice($classParts, 0, $i));
        }

        return $dictionaries;
    }

}
