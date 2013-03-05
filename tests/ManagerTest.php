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
        
        $this->worker = new Manager($this->options);
        $this->worker->getLogger()->addWriter(new Writer\Mock());
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
    

}
