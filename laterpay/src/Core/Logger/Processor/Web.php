<?php

namespace LaterPay\Core\Logger\Processor;

use LaterPay\Core\Request;

/**
 * LaterPay core logger processor web.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Web implements ProcessorInterface
{

    /**
     * @var array|\ArrayAccess
     */
    protected $serverData;

    /**
     * @var array
     */
    protected $extraFields = array(
        'url'         => 'REQUEST_URI',
        'ip'          => 'REMOTE_ADDR',
        'http_method' => 'REQUEST_METHOD',
        'server'      => 'SERVER_NAME',
        'referrer'    => 'HTTP_REFERER',
    );

    /**
     * @param array|\ArrayAccess $serverData Array or object w/ ArrayAccess that provides access to the $_SERVER data
     * @param array|null         extra_fields Extra field names to be added (all available by default)
     *
     * @throws \UnexpectedValueException
     *
     * @return void
     */
    public function __construct($serverData = null, array $extraFields = null)
    {
        if ($serverData === null) {
            $this->serverData = array_map('sanitize_text_field', Request::server());
        } elseif (is_array($serverData) || $serverData instanceof \ArrayAccess) {
            $this->serverData = $serverData;
        } else {
            throw new \UnexpectedValueException('$serverData must be an array or object implementing ArrayAccess.');
        }

        if ($extraFields !== null) {
            foreach (array_keys($this->extraFields) as $fieldName) {
                if (! in_array($fieldName, $extraFields, true)) {
                    unset($this->extraFields[ $fieldName ]);
                }
            }
        }
    }

    /**
     * Record processor
     *
     * @param array record data
     *
     * @return array processed record
     */
    public function process(array $record)
    {
        // skip processing if for some reason request data is not present (CLI or wonky SAPIs)
        if (! isset($this->serverData['REQUEST_URI'])) {
            return $record;
        }

        $record['extra'] = $this->appendExtraFields($record['extra']);

        return $record;
    }

    /**
     * @param string $extraName
     * @param string $serverName
     *
     * @return $this
     */
    public function addExtraField($extraName, $serverName)
    {
        $this->extraFields[ $extraName ] = $serverName;

        return $this;
    }

    /**
     * @param array $extra
     *
     * @return array
     */
    private function appendExtraFields(array $extra)
    {
        foreach ($this->extraFields as $extraName => $serverName) {
            $extra[ $extraName ] = isset($this->serverData[ $serverName ]) ? $this->serverData[ $serverName ] : null;
        }

        if (isset($this->serverData['UNIQUE_ID'])) {
            $extra['unique_id'] = $this->serverData['UNIQUE_ID'];
        }

        return $extra;
    }
}
