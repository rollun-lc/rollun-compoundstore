<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 13.05.17
 * Time: 11:57 AM
 */

namespace rollun\compoundstore;


use rollun\datastore\DataStore\DbTable;
use rollun\datastore\TableGateway\TableManagerMysql as TableManager;

class TypeEntityList extends DbTable
{
    const TABLE_NAME = 'type_entity_list';
    const ID_FIELD = 'entity_type';

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::ID_FIELD;
    }

    public static function getTableConfig()
    {
        return [
            static::TABLE_NAME => [
                static::ID_FIELD => [
                    TableManager::FIELD_TYPE => 'Varchar',
                    TableManager::PRIMARY_KEY => true,
                    TableManager::FIELD_PARAMS => [
                        'length' => 255,
                        'nullable' => false,
                    ],
                ],
            ]
        ];
    }
}