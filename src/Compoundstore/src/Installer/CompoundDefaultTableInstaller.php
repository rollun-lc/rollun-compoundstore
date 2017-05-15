<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.05.17
 * Time: 13:24
 */

namespace rollun\compoundstore\Installer;

use rollun\compoundstore\Entity;
use rollun\compoundstore\Example\StoreCatalog;
use rollun\compoundstore\Factory\CompoundAbstractFactory;
use rollun\compoundstore\Prop;
use rollun\compoundstore\SysEntities;
use rollun\compoundstore\TypeEntityList;
use rollun\datastore\DataStore\DbTable;
use rollun\datastore\DataStore\Installers\DbTableInstaller;
use rollun\datastore\DataStore\Installers\HttpClientInstaller;
use rollun\datastore\Middleware\DataStoreMiddlewareInstaller;
use rollun\datastore\TableGateway\DbSql\MultiInsertSql;
use rollun\datastore\TableGateway\TableManagerMysql as TableManager;
use rollun\installer\Install\InstallerAbstract;
use rollun\utils\DbInstaller;
use rollun\utils\Json\Exception;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class CompoundDefaultTableInstaller extends InstallerAbstract
{

    /**
     *
     * @var AdapterInterface
     */
    private $dbAdapter;

    /**
     *
     *
     * Add to config:
     * <code>
     *    'services' => [
     *        'aliases' => [
     *            compoundAbstractFactory::DB_SERVICE_NAME => getenv('APP_ENV') === 'prod' ? 'dbOnProduction' : 'local-db',
     *        ],
     *        'abstract_factories' => [
     *            compoundAbstractFactory::class,
     *        ]
     *    ],
     * </code>
     *
     */
    public function isInstall()
    {
        $config = $this->container->get('config');
        return (
            isset($config['services']['abstract_factories']) &&
            in_array(CompoundAbstractFactory::class, $config['services']['abstract_factories']) &&
            $this->container->has(CompoundAbstractFactory::DB_SERVICE_NAME)
        );
    }

    public function uninstall()
    {
        $this->dbAdapter = $this->container->get(CompoundAbstractFactory::DB_SERVICE_NAME);
        if (isset($this->dbAdapter)) {
            if (constant('APP_ENV') === 'dev') {
                $tableManager = new TableManager($this->dbAdapter);
                $tableManager->deleteTable(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
                $tableManager->deleteTable(StoreCatalog::PROP_PRODUCT_CATEGORY_TABLE_NAME);
                $tableManager->deleteTable(StoreCatalog::PROP_TAG_TABLE_NAME);
                $tableManager->deleteTable(StoreCatalog::MAIN_SPECIFIC_TABLE_NAME);
                $tableManager->deleteTable(StoreCatalog::MAINICON_TABLE_NAME);
                $tableManager->deleteTable(StoreCatalog::PRODUCT_TABLE_NAME);
                $tableManager->deleteTable(StoreCatalog::CATEGORY_TABLE_NAME);
                $tableManager->deleteTable(StoreCatalog::TAG_TABLE_NAME);
                $tableManager->deleteTable(SysEntities::TABLE_NAME);
                $tableManager->deleteTable(TypeEntityList::TABLE_NAME);
            } else {
                $this->consoleIO->write('constant("APP_ENV") !== "dev" It has did nothing');
            }
        }
    }

    public function install()
    {
        $this->dbAdapter = $this->container->get('db');
        if (isset($this->dbAdapter)) {
            if (constant('APP_ENV') === 'dev') {
                //develop only
                $tablesConfigDevelop = [
                    TableManager::KEY_TABLES_CONFIGS => array_merge(
                        TypeEntityList::getTableConfig(),
                        SysEntities::getTableConfig(),
                        StoreCatalog::$develop_tables_config
                    )
                ];
                $tableManager = new TableManager($this->dbAdapter, $tablesConfigDevelop);

                $tableManager->rewriteTable(TypeEntityList::TABLE_NAME);
                $tableManager->rewriteTable(SysEntities::TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PRODUCT_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::TAG_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::MAINICON_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::MAIN_SPECIFIC_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::CATEGORY_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PROP_LINKED_URL_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PROP_PRODUCT_CATEGORY_TABLE_NAME);
                $tableManager->rewriteTable(StoreCatalog::PROP_TAG_TABLE_NAME);
                $this->tableDataWrite();
            } else {
                $tablesConfigProduction = [
                    TableManager::KEY_TABLES_CONFIGS => array_merge(
                        TypeEntityList::getTableConfig(),
                        SysEntities::getTableConfig()
                    )
                ];
                $tableManager = new TableManager($this->dbAdapter, $tablesConfigProduction);

                $tableManager->rewriteTable(TypeEntityList::TABLE_NAME);
                $tableManager->createTable(SysEntities::TABLE_NAME);
            }
            return [
                'dependencies' => [
                    'aliases' => [
                        CompoundAbstractFactory::DB_SERVICE_NAME => 'db',
                    ],
                    'abstract_factories' => [
                        CompoundAbstractFactory::class,
                    ]
                ],
            ];
        }
        return [];
    }

    public function tableDataWrite()
    {
        if (isset($this->dbAdapter)) {
            $entityData = array_merge(
                StoreCatalog::$entity_product,
                StoreCatalog::$entity_category,
                StoreCatalog::$entity_tag,
                StoreCatalog::$entity_mainicon,
                StoreCatalog::$entity_main_specific
            );
            $propData = array_merge(
                StoreCatalog::$prop_tag,
                StoreCatalog::$prop_product_category,
                StoreCatalog::$prop_linked_url
            );

            $this->addData(StoreCatalog::$type_entity_list, TypeEntityList::class);
            $this->addData(StoreCatalog::$sys_entities, SysEntities::class);
            $this->addData($entityData, DbTable::class);
            $this->addData($propData, DbTable::class);
        }
    }
    protected function addData(array $data, $dbTableClass)
    {
        if(!is_a($dbTableClass, DbTable::class, true)) {
            throw new Exception("$dbTableClass not instance of " . DbTable::class);
        }
        foreach ($data as $key => $value) {
            $sql = new MultiInsertSql($this->dbAdapter, $key);
            $tableGateway = new TableGateway($key, $this->dbAdapter, null, null, $sql);
            $dataStore = new $dbTableClass($tableGateway);
            echo "create $key" . PHP_EOL;
            $dataStore->create($value, true);
        }
    }

    /**
     * Return string with description of installable functional.
     * @param string $lang ; set select language for description getted.
     * @return string
     */
    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Предоставляет реализацию compound для тестов.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function getDependencyInstallers()
    {
        return [
            CompoundInstaller::class
        ];
    }
}
