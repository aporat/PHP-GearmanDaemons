<?php
namespace GearmanDaemons;

use Zend\Log\Logger;
use \Zend\Log\Writer;
use \Zend\Config;

/**
 * Base manager class.
 *
 * @author Adar Porat <adar.porat@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class Manager
{

    const VERSION = '1.0.0';

    /**
     * User-provided configuration
     *
     * @var array
     */
    protected $_options = [];

    /**
     *
     * @var Logger
     */
    protected $_logger = null;

    /**
     * Array of worker aliases
     *
     * @var Array
     */
    private $_workers = array();

    /**
     * Is this process running as a Daemon?
     * 
     * @var boolean
     */
    private $_daemon = false;

    /**
     * Timestamp when was the worker started
     * 
     * @var integer
     */
    private $_start_time;

    /**
     * Recover workers if a worker was terminated
     *
     * @var bool
     */
    private $_recover_workers = false;

    /**
     * Maximum time a worker will run.
     * In Seconds
     * 
     * @var integer The interval in Seconds
     */
    private $_max_worker_run_time = 3600;

    /**
     *
     * @var boolean True if on the next iteration, the manager should shutdown.
     */
    private $_shutdown = false;

    /**
     *
     * @param
     *            string|array|Zend_Config|null, or options array or Zend_Config
     *            instance
     * @return void
     */
    public function __construct ($spec)
    {
        $this->_logger = new Logger();
        $this->_logger->addWriter(new Writer\Null());
        
        $this->_start_time = time();
        
        $this->_registerSigHandlers();
        
        $this->_logger->info('Application\Gearman\Manager loaded');
        
        if ($spec instanceof \Zend\Config\Config) {
            $options = $spec->toArray();
        } elseif (is_array($spec)) {
            $options = $spec;
        }
        
        $this->setOptions($options);
        
        if (array_key_exists('daemon', $this->_options) &&
                 $this->_options['daemon'] == true) {
            $this->_daemon = true;
            $this->_daemonize();
        }
        
        if (array_key_exists('recover_workers', $this->_options) &&
                 $this->_options['recover_workers'] == true) {
            $this->_recover_workers = true;
        }
        
        if (array_key_exists('auto_restart_interval', $this->_options)) {
            $this->_auto_restart_interval = $this->_options['auto_restart_interval'];
        }
        
        if (array_key_exists('pid_file', $this->_options)) {
            $this->_managePidfile($this->_options['pid_file']);
        }
        
        $this->_updateProcLine('');
    }

    /**
     * run as a php daemon
     */
    protected function _daemonize ()
    {
        $pid = pcntl_fork();
        
        if ($pid === - 1) {
            throw new \RuntimeException("Failed pcntl_fork");
        }
        if ($pid) {
            $this->_registerSigHandlers();
            
            $this->_logger->info("Started background process:" . $pid);
            exit(1);
        }
        
        posix_setsid();
    }

    /**
     * Register signal handlers that a worker should respond to.
     *
     * TERM: Shutdown immediately and stop processing jobs.
     * INT: Shutdown immediately and stop processing jobs.
     * QUIT: Shutdown after the current job finishes processing.
     * USR1: Kill the forked child immediately and continue processing jobs.
     */
    private function _registerSigHandlers ()
    {
        if (! function_exists('pcntl_signal')) {
            return;
        }
        
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array(
                $this,
                'shutdown'
        ));
        pcntl_signal(SIGINT, array(
                $this,
                'shutdown'
        ));
        pcntl_signal(SIGQUIT, array(
                $this,
                'shutdown'
        ));
        
        $this->_logger->info('Registered signals');
    }

    /**
     * Schedule a worker for shutdown.
     * Will finish processing the current job
     * and when the timeout interval is reached, the worker will shut down.
     */
    public function shutdown ()
    {
        $this->_signalAllWorkers(SIGKILL);
        
        $this->_logger->info('Exiting...');
        $this->_shutdown = true;
        
        exit(1);
    }
    
    /**
     * return the zned logger object
     * 
     * @return \Zend_Log         
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * signal all the child workers
     * 
     * @param int $signal            
     */
    protected function _signalAllWorkers ($signal)
    {
        foreach ($this->_workers as $worker) {
            $this->_logger->info(
                    'Sending ' . $signal . ' Signal ' . $worker->getPid());
            if (getmypid() != $worker->getPid()) {
                posix_kill($worker->getPid(), $signal);
            }
        }
        
        $this->_logger->info('All workers terminated');
    }

    /**
     * registers a worker into the manager
     */
    public function registerWorker (WorkerAbstract $worker)
    {
        $this->_logger->info('Registering ' . $worker->getRegisterFunction());
        
        $this->_spawnWorker($worker);
    }

    /**
     * spawn a worker into it's own process
     * 
     * @param WorkerAbstract $worker            
     */
    protected function _spawnWorker (WorkerAbstract $worker)
    {
        $pid = pcntl_fork();
        
        if ($pid === - 1) {
            $this->_logger->info('pcntl_fork failed');
            exit(0);
        } elseif ($pid === 0) {
            $worker->registerSigHandlers();
            $worker->prepare();
            
            $worker->_start_time = time();
            $gearmanWorker = new \GearmanWorker();
            
            foreach ($this->_options['servers'] as $server) {
                $gearmanWorker->addServer($server['host'], $server['port']);
            }
            
            $gearmanWorker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
            
            $gearmanWorker->addFunction($worker->getRegisterFunction(), 
                    [
                            $worker,
                            'execute'
                    ]);
            
            while (true) {
                if ($worker->_shutdown) {
                    break;
                }
                
                if ($worker->runtime() > $this->_max_worker_run_time) {
                    $worker->shutdown();
                }
                
                pcntl_signal_dispatch();
                $gearmanWorker->work();
                
                usleep(50000);
            }
        } else {
            $this->_logger->info(
                    'Worker ' . $worker->getRegisterFunction() . ' to ' . $pid);
            $worker->setPid($pid);
            $this->_workers[] = $worker;
            $this->_registerSigHandlers();
        }
    }

    /**
     * starts the workers
     */
    public function start ()
    {
        $this->_logger->info('Manager started');
        $this->_updateProcLine();
        
        while (true) {
            
            if ($this->_shutdown) {
                break;
            }
            
            pcntl_signal_dispatch();
            
            if ($this->_recover_workers) {
                $status = null;
                $exitedPid = pcntl_wait($status, WNOHANG);
                
                foreach ($this->_workers as $worker) {
                    if ($worker->getPid() == $exitedPid) {
                        $this->_logger->info('Worker was ternimated');
                        
                        if (! $this->_shutdown) {
                            $this->_spawnWorker($worker);
                        }
                    }
                }
            }
            
            usleep(50000);
        }
        
        $this->_logger->info('Manager finished');
    }

    /**
     * On supported systems (with the PECL proctitle module installed), update
     * the name of the currently running process to indicate name of the manager
     */
    private function _updateProcLine ()
    {
        if (function_exists('setproctitle')) {
            setproctitle('GearmanManager-' . self::VERSION);
        }
    }

    protected function _managePidfile ($pidfile)
    {
        if (! $pidfile) {
            return;
        }
        
        if (file_exists($pidfile)) {
            unlink($pidfile);
        } elseif (! is_dir($piddir = basename($pidfile))) {
            mkdir($piddir, 0777, true);
        }
        
        file_put_contents($pidfile, getmypid(), LOCK_EX);
        register_shutdown_function(
                function  () use( $pidfile)
                {
                    if (getmypid() === file_get_contents($pidfile)) {
                        unlink($pidfile);
                    }
                });
    }

    /**
     * Return the running time in Seconds
     * 
     * @return integer
     */
    public function runtime ()
    {
        return time() - $this->_start_time;
    }

    /**
     * Set manager options
     *
     * @param array $options            
     * @return \Manager
     */
    public function setOptions (array $options)
    {
        $this->_options = array_merge($this->_options, $options);
        
        return $this;
    }
}