<?php

namespace LaterPay\Core\Logger\Handler;

use LaterPay\Core\Logger;
use LaterPay\Core\Logger\Formatter\Normalizer;
use LaterPay\Core\Logger\Formatter\FormatterInterface;

/**
 * Class HandlerAbstract
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
abstract class HandlerAbstract implements HandlerInterface
{
    /**
     * @var \LaterPay\Core\Logger\Formatter\FormatterInterface
     */
    protected $formatter;

    /**
     * @var array Array of processors for record
     */
    protected $processors = array();

    /**
     * @see Logger
     * @var int Level of record to handle
     */
    protected $level = Logger::DEBUG;

    /**
     * @param integer $level
     */
    public function __construct($level = Logger::DEBUG)
    {
        $this->level = $level;
    }

    /**
     * Hanlder for array of records
     *
     * @param array $records Description
     *
     * @return void
     */
    public function handleBatch(array $records)
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    protected function getFormatted(array $record)
    {
        $output = "%datetime%:%pid%.%channel%.%level_name%: %message% %context%\n";
        foreach ($record as $var => $val) {
            $output = str_replace('%' . $var . '%', $this->convertToString($val), $output);
        }

        return $output;
    }

    /**
     * Closes the handler.
     *
     * This will be called automatically when the object is destroyed.
     */
    public function close()
    {
    }

    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Exception $e) {
            // do nothing
            $e->getMessage();
        }
    }

    /**
     * Convert data into string
     *
     * @param mixed $data
     *
     * @return string
     */
    protected function convertToString($data)
    {
        if (null === $data || is_scalar($data)) {
            return (string)$data;
        }

        if (PHP_VERSION_ID >= 50400 && defined('JSON_UNESCAPED_SLASHES') && defined('JSON_UNESCAPED_UNICODE')) {
            return wp_json_encode($this->normalize($data), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return str_replace('\\/', '/', wp_json_encode($this->normalize($data)));
    }

    /**
     * Data normalization.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    protected function normalize($data)
    {
        if (is_bool($data) || null === $data) {
            return null;
        }

        if (null === $data || is_scalar($data)) {
            return $data;
        }

        if (is_array($data) || $data instanceof \Traversable) {
            $normalized = array();

            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalize($value);
            }

            return $normalized;
        }

        if ($data instanceof \DateTime) {
            return $data->format('Y-m-d H:i:s.u');
        }

        if (is_object($data)) {
            return sprintf('[object] (%s: %s)', get_class($data), wp_json_encode($data));
        }

        if (is_resource($data)) {
            return '[resource]';
        }

        return '[unknown(' . gettype($data) . ')]';
    }

    /**
     * Is needed to handle or not.
     *
     * @param array Record data
     *
     * @return bool
     */
    public function isHandling(array $record)
    {
        return $record['level'] >= $this->level;
    }


    /**
     * @param callable new processor which must be added into processors list
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function pushProcessor($callback)
    {
        if (! is_callable($callback)) {
            throw new \InvalidArgumentException(
                'Processors must be valid callables
                 (callback or object with an __invoke method), ' . $callback . ' given'
            );
        }

        array_unshift($this->processors, $callback);

        return $this;
    }

    /**
     * Remove first processor from stack
     *
     * @throws \LogicException
     *
     * @return callable first processor from stack
     */
    public function popProcessor()
    {
        if (! $this->processors) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift($this->processors);
    }

    /**
     * @param FormatterInterface $formatter
     *
     * @return self
     */
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @return FormatterInterface current or default formatter
     */
    public function getFormatter()
    {
        if (! $this->formatter) {
            $this->formatter = $this->getDefaultFormatter();
        }

        return $this->formatter;
    }

    /**
     * Sets minimum logging level at which this handler will be triggered.
     *
     * @param  integer $level
     *
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Gets minimum logging level at which this handler will be triggered.
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Gets the default formatter
     *
     * @return Normalizer
     */
    protected function getDefaultFormatter()
    {
        return new Normalizer();
    }
}
