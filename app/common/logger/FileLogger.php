<?php
namespace app\common\logger;

use Phalcon\Di;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileAdapter;

class FileLogger extends FileAdapter implements InjectionAwareInterface
{
    const LEVEL_VALUES = [
        "DEBUG"     => Logger::DEBUG,
        "INFO"      => Logger::INFO,
        "WARNING"   => Logger::WARNING,
        "ERROR"     => Logger::ERROR,
        "CRITICAL"  => Logger::CRITICAL
    ];

    protected $_dependencyInjector;

    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);
        $this->getFormatter()->setDateFormat('Y-m-d H:i:s');
    }

    /**
     * Sets the dependency injector
     *
     * @param mixed $dependencyInjector
     */
    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Returns the internal dependency injector
     *
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        $dependencyInjector = $this->_dependencyInjector;
        if (is_object($dependencyInjector) == false) {
            $dependencyInjector = Di::getDefault();
            if (is_object($dependencyInjector) == false) {
                throw new \Exception("A dependency injection object is required to access the application services");
            }
        }
        return $dependencyInjector;
    }

    public function __get($name)
    {
        $dependencyInjector = $this->getDI();

        if ($dependencyInjector->has($name)) {
            $service = $dependencyInjector->getShared($name);
            $this->{$name} = $service;
            return $service;
        }

        if ($name == "di") {
            $this->{"di"} = $dependencyInjector;
            return $dependencyInjector;
        }
        trigger_error("Access to undefined property " . $name);
        return null;
    }


}