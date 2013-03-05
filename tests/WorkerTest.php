<?php
require_once 'Custom/Worker.php';

use \Zend\Log\Writer;


class GearmanDaemonsWorkerTest extends PHPUnit_Framework_TestCase
{
	
    public function setUp()
    {
        $this->worker = new CustomWorker();
        $this->worker->getLogger()->addWriter(new Writer\Mock());
    }
    
    
    public function testConstruct()
    {
        try {
            $manager = new CustomWorker();
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail('should have gotten a valid object');
        }
    }

}
    