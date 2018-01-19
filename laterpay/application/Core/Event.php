<?php

namespace LaterPay\Core;

use LaterPay\Helper\Strings;

/**
 * Event is the base class for classes containing event data.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Event {

	/**
	 *
	 */
	const TYPE_TEXT = 'text';

	/**
	 *
	 */
	const TYPE_HTML = 'html';

	/**
	 *
	 */
	const TYPE_JSON = 'json';

	/**
	 * Event name.
	 *
	 * @var string Event name.
	 */
	protected $name;

	/**
	 * Should be event result output
	 */
	protected $echoOutput = true;

	/**
	 * Event result
	 * @var mixed
	 */
	protected $result;

	/**
	 * Array of arguments.
	 *
	 * @var array
	 */
	protected $arguments;

	/**
	 * @var bool Whether no further event listeners should be triggered
	 */
	protected $propagations_stopped = false;

	/**
	 * @var bool who has stopped event
	 */
	protected $propagations_stopped_by = '';

	/**
	 * @var string $type Event result type.
	 */
	protected $type = self::TYPE_TEXT;

	/**
	 * @var bool $ajax Is ajax event
	 */
	protected $ajax = false;

	/**
	 * Encapsulate an event with $args.
	 *
	 * @param array $arguments Arguments to store in the event.
	 */
	public function __construct( array $arguments = array() ) {
		$this->arguments = $arguments;
	}

	/**
	 * Set event result type
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $type
	 *
	 * @return Event
	 */
	public function setType( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * Check if event is for ajax request.
	 *
	 * @return boolean
	 */
	public function isAjax() {
		return $this->ajax;
	}

	/**
	 * Set ajax attribute option
	 *
	 * @param boolean $ajax
	 */
	public function setAjax( $ajax ) {
		$this->ajax = $ajax;
	}

	/**
	 * Returns whether further event listeners should be triggered.
	 *
	 * @return bool Whether propagation was already stopped for this event.
	 */
	public function isPropagationStopped() {
		return $this->propagations_stopped;
	}

	public function setPropagationsStoppedBy( $listener ) {
		if ( is_array( $listener ) && is_object( $listener[0] ) ) {
			$name = '[[object] (' . get_class( $listener[0] ) . ': {}),"' . ( isset( $listener[1] ) ? $listener[1] : '__invoke' ) . '"]';
		} else {
			$name = (string) $listener;
		}
		$this->propagations_stopped_by = $name;
	}

	/**
	 * Stops the propagation of the event to further event listeners.
	 *
	 * @return void
	 */
	public function stopPropagation() {
		$this->propagations_stopped = true;
	}

	/**
	 * Getter for all arguments.
	 *
	 * @return array
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Set args property.
	 *
	 * @param array $args Arguments.
	 *
	 * @return Event
	 */
	public function setArguments( array $args = array() ) {
		$this->arguments = $args;

		return $this;
	}

	/**
	 * Get argument by key.
	 *
	 * @param string $key Key.
	 *
	 * @return mixed|null Contents of array key.
	 */
	public function getArgument( $key ) {
		if ( $this->hasArgument( $key ) ) {
			return $this->arguments[ $key ];
		}

		return null;
	}

	/**
	 * Has argument.
	 *
	 * @param string $key Key of arguments array.
	 *
	 * @return bool
	 */
	public function hasArgument( $key ) {
		return array_key_exists( $key, $this->arguments );
	}

	/**
	 * Add argument to event.
	 *
	 * @param string $key Argument name.
	 * @param mixed $value Value.
	 *
	 * @return Event
	 */
	public function setArgument( $key, $value ) {
		$this->arguments[ $key ] = $value;

		return $this;
	}

	/**
	 * Safety adds arguments to event. if such argument is already present appends new one
	 *
	 * @param string $key Argument name.
	 * @param mixed $value Value.
	 *
	 * @return Event
	 */
	public function addArgument( $key, $value ) {
		if ( $this->hasArgument( $key ) ) {
			$argument = (array) $this->getArgument( $key );
			$value    = (array) $value;
			$this->setArgument( $key, array_merge( $argument, $value ) );
		} else {
			$this->setArgument( $key, $value );
		}

		return $this;
	}

	/**
	 * Get event result.
	 *
	 * @return mixed
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * Get formatted result.
	 *
	 * @return mixed
	 */
	public function getFormattedResult() {
		$result = $this->getResult();
		switch ( $this->getType() ) {
			default:
			case self::TYPE_TEXT:
			case self::TYPE_HTML:
				$result = empty( $result ) ? '' : $result;
				break;
			case self::TYPE_JSON:
				// add debug data to JSON/AJAX output
				$debug = laterpay_get_plugin_config()->get( 'debug_mode' );
				if ( $debug && is_array( $result ) ) {
					$listeners = laterpay_event_dispatcher()->getListeners( $this->getName() );

					/**
					 * @var $listeners array
					 */
					foreach ( $listeners as $key => $listener ) {
						if ( is_array( $listener ) && is_object( $listener[0] ) ) {
							$listeners[ $key ] = array( get_class( $listener[0] ) ) + $listener;
						}
					}
					$result['listeners'] = $listeners;
					$result['debug']     = $this->getDebug();
				}
				$result = Strings::laterpayJSONEncode( $result );
				break;
		}

		return $result;
	}

	/**
	 * Set event result.
	 *
	 * @param mixed $value Value.
	 *
	 * @return Event
	 */
	public function setResult( $value ) {
		$this->result = $value;

		return $this;
	}

	/**
	 * Return flag if we should output event result.
	 *
	 * @return bool
	 */
	public function isEchoEnabled() {
		return $this->echoOutput;
	}

	/**
	 * Set flag that we should output event result.
	 *
	 * @param bool $echoOutput
	 *
	 * @return Event
	 */
	public function setEchoOutput( $echoOutput ) {
		$this->echoOutput = $echoOutput;

		return $this;
	}

	/**
	 * Gets debug information
	 *
	 * @return array
	 */
	public function getDebug() {
		return array(
			'is_echo_enabled'        => $this->isEchoEnabled() ? 'true' : 'false',
			'is_propagation_stopped' => $this->isPropagationStopped() ? 'true' : 'false',
			'propagation_stopped_by' => $this->propagations_stopped_by,
			'arguments'              => $this->getArguments(),
			'result'                 => $this->getResult(),
		);
	}

	/**
	 * Set event name.
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get event name.
	 *
	 * @param string $name
	 */
	public function setName( $name ) {
		$this->name = $name;
	}
}
