<?php

namespace LaterPay\Core\Event;

use LaterPay\Core\Event;

/**
 * LaterPay Event Dispatcher Interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface DispatcherInterface {

	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @param string $event_name The name of the event to dispatch.
	 * @param Event|array|null $args The event to pass to the event handlers/listeners.
	 *
	 * @return Event
	 */
	public function dispatch( $event_name, $args = null);

	/**
	 * Adds an event listener that listens on the specified events.
	 *
	 * @param string $event_name The event name to listen on.
	 * @param callable $listener The event listener.
	 * @param int $priority The higher this value, the earlier an event
	 *                            listener will be triggered in the chain (defaults to 0)
	 *
	 * @return null
	 */
	public function addListener( $event_name, $listener, $priority = 0);

	/**
	 * Removes an event listener from the specified events.
	 *
	 * @param string $event_name The event name to listen on.
	 * @param callable $listener The event listener.
	 *
	 * @return mixed
	 */
	public function removeListener( $event_name, $listener);

	/**
	 * Gets the listeners of a specific event or all listeners.
	 *
	 * @param string|null $event_name The event name to get listeners or null to get all.
	 *
	 * @return mixed
	 */
	public function getListeners( $event_name = null);

	/**
	 * Checks whether an event has any registered listeners.
	 *
	 * @param string|null $event_name
	 *
	 * @return mixed
	 */
	public function hasListeners( $event_name = null);

	/**
	 * Adds an event subscriber.
	 *
	 * The subscriber is asked for all the events he is
	 * interested in and added as a listener for these events.
	 *
	 * @param SubscriberInterface $subscriber The subscriber.
	 */
	public function addSubscriber( SubscriberInterface $subscriber);

	/**
	 * Removes an event subscriber.
	 *
	 * @param SubscriberInterface $subscriber The subscriber
	 */
	public function removeSubscriber( SubscriberInterface $subscriber);
}
