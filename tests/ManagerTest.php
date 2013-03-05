<?php
use \GearmanDaemons\Manager;
use \Zend\Log\Writer;
use \Zend\Config;

class GearmanDaemonsManagerTest extends PHPUnit_Framework_TestCase
{
	
    public function setUp()
    {
        $this->options = new \Zend\Config\Config(array(
            'recover_workers' => true,
            'servers' =>
             array (
                array('host' => '127.0.0.1', 'port' => 4730)
             )
        ));
        
        $this->worker = new Manager($this->options);
        $this->worker->getLogger()->addWriter(new Writer\Mock());
    }
    
    
    public function testTest()
    {

    	// ham shouldn't be marked as profanity
    	$this->assertFalse(false);
    }
  
}
