<?php
use \GearmanDaemons\WorkerAbstract;

class CustomWorker extends WorkerAbstract {
    
    protected $_registerFunction = 'CustomWorker';
    
    protected function _perform() {
    
        $body = unserialize($this->getWorkload());
    }
    
}