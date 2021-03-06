<?php

namespace LaterPay\Core\Logger\Processor;

/**
 * Injects memory_get_usage in all records
 *
 * @see \Monolog\Processor\MemoryProcessor::__construct() for options
 * @author Rob Jensen
 */
class MemoryUsage extends Memory implements ProcessorInterface
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
        $bytes     = memory_get_usage($this->realUsage);
        $formatted = $this->formatBytes($bytes);

        $record['extra'] = array_merge(
            $record['extra'],
            array('memory_usage' => $formatted)
        );

        return $record;
    }
}
