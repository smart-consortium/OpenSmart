<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\MetaDataTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\MetaDataTable Test Case
 */
class MetaDataTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\MetaDataTable
     */
    public $MetaData;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.meta_data',
        'app.videos'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('MetaData') ? [] : ['className' => MetaDataTable::class];
        $this->MetaData = TableRegistry::get('MetaData', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->MetaData);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
