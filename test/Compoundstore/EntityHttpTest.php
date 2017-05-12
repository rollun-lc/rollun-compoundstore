<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 26.10.16
 * Time: 2:21 PM
 */

namespace rollun\test\compoundstore;

class EntityHttpTest extends EntityTestAbstract
{

    protected function __init()
    {
        $this->object = $this->container->get('testEavOverHttpClient');
    }
}