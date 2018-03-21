<?php

namespace LaterPay\Core\Logger\Processor;

/**
 * LaterPay core logger processor memory peak usage.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class MemoryPeakUsage extends Memory implements ProcessorInterface
{
    /**
     * Record processor
     *
     * @param array record data
     *
     * @return array processed record
     */
    public function process(array $record)
    {
        $bytes     = memory_get_peak_usage($this->realUsage);
        $formatted = $this->formatBytes($bytes);

        $record['extra'] = array_merge(
            $record['extra'],
            array('memory_peak_usage' => $formatted)
        );

        return $record;
    }
}
