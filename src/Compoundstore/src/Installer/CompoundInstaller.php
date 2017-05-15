<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
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

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class CompoundInstaller extends InstallerAbstract
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

    }

    public function install()
    {
        $this->dbAdapter = $this->container->get('db');
        if (isset($this->dbAdapter)) {
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

    /**
     * Return string with description of installable functional.
     * @param string $lang ; set select language for description getted.
     * @return string
     */
    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Предоставляет compound ранилище.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function getDependencyInstallers()
    {
        return [
            DbInstaller::class,
            DataStoreMiddlewareInstaller::class,
            DbTableInstaller::class,
            HttpClientInstaller::class,
        ];
    }
}
