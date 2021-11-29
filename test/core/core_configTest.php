<?php
/**
 * FuzeWorks Framework Core.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2019 TechFuze
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author    TechFuze
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 0.0.1
 *
 * @version Version 1.3.0
 */

use FuzeWorks\Config;
use FuzeWorks\Event\ConfigGetEvent;
use FuzeWorks\Exception\ConfigException;
use FuzeWorks\Priority;
use FuzeWorks\Events;

/**
 * Class ConfigTest.
 *
 * Config testing suite, will test basic config functionality while also testing default ORM's
 * @coversDefaultClass \FuzeWorks\Config
 */
class configTest extends CoreTestAbstract
{

    /**
     * @var Config
     */
	protected Config $config;

	public function setUp(): void
	{
		$this->config = new Config();
	}

    /**
     * @coversNothing
     */
	public function testGetConfigClass()
	{
		$this->assertInstanceOf('FuzeWorks\Config', $this->config);
	}

	/**
	 * @depends testGetConfigClass
     * @covers ::getConfig
     * @covers ::loadConfigFile
	 */
	public function testLoadConfig()
	{
		$this->assertInstanceOf('FuzeWorks\ConfigORM\ConfigORM', $this->config->getConfig('error'));
	}

    /**
     * @depends testLoadConfig
     * @covers ::getConfig
     * @covers ::loadConfigFile
     */
	public function testLoadConfigWithAltDirectory()
    {
        $config = $this->config->getConfig('TestLoadConfigWithAltDirectory', ['test'.DS.'config'.DS.'TestLoadConfigWithAltDirectory'.DS.'SubDirectory']);
        $this->assertEquals('value', $config->key);
    }

	/**
	 * @depends testLoadConfig
     * @covers ::loadConfigFile
	 */
	public function testFileNotFound()
	{
	    $this->expectException(ConfigException::class);
		$this->config->getConfig('notFound');
	}

    /**
     * @depends testLoadConfig
     * @covers ::loadConfigFile
     */
	public function testLoadConfigCancel()
    {
        // Register listener
        Events::addListener(function($event){
            $event->setCancelled(true);
        }, 'configGetEvent', Priority::NORMAL);

        // Attempt and load a config file
        $config = $this->config->getConfig('loadConfigCancel');
        $this->assertInstanceOf('FuzeWorks\ConfigORM\ConfigORM', $config);
        $this->assertEmpty($config->toArray());
    }

    /**
     * @depends testLoadConfig
     * @covers ::loadConfigFile
     */
    public function testLoadConfigIntercept()
    {
        // Register listener
        Events::addListener(function($event){
            /** @var ConfigGetEvent $event */
            $event->configName = 'TestLoadConfigIntercept';
        }, 'configGetEvent', Priority::NORMAL);

        // Load file
        $config = $this->config->getConfig('does_not_exist', ['test'.DS.'config'.DS.'TestLoadConfigIntercept']);
        $this->assertEquals('exists', $config->it);
    }

    /**
     * @depends testLoadConfig
     * @covers ::overrideConfig
     * @covers ::loadConfigFile
     */
    public function testLoadConfigOverride()
    {
        // Load file without override
        $this->assertEquals(['initial' => 'value'], $this->config->getConfig('testLoadConfigOverride', ['test'.DS.'config'.DS.'TestLoadConfigOverride'])->toArray());

        // Discard to reset test
        $this->config->discardConfigFiles();

        // Create override
        Config::overrideConfig('testLoadConfigOverride', 'initial', 'different');

        $this->assertEquals(['initial' => 'different'], $this->config->getConfig('testLoadConfigOverride', ['test'.DS.'config'.DS.'TestLoadConfigOverride'])->toArray());
    }

    /**
     * @depends testLoadConfigOverride
     * @covers ::overrideConfig
     * @covers ::loadConfigFile
     */
    public function testLoadConfigCoreOverride()
    {
        // First see that it does not exist
        $this->assertFalse(isset($this->config->getConfig('error')->toArray()['someKey']));

        // Then discard to reset the test
        $this->config->discardConfigFiles();

        // Create the override
        Config::overrideConfig('error', 'someKey', 'someValue');

        // And test that it exists now
        $this->assertTrue(isset($this->config->getConfig('error')->toArray()['someKey']));
        $this->assertEquals('someValue', $this->config->getConfig('error')->toArray()['someKey']);
    }

    /**
     * @covers ::getConfig
     */
    public function testSameConfigObject()
    {
        $config = $this->config->getConfig('testsameconfigobject', array('test'.DS.'config'.DS.'TestSameConfigObject'));
        $config2 = $this->config->getConfig('testsameconfigobject', array('test'.DS.'config'.DS.'TestSameConfigObject'));

        // First test if the objects are the same instance
        $this->assertSame($config, $config2);

        // First test the existing key
        $this->assertEquals('value', $config->key);

        // Change it and test if it's different now
        $config->key = 'other_value';
        $this->assertEquals('other_value', $config2->key);
    }

    /**
     * @coversNothing
     */
    public function testConfigWithEnvironmentVariables()
    {
        // First push the test variable
        putenv('TESTKEY=Superb');

        // Load the config
        $config = $this->config->getConfig('testconfigwithenvironment', ['test'.DS.'config'.DS.'TestConfigWithEnvironment']);

        // Check values
        $this->assertEquals('Superb', $config->get('testKey'));
        $this->assertEquals('somethingDefault', $config->get('otherKey'));
    }

    /**
     * @covers ::loadConfigFile
     * @depends testLoadConfigCoreOverride
     */
    public function testCumulativeConfigFile()
    {
        // Add folders
        $this->config->addComponentPath('test'.DS.'config'.DS.'TestCumulativeConfigFile'.DS.'HighPriorityFolder', Priority::HIGH);
        $this->config->addComponentPath('test'.DS.'config'.DS.'TestCumulativeConfigFile'.DS.'LowPriorityFolder', Priority::LOW);

        // And override a value
        Config::overrideConfig('cumulative', 'override', 'secondValue');

        // Load the config
        $config = $this->config->get('cumulative');

        // Check values
        $this->assertEquals("world", $config->get('first'));
        $this->assertEquals("highValue", $config->get('onlyInHigh'));
        $this->assertEquals("lowValue", $config->get('onlyInLow'));
        $this->assertEquals("secondValue", $config->get('override'));
    }

}
