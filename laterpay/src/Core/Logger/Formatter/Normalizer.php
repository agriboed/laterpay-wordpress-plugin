<?php

namespace LaterPay\Core\Logger\Formatter;

/**
 * LaterPay logger formatter normalizer.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Normalizer implements FormatterInterface
{

    /**
     * @const string default date format
     */
    const SIMPLE_DATE = 'H:i:s j.m.Y';

    /**
     * @var string date format
     */
    protected $dateFormat;

    /**
     * @param string $dateFormat The format of the timestamp: one supported by DateTime::format
     *
     * @return void
     */
    public function __construct($dateFormat = null)
    {
        $this->dateFormat = ($dateFormat === null) ? self::SIMPLE_DATE : $dateFormat;
    }

    /**
     * Equile to normalize method
     *
     * @param array $record data
     *
     * @return string
     */
    public function format(array $record)
    {
        return $this->normalize($record);
    }

    /**
     * @param array array of records data to normalize
     *
     * @return array
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }

    /**
     * Transform record into normalized form.
     *
     * @param mixed $data - incoming variable for normalizing
     *
     * @return string|array
     */
    protected function normalize($data)
    {
        if (null === $data || is_scalar($data)) {
            return $data;
        }

        if (is_array($data) || $data instanceof \Traversable) {
            $normalized = array();

            $count = 1;
            foreach ($data as $key => $value) {
                if ($count++ >= 1000) {
                    $normalized['...'] = 'Over 1000 items, aborting normalization';
                    break;
                }
                $normalized[$key] = $this->normalize($value);
            }

            return $normalized;
        }

        if ($data instanceof \DateTime) {
            return $data->format($this->dateFormat);
        }

        if (is_object($data)) {
            if ($data instanceof \Exception) {
                return $this->normalizeException($data);
            }

            return sprintf('[object] (%s: %s)', get_class($data), $this->toJSON($data, true));
        }

        if (is_resource($data)) {
            return '[resource]';
        }

        return '[unknown(' . gettype($data) . ')]';
    }

    /**
     * Special method for normalizing exception.
     *
     * @param \Exception $e
     *
     * @return string|array
     */
    protected function normalizeException(\Exception $e)
    {
        $data = array(
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        );

        $trace = $e->getTrace();
        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $data['trace'][] = $frame['file'] . ':' . $frame['line'];
            } else {
                $data['trace'][] = wp_json_encode($frame);
            }
        }

        $previous = $e->getPrevious();

        if ($previous) {
            $data['previous'] = $this->normalizeException($previous);
        }

        return $data;
    }

    /**
     * Convert variable into JSON.
     *
     * @param mixed $data
     * @param bool $ignoreErrors - ignore errors or not
     *
     * @return string
     */
    protected function toJSON($data, $ignoreErrors = false)
    {
        // suppress json_encode errors since it's twitchy with some inputs
        if ($ignoreErrors) {
            if (PHP_VERSION_ID >= 50400) {
                return wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return wp_json_encode($data);
        }

        if (PHP_VERSION_ID >= 50400) {
            return wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return wp_json_encode($data);
    }
}
