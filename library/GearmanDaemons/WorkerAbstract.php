<?php
namespace GearmanDaemons;

use Zend\Log\Logger;
use \Zend\Log\Writer;

abstract class WorkerAbstract
{
    /** 
     * Register Function
     * @var string
     */
    protected $_registerFunction;
 
    /** 
     * Gearman Worker
     * @var GearmanWorker
     */
    protected $_worker;

    /**
     *
     * @var Logger
     */
    protected $_logger = null;
    
    /**
     * proces id of the worker, if executed by the manager
     * @var int
     */
    protected $_pid;
    
    /**
     * @var boolean True if on the next iteration, the worker should shutdown.
     */
    public $_shutdown = false;
    
    /**
     * Timestamp when was the worker started
     * @var integer
     */
    public $_start_time;
    
    /**
     * Constructor
     * Checks for the required gearman extension,
     *
     * @return \WorkerAbstract
     */
    public function __construct()
    {
        $this->_logger = new Logger();
        $this->_logger->addWriter(new Writer\Null());
                
        $this->_start_time = time();
        
        $this->registerSigHandlers();
        
        $this->_init();
        
    }
    
    /**
     * init the application (zend)

     * @return \WorkerAbstract
     */
    public function _init()
    {
        declare(ticks = 1);
                        
        return $this;
    }

    /**
     * prepare the worker
     */
    public function prepare()
    {
        $this->_updateProcLine('Idle');
        
    }
    
    public function getRegisterFunction()
    {
        return $this->_registerFunction;
    }
 
    /**
     * Set Job Workload
     *
     * @param mixed
     * @return void
     */
    public function setWorkload($workload)
    {
        $this->_workload = $workload;
    }
 
    /**
     * Get Job Workload
     *
     * @return mixed
     */
    public function getWorkload()
    {
        return $this->_workload;
    }
 
    /**
     * Set Process ID
     *
     * @param int
     * @return void
     */
    public function setPid($pid)
    {
    	$this->_pid = $pid;
    }
    
    /**
     * Get Process ID
     *
     * @return mixed
     */
    public function getPid()
    {
    	return $this->_pid;
    }
    
    /**
     * Work, work, work
     *
     * @return void
     */
    public final function execute(\GearmanJob $job)
    {
        $this->_logger->info('Worker Job ' . $this->_registerFunction . ' started');

        $this->_updateProcLine('Working');
        
        $this->setWorkload($job->workload());
        
        try {
            $ret = $this->_perform();
        } catch (\Exception $e) {
            
        }

        $this->_logger->info('Worker Job ' . $this->_registerFunction . ' finished');
        $this->_updateProcLine('Idle');
    }
    
    /**
     * Register signal handlers that a worker should respond to.
     *
     * TERM: Shutdown immediately and stop processing jobs.
     * INT: Shutdown immediately and stop processing jobs.
     * QUIT: Shutdown after the current job finishes processing.
     * USR1: Kill the forked child immediately and continue processing jobs.
     */
    public function registerSigHandlers()
    {
    	if(!function_exists('pcntl_signal')) {
    		return;
    	}
    
    	declare(ticks = 1);
    	pcntl_signal(SIGTERM, array($this, 'shutdown'));
    	pcntl_signal(SIGINT, array($this, 'shutdown'));
    	pcntl_signal(SIGQUIT, array($this, 'shutdown'));
    
    	$this->_logger->info('Registered worker signals');
    }
    
    /**
     * On supported systems (with the PECL proctitle module installed), update
     * the name of the currently running process to indicate the current state
     * of a worker.
     *
     * @param string $status The updated process title.
     */
    private function _updateProcLine($status)
    {
    	if(function_exists('setproctitle')) {
    		setproctitle('GearmanManager: ' . $this->_registerFunction . ': ' . $status);
    	}
    }
    
    /**
     * Return the running time in Seconds
     * @return integer
     */
    public function runtime()
    {
    	return time() - $this->_start_time;
    }
    
    
    /**
     * Schedule a worker for shutdown. Will finish processing the current job
     * and when the timeout interval is reached, the worker will shut down.
     */
    public function shutdown()
    {
        $this->_shutdown = true;
    	$this->_logger->info('Exiting worker ' . $this->_registerFunction);
    	exit(1);
    }
    
    /**
     * Run the worker in standalone mode
     */
    public function run()
    {
        $this->registerSigHandlers();
        
        $this->_worker = new \GearmanWorker();
        $this->_worker->addServer();
        $this->_worker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        
        $this->_worker->addFunction($this->getRegisterFunction(), array($this, 'execute'));
        
        while ($this->_worker->work()) {
            if ($this->_shutdown) {
            	break;
            }
            
            pcntl_signal_dispatch();
            
        	if (GEARMAN_SUCCESS != $this->_worker->returnCode()) {
        		echo "Worker failed: " . $this->_worker->error() . "\n";
        	}
        }
    }
    
    /**
     * return the zend logger object
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->_logger;
    }
    
}