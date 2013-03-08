<?php
use \GearmanDaemons\Manager;
use \Zend\Log\Writer;
use \Zend\Config\Config;

class GearmanDaemonsManagerTest extends PHPUnit_Framework_TestCase
{
	
    public function setUp()
    {
        $this->options = new Config(array(
            'recover_workers' => true,
            'servers' =>
             array (
                array('host' => '127.0.0.1', 'port' => 4730)
             )
        ));
        
        $this->manager = new Manager($this->options);
    }
    
    public function testConstruct()
    {
        try {
            $manager = new Manager($this->options);
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail('should have gotten a valid object');
        }
    
        // parameter verification
        try {
            $manager = new Manager();
            $this->fail('should have thrown an exception bad queue var');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }
    
    
    public function testAddInvalidWorker()
    {
        try {
            $this->manager->registerWorker(new stdClass());
            $this->fail('should have thrown an exception');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

}
