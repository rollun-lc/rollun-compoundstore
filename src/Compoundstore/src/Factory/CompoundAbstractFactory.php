<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\compoundstore\Factory;

use Interop\Container\ContainerInterface;
use rollun\compoundstore\Entity;
use rollun\compoundstore\Prop;
use rollun\compoundstore\SuperEntity;
use rollun\compoundstore\SysEntities;
use rollun\datastore\DataStore\DataStoreException;
use rollun\datastore\DataStore\Factory\DataStoreAbstractFactory;
use rollun\datastore\DataStore\Interfaces\DataStoresInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

/**
 * Create and return an instance of the DataStore which based on DbTable
 *
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *    'db' => [
 *        'driver' => 'Pdo_Mysql',
 *        'host' => 'localhost',
 *        'database' => '',
 *    ]
 * 'DataStore' => [
 *
 *     'DbTable' => [
 *         'class' => 'mydatabase',
 *         'tableName' => 'mytableName',
 *         'dbAdapter' => 'db' // Service Name. 'db' by default
 *     ]
 * ]
 * </code>
 *
 * @uses zend-db
 * @see https://github.com/zendframework/zend-db
 * @category   rest
 * @package    zaboy
 */
class CompoundAbstractFactory extends DataStoreAbstractFactory
{

    const DB_SERVICE_NAME = 'compound db';
    const DB_NAME_DELIMITER = '~';

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        //'SuperEtity - 'entity_table_name_1-entity_table_name_1'
        $superEntity = strpos($requestedName, SuperEntity::INNER_JOIN);
        if ($superEntity) {
            $compoundDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            foreach ($compoundDataStores as $compoundstoreDataStore) {
                //db.entity_table_name_1 -> entity_table_name_1
                $locate = explode(CompoundAbstractFactory::DB_NAME_DELIMITER, $compoundstoreDataStore);
                $compoundstoreDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
                if (strpos($compoundstoreDataStore, SysEntities::ENTITY_PREFIX) !== 0) {
                    return false;
                }
            }
            return true;
        } else {
            //db.entity_table_name_1 -> entity_table_name_1
            $locate = explode(CompoundAbstractFactory::DB_NAME_DELIMITER, $requestedName);
            $compoundstoreDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
            return strpos($compoundstoreDataStore, SysEntities::ENTITY_PREFIX) === 0 ||
                strpos($compoundstoreDataStore, SysEntities::PROP_PREFIX) === 0 ||
                $compoundstoreDataStore == SysEntities::TABLE_NAME;
        }
    }

    /**
     * Create and return an instance of the DataStore.
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  array $options
     * @return DataStoresInterface
     * @throws DataStoreException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $dbAdapterName = $this->getDbAdapterName($requestedName);
        $db = $container->has($dbAdapterName) ? $container->get($dbAdapterName) : null;
        if (null === $db) {
            throw new DataStoreException(
                'Can\'t create Zend\Db\TableGateway\TableGateway for ' . $requestedName
            );
        }

        //'SuperEtity - 'entity_table_name_1-entity_table_name_1'
        if (strpos($requestedName, SuperEntity::INNER_JOIN)) {
            $compoundDataStores = $this->getCompoundStores($requestedName);
            $compoundDataStoresObjects = [];
            foreach ($compoundDataStores as $compoundDataStore) {
                $compoundDataStoresObjects[] = $this->getCompoundStore($db, $compoundDataStore);
                $compoundDataStoresObjects[] = SuperEntity::INNER_JOIN;
            }
            array_pop($compoundDataStoresObjects);
            $tableGateway = new TableGateway(SysEntities::TABLE_NAME, $db);
            $result = new SuperEntity($tableGateway, $compoundDataStoresObjects);
            return $result;
        }
        //'sys_entities' or 'entity_table_name' or 'prop_table_name'
        $requestedName = $this->getCompoundStores($requestedName)[0];
        return $this->getCompoundStore($db, $requestedName);
    }

    /**
     * @param AdapterInterface $db
     * @param $requestedName
     * @return Entity|Prop|SysEntities
     * @throws DataStoreException
     */
    public function getCompoundStore(AdapterInterface $db, $requestedName)
    {
        //$requestedName = 'sys_entities' or 'entity_table_name' or 'prop_table_name'
        $tableGateway = new TableGateway($requestedName, $db);
        //'sys_entities' or 'entity_table_name' or 'prop_table_name'
        switch (explode('_', $requestedName)[0] . '_') {
            case SysEntities::ENTITY_PREFIX :
                return new Entity($tableGateway);
            case SysEntities::PROP_PREFIX :
                return new Prop($tableGateway);
            case explode('_', SysEntities::TABLE_NAME)[0] . '_':
                return new SysEntities($tableGateway);
            default:
                throw new DataStoreException(
                    'Can\'t create service for ' . $requestedName
                );
        }
    }

    /**
     * @param $requestedName
     * @return string
     */
    protected function getDbAdapterName($requestedName)
    {
        if (strpos($requestedName, SuperEntity::INNER_JOIN)) {
            $compoundDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            //db.entity_table_name_1 -> entity_table_name_1
            $locate = explode(CompoundAbstractFactory::DB_NAME_DELIMITER, $compoundDataStores[0]);
        } else {
            $locate = explode(CompoundAbstractFactory::DB_NAME_DELIMITER, $requestedName);
        }
        return count($locate) == 2 ? $locate[0] : static::DB_SERVICE_NAME;
    }

    /**
     * @param $requestedName
     * @return array
     */
    protected function getCompoundStores($requestedName)
    {
        if (strpos($requestedName, SuperEntity::INNER_JOIN)) {
            $compoundDataStores = explode(SuperEntity::INNER_JOIN, $requestedName);
            foreach ($compoundDataStores as &$compoundDataStore) {
                //db.entity_table_name_1 -> entity_table_name_1
                $locate = explode(CompoundAbstractFactory::DB_NAME_DELIMITER, $compoundDataStore);
                $compoundDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
            }
            return $compoundDataStores;
        } else {
            //db.entity_table_name_1 -> entity_table_name_1
            $locate = explode(CompoundAbstractFactory::DB_NAME_DELIMITER, $requestedName);
            $compoundDataStore = count($locate) == 1 ? $locate[0] : $locate[1];
            return [$compoundDataStore];
        }
    }
}
