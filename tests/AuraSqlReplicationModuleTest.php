<?php

namespace Ray\AuraSqlModule;

use Aura\Sql\ConnectionLocator;
use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use Ray\Di\Injector;

class AuraSqlReplicationModuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExtendedPdo
     */
    private $slavePdo;

    /**
     * @var ExtendedPdo
     */
    private $masterPdo;

    /**
     * @var ConnectionLocator
     */
    private $locator;

    public function connectionProvider()
    {
        $locator = new ConnectionLocator;
        $slave = new Connection('sqlite::memory:');
        $slavePdo = $slave();
        $locator->setRead('slave', $slave);
        $master = new Connection('sqlite::memory:');
        $masterPdo = $master();
        $locator->setWrite('master', $master);

        return [[$locator, $masterPdo, $slavePdo]];
    }

    /**
     * @dataProvider connectionProvider
     */
    public function testLocatorSlave(ConnectionLocator $locator, ExtendedPdo $masterPdo, ExtendedPdo $slavePdo)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        /* @var  $model FakeRepModel */
        $model = (new Injector(new AuraSqlReplicationModule($locator), $_ENV['TMP_DIR']))->getInstance(FakeRepModel::class);
        $this->assertInstanceOf(ExtendedPdo::class, $model->pdo);
        $this->assertSame($slavePdo, $model->pdo);
    }

    /**
     * @dataProvider connectionProvider
     */
    public function testLocatorMaster(ConnectionLocator $locator, ExtendedPdo $masterPdo, ExtendedPdo $slavePdo)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        /* @var  $model FakeRepModel */
        $model = (new Injector(new AuraSqlReplicationModule($locator), $_ENV['TMP_DIR']))->getInstance(FakeRepModel::class);
        $this->assertInstanceOf(ExtendedPdo::class, $model->pdo);
        $this->assertSame($masterPdo, $model->pdo);
    }

    /**
     * @dataProvider connectionProvider
     */
    public function testLocatorMasterWithQualifer(ConnectionLocator $locator, ExtendedPdo $masterPdo, ExtendedPdo $slavePdo)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        /* @var  $db1Master ExtendedPdo */
        /* @var  $db2Master ExtendedPdo */
        list(list($locator2)) = $this->connectionProvider();
        $db1Master = (new Injector(new AuraSqlReplicationModule($locator, 'db1'), $_ENV['TMP_DIR']))->getInstance(ExtendedPdoInterface::class, 'db1');
        $db2Master = (new Injector(new AuraSqlReplicationModule($locator2, 'db2'), $_ENV['TMP_DIR']))->getInstance(ExtendedPdoInterface::class, 'db2');
        $this->assertInstanceOf(ExtendedPdo::class, $db1Master);
        $this->assertInstanceOf(ExtendedPdo::class, $db2Master);
        $this->assertNotSame($db1Master, $db2Master);
    }
}
