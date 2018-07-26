<?php

namespace LaterPay\Core\Event;

use LaterPay\Core\Hooks;
use LaterPay\Core\Event\Event;
use LaterPay\Core\Event\DispatcherInterface;
use LaterPay\Core\Event\SubscriberInterface;
use LaterPay\Core\Logger;

/**
 * LaterPay core class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Dispatcher implements DispatcherInterface
{

    /**
     *
     */
    const DEFAULT_PRIORITY = 10;

    /**
     * @var Dispatcher
     */
    protected static $dispatcher;

    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * Shared events, that could be called from any place
     * @var array
     */
    protected $sharedListeners = array();

    /**
     * @var array
     */
    protected $sorted = array();

    /**
     * @var bool
     */
    protected $debugEnabled = false;

    /**
     * @var array
     */
    protected $debugData = array();

    /**
     * Singleton to get only one event dispatcher
     *
     * @return Dispatcher
     */
    public static function getDispatcher()
    {
        if (null === static::$dispatcher) {
            static::$dispatcher = new static();
        }

        return static::$dispatcher;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string $eventName The name of the event to dispatch.
     * @param Event|array|null $args The event to pass to the event handlers/listeners.
     *
     * @return Event
     */
    public function dispatch($eventName, $args = null)
    {
        if (is_array($args)) {
            $event = new Event($args);
        } elseif ($args instanceof Event) {
            $event = $args;
        } else {
            $event = new Event();
        }

        $event->setName($eventName);

        if (! isset($this->listeners[$eventName])) {
            return $event;
        }

        $arguments = Hooks::applyArgumentsFilters($eventName, $event->getArguments());
        $event->setArguments($arguments);

        $this->doDispatch($this->getListeners($eventName), $event);

        // apply registered in WordPress filters for the event result
        $result = Hooks::applyFilters($eventName, $event->getResult());
        $event->setResult($result);

        if ($event->isEchoEnabled()) {
            echo $event->getFormattedResult();
        }

        $this->setDebugData($eventName, $event->getDebug());
        laterpay_get_logger()->debug($eventName, $event->getDebug());

        if ($event->isAjax()) { // otherwise admin-ajax.php will add extra '0' to each request
            die;
        }

        return $event;
    }

    /**
     * Triggers the listeners of an event.
     *
     * @param callable[] $listeners The event listeners.
     * @param Event $event The event object to pass to the event handlers/listeners.
     *
     * @return void
     */
    protected function doDispatch($listeners, Event $event)
    {
        foreach ($listeners as $listener) {
            try {
                $arguments = $this->getArguments($listener, $event);
                call_user_func_array($listener, $arguments);
            } catch (\Exception $e) {
                laterpay_get_logger()->error(
                    $e->getMessage(),
                    array(
                        'trace' => $e->getTraceAsString(),
                    )
                );
                $event->stopPropagation();
            }

            if ($event->isPropagationStopped()) {
                $event->setPropagationsStoppedBy($listener);
                break;
            }
        }
    }

    /**
     * Processes callback description to get required list of arguments.
     *
     * @param callable|array|object $callback The event listener.
     * @param Event $event The event object.
     * @param array $attributes The context to get attributes.
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function getArguments($callback, Event $event, array $attributes = array())
    {
        $arguments = array();
        if (is_array($callback)) {
            if (is_callable($callback) && ! method_exists($callback[0], $callback[1])) {
                return $arguments;
            } elseif (method_exists($callback[0], $callback[1])) {
                $callbackReflection = new \ReflectionMethod($callback[0], $callback[1]);
            } else {
                throw new \RuntimeException('Callback method  is not found');
            }
        } elseif (is_object($callback)) {
            $callbackReflection = new \ReflectionObject($callback);
            $callbackReflection = $callbackReflection->getMethod('__invoke');
        } else {
            $callbackReflection = new \ReflectionFunction($callback);
        }

        if ($callbackReflection->getNumberOfParameters() > 0) {
            $parameters = $callbackReflection->getParameters();
            foreach ($parameters as $param) {
                if (array_key_exists($param->name, $attributes)) {
                    $arguments[] = $attributes[$param->name];
                } elseif ($param->getClass() && $param->getClass()->isInstance($event)) {
                    $arguments[] = $event;
                } elseif ($param->isDefaultValueAvailable()) {
                    $arguments[] = $param->getDefaultValue();
                } else {
                    $arguments[] = $event;
                }
            }
        }

        return $arguments;
    }

    /**
     * Gets the listeners of a specific event or all listeners.
     *
     * @param string|null $eventName The event name to get listeners or null to get all.
     *
     * @return mixed
     */
    public function getListeners($eventName = null)
    {
        if (null !== $eventName) {
            if (! isset($this->sorted[$eventName])) {
                $this->sortListeners($eventName);
            }

            return $this->sorted[$eventName];
        }

        foreach (array_keys($this->listeners) as $event) {
            if (! isset($this->sorted[$event])) {
                $this->sortListeners($event);
            }
        }

        return array_filter($this->sorted);
    }

    /**
     * Sorts the internal list of listeners for the given event by priority.
     *
     * @param string $event_name The name of the event.
     *
     * @return void
     */
    protected function sortListeners($event_name)
    {
        $this->sorted[$event_name] = array();

        if (isset($this->listeners[$event_name])) {
            krsort($this->listeners[$event_name]);
            // we should make resulted array unique to avoid duplicated calls.
            // php function `array_unique` works wrong and has bugs working with objects/arrays.
            $temp_array = call_user_func_array('array_merge', $this->listeners[$event_name]);
            $result     = array();

            /**
             * @var $temp_array array
             */
            foreach ($temp_array as $callback) {
                if (! in_array($callback, $result, true)) {
                    $result[] = $callback;
                }
            }
            $this->sorted[$event_name] = $result;
        }
    }

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string|null $eventName
     *
     * @return bool
     */
    public function hasListeners($eventName = null)
    {
        return (bool)count($this->getListeners($eventName));
    }

    /**
     * Adds an event subscriber.
     *
     * The subscriber is asked for all the events he is
     * interested in and added as a listener for these events.
     *
     * @param SubscriberInterface $subscriber The subscriber.
     *
     * @return self
     */
    public function addSubscriber(SubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSharedEvents() as $event_name => $params) {
            if (is_string($params)) {
                $this->addSharedListener($event_name, array($subscriber, $params));
            } elseif (is_string($params[0])) {
                $this->addSharedListener($event_name, array($subscriber, $params[0]));
            } else {
                /**
                 * @var $params array
                 */
                foreach ($params as $listener) {
                    $this->addSharedListener($event_name, array($subscriber, $listener[0]));
                }
            }
        }

        foreach ($subscriber->getSubscribedEvents() as $event_name => $params) {
            if (is_string($params)) {
                $this->addListener($event_name, array($subscriber, $params));
            } else {

                /**
                 * @var $params array
                 */
                foreach ($params as $listener) {
                    if (method_exists($subscriber, $listener[0])) {
                        $this->addListener(
                            $event_name,
                            array($subscriber, $listener[0]),
                            isset($listener[1]) ? $listener[1] : static::DEFAULT_PRIORITY
                        );
                    } elseif ($this->getSharedListener($listener[0]) !== null) {
                        $callable = $this->getSharedListener($listener[0]);

                        $this->addListener(
                            $event_name,
                            $callable,
                            isset($listener[1]) ? $listener[1] : static::DEFAULT_PRIORITY
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string $eventName The event name to listen on.
     * @param callable $listener The event listener.
     * @param int $priority The higher this value, the earlier an event
     *                            listener will be triggered in the chain (defaults to static::DEFAULT_PRIORITY)
     *
     * @return self
     */
    public function addListener($eventName, $listener, $priority = self::DEFAULT_PRIORITY)
    {
        Hooks::registerLaterpayAction($eventName);
        $this->listeners[$eventName][$priority][] = $listener;

        unset($this->sorted[$eventName]);

        return $this;
    }

    /**
     * Adds an shared event listener that listens on the specified events.
     *
     * @param string $event_name The event name to listen on.
     * @param callable $listener The event listener.
     *
     * @return void
     */
    public function addSharedListener($event_name, $listener)
    {
        $this->sharedListeners[$event_name] = $listener;
    }

    /**
     * Returns shared event listener.
     *
     * @param string $event_name The event name.
     *
     * @return callable|null
     */
    public function getSharedListener($event_name)
    {
        if (isset($this->sharedListeners[$event_name])) {
            return $this->sharedListeners[$event_name];
        }

        return null;
    }

    /**
     * Removes an event subscriber.
     *
     * @param SubscriberInterface $subscriber The subscriber
     *
     * @return void
     */
    public function removeSubscriber(SubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $event_name => $params) {
            if (is_array($params) && is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->removeListener($event_name, array($subscriber, $listener[0]));
                }
            } else {
                $this->removeListener($event_name, array($subscriber, is_string($params) ? $params : $params[0]));
            }
        }
    }

    /**
     * Removes an event listener from the specified events.
     *
     * @param string $eventName The event name to listen on.
     * @param callable $listener The event listener.
     *
     * @return bool
     */
    public function removeListener($eventName, $listener)
    {
        if (! isset($this->listeners[$eventName])) {
            return false;
        }
        $result = false;

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            $key = array_search($listener, $listeners, true);

            if (false !== $key) {
                unset($this->listeners[$eventName][$priority][$key], $this->sorted[$eventName]);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Enables collecting of the debug information about raised events.
     *
     * @param boolean $debugEnabled
     *
     * @return self
     */
    public function setDebugEnabled($debugEnabled)
    {
        $this->debugEnabled = $debugEnabled;

        return $this;
    }

    /**
     * Returns event's debug information
     *
     * @return array
     */
    public function getDebugData()
    {
        return $this->debugData;
    }

    /**
     * Formats and adds event debug information into collection.
     *
     * @param string $event_name The name of the event.
     * @param array $context Debug information.
     *
     * @return self
     */
    public function setDebugData($event_name, $context)
    {
        if ('laterpay_post_metadata' === $event_name) {
            return $this;
        }

        if ($this->debugEnabled) {
            $listeners         = $this->getListeners($event_name);
            $record            = array(
                'message' => (string)$event_name,
                'context' => $context,
                'extra'   => (array)$listeners,
                'level'   => count($listeners) > 0 ? Logger::DEBUG : Logger::WARNING,
            );
            $this->debugData[] = $record;
        }

        return $this;
    }
}
