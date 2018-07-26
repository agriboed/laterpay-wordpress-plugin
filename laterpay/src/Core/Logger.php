<?php

namespace LaterPay\Core;

use LaterPay\Core\Logger\LoggerInterface;
use LaterPay\Core\Logger\Handler\Nothing;
use LaterPay\Core\Logger\Handler\HandlerInterface;
use LaterPay\Core\Logger\Processor\ProcessorInterface;

/**
 * LaterPay core logger.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Logger implements LoggerInterface
{

    /**
     * Logger levels
     */
    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 250;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;
    const ALERT = 550;
    const EMERGENCY = 600;

    /**
     * Contains all debugging levels.
     *
     * @var array
     */
    protected static $levels = array(
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    );

    /**
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $name;

    /**
     * The handler stack
     *
     * @var \LaterPay\Core\Logger\Handler\HandlerInterface[]
     */
    protected $handlers;

    /**
     * Processors that will process all log records
     *
     * To process records of a single handler instead, add the processor on that specific handler
     *
     * @var ProcessorInterface[]
     */
    protected $processors;

    /**
     * @param string $name The logging channel
     * @param \LaterPay\Core\Logger\Handler\HandlerInterface[] $handlers
     * Optional stack of handlers, the first one in the array is called first, etc.
     * @param ProcessorInterface[] $processors Optional array of processors
     *
     * @return void
     */
    public function __construct($name = 'default', array $handlers = array(), array $processors = array())
    {
        $this->name       = $name;
        $this->handlers   = $handlers;
        $this->processors = $processors;
        $this->timezone   = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
    }

    /**
     * Add a log record at the DEBUG level.
     *
     * @param string $message The log message
     * @param array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function debug($message, array $context = array())
    {
        return $this->addRecord(self::DEBUG, $message, $context);
    }

    /**
     * Add a log record at the ERROR level.
     *
     * @param string $message The log message
     * @param array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function error($message, array $context = array())
    {
        return $this->addRecord(self::ERROR, $message, $context);
    }

    /**
     * Add a log record at the INFO level.
     *
     * @param  string $message The log message
     * @param  array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function info($message, array $context = array())
    {
        return $this->addRecord(self::INFO, $message, $context);
    }

    /**
     * Add a log record at the NOTICE level.
     *
     * @param  string $message The log message
     * @param  array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function notice($message, array $context = array())
    {
        return $this->addRecord(self::NOTICE, $message, $context);
    }

    /**
     * Add a log record at the WARNING level.
     *
     * @param  string $message The log message
     * @param  array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function warning($message, array $context = array())
    {
        return $this->addRecord(self::WARNING, $message, $context);
    }

    /**
     * Add a log record at the CRITICAL level.
     *
     * @param  string $message The log message
     * @param  array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function critical($message, array $context = array())
    {
        return $this->addRecord(self::CRITICAL, $message, $context);
    }

    /**
     * Add a log record at the ALERT level.
     *
     * @param  string $message The log message
     * @param  array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function alert($message, array $context = array())
    {
        return $this->addRecord(self::ALERT, $message, $context);
    }

    /**
     * Add a log record at the EMERGENCY level.
     *
     * @param  string $message The log message
     * @param  array $context The log context
     *
     * @return boolean Whether the record has been processed
     */
    public function emergency($message, array $context = array())
    {
        return $this->addRecord(self::EMERGENCY, $message, $context);
    }

    /**
     * Add a record to the log.
     *
     * @param integer $level
     * @param string $message
     * @param array $context
     *
     * @return boolean
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (! $this->handlers) {
            $this->pushHandler(new Nothing());
        }

        $dateTime = new \DateTime('now', $this->timezone);

        $record = array(
            'message'    => (string)$message,
            'context'    => $context,
            'level'      => $level,
            'level_name' => self::getLevelName($level),
            'channel'    => $this->name,
            'datetime'   => $dateTime,
            'extra'      => array(),
        );

        // check, if any handler will handle this message
        $handlerKey = null;

        foreach ($this->handlers as $key => $handler) {
            if ($handler->isHandling($record)) {
                $handlerKey = $key;
                break;
            }
        }

        if ($handlerKey === null) {
            // no handler found
            return false;
        }

        // found at least one handler, so process message and dispatch it
        foreach ($this->processors as $processor) {
            $record = $processor->process($record);
        }

        while (isset($this->handlers[$handlerKey]) &&
               $this->handlers[$handlerKey]->handle($record) === false
        ) {
            $handlerKey++;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Push a handler onto the stack.
     *
     * @param \LaterPay\Core\Logger\Handler\HandlerInterface $handler
     */
    public function pushHandler(HandlerInterface $handler)
    {
        array_unshift($this->handlers, $handler);
    }

    /**
     * Pop a handler from the stack.
     *
     * @throws \LogicException
     *
     * @return \LaterPay\Core\Logger\Handler\HandlerInterface
     */
    public function popHandler()
    {
        if (! $this->handlers) {
            throw new \LogicException('You tried to pop from an empty handler stack.');
        }

        return array_shift($this->handlers);
    }

    /**
     * @return \LaterPay\Core\Logger\Handler\HandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Add a processor to the stack.
     *
     * @param ProcessorInterface $callback
     */
    public function pushProcessor(ProcessorInterface $callback)
    {
        array_unshift($this->processors, $callback);
    }

    /**
     * Remove the processor on top of the stack and return it.
     *
     * @throws \LogicException
     *
     * @return callable
     */
    public function popProcessor()
    {
        if (! $this->processors) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift($this->processors);
    }

    /**
     * @return callable[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * Check, if the logger has a handler that listens on the given level.
     *
     * @param integer $level
     *
     * @return boolean
     */
    public function isHandling($level)
    {
        $record = array(
            'level' => $level,
        );

        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($record)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the name of the logging level.
     *
     * @param integer $level
     *
     * @return string $level_name
     */
    public static function getLevelName($level)
    {
        return static::$levels[$level];
    }
}
