<?php
/**
 * UMI.Framework (http://umi-framework.ru/)
 *
 * @link      http://github.com/Umisoft/framework for the canonical source repository
 * @copyright Copyright (c) 2007-2013 Umisoft ltd. (http://umisoft.ru/)
 * @license   http://umi-framework.ru/license/bsd-3 BSD-3 License
 */

namespace utest\filter\unit\type;

use umi\filter\IFilter;
use umi\filter\type\FilterInt;
use utest\filter\FilterTestCase;

/**
 * Класс тестирование фильтра Int
 */
class IntFilterTests extends FilterTestCase
{
    /**
     * @var IFilter $filter
     */
    private $filter = null;

    public function setUpFixtures()
    {
        $this->filter = new FilterInt();
    }

    public function testFilterBaseUsage()
    {
        $this->assertEquals(123, $this->filter->filter("123"), "Ожидается, что отфильтрованное значение будет числом");
        $this->assertEquals(
            123,
            $this->filter->filter("123 my int"),
            "Ожидается, что отфильтрованное значение будет числом"
        );
        $this->assertEmpty(
            $this->filter->filter("my int: 123"),
            "Ожидается, что отфильтрованное значение будет пустым"
        );
    }

    public function testFilterAdvancedUsage()
    {
        $filter = new FilterInt(['base' => 16]);
        $this->assertEquals(
            255,
            $filter->filter("FF"),
            "Ожидается, что отфильтрованное значение будет десятичным числом"
        );
    }
}