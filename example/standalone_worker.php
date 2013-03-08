<?php
require_once 'vendor/autoload.php';

use \GearmanDaemons\WorkerAbstract;

class Worker_Hello extends WorkerAbstract {
    
    protected $_registerFunction = 'CustomWorker';
    
    protected function _perform() {
    
        $body = unserialize($this->getWorkload());
        
        echo 'Hello';
    }
    
}

$worker = new Worker_Hello();
$worker->run();
