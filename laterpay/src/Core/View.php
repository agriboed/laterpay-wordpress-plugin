<?php

namespace LaterPay\Core;

use LaterPay\Model\ConfigInterface;
use LaterPay\Helper\ViewInterface;

/**
 * LaterPay core view.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class View implements ViewInterface
{
    /**
     * Contains all settings for the plugin.
     *
     * @var ConfigInterface
     */
    protected $config;

    /**
     * Variables for substitution in templates.
     *
     * @var array
     */
    protected $variables = array();

    /**
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Refresh config
     *
     * @return void
     */
    protected function refreshConfig()
    {
        laterpay_clean_plugin_cache();

        // set new config and update assignation
        $this->config = laterpay_get_plugin_config();
        $this->assign('config', $this->config);
    }

    /**
     * Assign variable for substitution in templates.
     *
     * @param string $variable name variable to assign
     * @param mixed $value value variable for assign
     *
     * @return self
     */
    public function assign($variable, $value)
    {
        $this->variables[$variable] = $value;

        return $this;
    }

    /**
     * Get HTML from file.
     *
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     *
     * @return string $html html output as string
     */
    public function getTextView($view, array $parameters = array())
    {
        $file = $this->config->get('view_dir') . $view . '.php';

        if (! file_exists($file)) {
            return '';
        }

        $parameters = array_merge($this->variables, $parameters);

        $closure = function () use ($file, $parameters) {
            foreach ($parameters as $key => $value) {
                ${$key} = $value;
            }

            ob_start();
            include $file;

            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        };

        return $closure();
    }

    /**
     * Render HTML file.
     *
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     *
     * @return self
     */
    public function render($view, array $parameters = array())
    {
        echo $this->getTextView($view, $parameters);

        return $this;
    }
}
