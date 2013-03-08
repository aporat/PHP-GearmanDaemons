<?php
require_once 'vendor/autoload.php';

use \GearmanDaemons\WorkerAbstract;
use \GearmanDaemons\Manager;
use \Zend\Config\Config;

class Worker_Hello1 extends WorkerAbstract {

    protected $_registerFunction = 'Hello1';

    protected function _perform() {

        $body = unserialize($this->getWorkload());

        echo 'Hello1';
    }

}

class Worker_Hello2 extends WorkerAbstract {

    protected $_registerFunction = 'Hello2';

    protected function _perform() {

        $body = unserialize($this->getWorkload());

        echo 'Hello2';
    }
}

$config = new Config([
        'recover_workers' => true,
        'pid_file' => '/var/run/gearman-manager.pid',
        'servers' =>
        [
            ['host' => '127.0.0.1', 'port' => 4730]
        ]
]);


$manager = new Manager($config);
$manager->getLogger()->addWriter(new \Zend\Log\Writer\Stream('php://output'));
$manager->registerWorker(new Worker_Hello1());
$manager->registerWorker(new Worker_Hello2());
$manager->start();


