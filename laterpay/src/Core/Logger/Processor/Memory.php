<?php

namespace LaterPay\Core\Logger\Processor;

/**
 * LaterPay core logger processor memory.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Memory
{
    /**
     * @var boolean If true, get the real size of memory allocated from system.
     * Else, only the memory used by emalloc() is reported.
     */
    protected $realUsage;

    /**
     * @var boolean If true, then format memory size to human readable string (MB, KB, B depending on size).
     */
    protected $useFormatting;

    /**
     * @param boolean $real_usage Set this to true to get the real size of memory allocated from system
     * @param boolean $use_formatting If true, then format memory size
     * to human readable string (MB, KB, B depending on size)
     */
    public function __construct($real_usage = true, $use_formatting = true)
    {
        $this->realUsage     = (boolean)$real_usage;
        $this->useFormatting = (boolean)$use_formatting;
    }

    /**
     * Formats bytes into a human readable string if $this->use_formatting is true, otherwise return $bytes as is.
     *
     * @param int $bytes
     *
     * @return string|int Formatted string if $this->use_formatting is true, otherwise return $bytes as is
     */
    protected function formatBytes($bytes)
    {
        $bytes = (int)$bytes;

        if (! $this->useFormatting) {
            return $bytes;
        }

        if ($bytes > 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }

        if ($bytes > 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
