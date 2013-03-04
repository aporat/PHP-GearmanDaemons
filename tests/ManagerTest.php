<?php
use GearmanDaemons\Manager;

class GearmanDaemonsManagerTest extends PHPUnit_Framework_TestCase
{
	
    public function setUp()
    {
        $config = new \Zend_Config(array(
            'recover_workers' => true,
            'servers' =>
             array (
                array('host' => '127.0.0.1', 'port' => 4730)
             )
        ));
        
        $this->worker = new Manager($config);
        $this->worker->getLogger()->addWriter(new \Zend_Log_Writer_Mock());
    }
    
    
    public function testTest()
    {

    	// ham shouldn't be marked as profanity
    	$this->assertFalse(false);
    }
  
}
