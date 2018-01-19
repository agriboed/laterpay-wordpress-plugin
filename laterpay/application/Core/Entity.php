<?php

namespace LaterPay\Core;


/**
 * LaterPay core entity.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Entity {

	/**
	 * Object attributes
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Data changes flag (true after set_data|unset_data call)
	 *
	 * @var $_has_dataChange boolean
	 */
	protected $hasDataChanges = false;

	/**
	 * Original data loaded
	 *
	 * @var array
	 */
	protected $originalData;

	/**
	 * Name of object id field
	 *
	 * @var string
	 */
	protected $idFieldName;

	/**
	 * Setter / getter underscore transformation cache
	 *
	 * @var array
	 */
	protected static $underscoreCache = array();

	/**
	 * Object deleted flag
	 *
	 * @var boolean
	 */
	protected $isDeleted = false;

	/**
	 * Map short fields names to their full names
	 *
	 * @var array
	 */
	protected $oldFieldsMap = array();

	/**
	 * Map of fields to sync to other fields upon changing their data
	 */
	protected $syncFieldsMap = array();

	/**
	 * Constructor
	 *
	 * By default is looking for first argument as array and assigns it as object attributes.
	 * This behavior may change in child classes.
	 *
	 */
	public function __construct() {
		$this->initOldFieldsMap();
		if ( $this->oldFieldsMap ) {
			$this->prepareSyncMapForFields();
		}

		$args = func_get_args();
		if ( empty( $args[0] ) ) {
			$args[0] = array();
		}
		$this->data = $args[0];
		$this->addFullNames();

		$this->init();
	}

	/**
	 *
	 * @return void
	 */
	protected function addFullNames() {
		$existing_short_keys = array_intersect( $this->syncFieldsMap, array_keys( $this->data ) );

		if ( ! empty( $existing_short_keys ) ) {
			foreach ( $existing_short_keys as $key ) {
				$fullFieldName                = array_search( $key, $this->syncFieldsMap, true );
				$this->data[ $fullFieldName ] = $this->data[ $key ];
			}
		}
	}

	/**
	 * Initiate mapping the array of object's previously used fields to new fields.
	 * Must be overloaded by descendants to set actual fields map.
	 *
	 * @return void
	 */
	protected function initOldFieldsMap() {
	}

	/**
	 * Called after old fields are initiated. Forms synchronization map to sync old fields and new fields.
	 *
	 * @return Entity
	 */
	protected function prepareSyncMapForFields() {
		$old2New             = $this->oldFieldsMap;
		$new2Old             = array_flip( $this->oldFieldsMap );
		$this->syncFieldsMap = array_merge( $old2New, $new2Old );

		return $this;
	}

	/**
	 * Internal constructor not dependent on parameters. Can be used for object initialization.
	 *
	 */
	protected function init() {
	}

	/**
	 * Set _is_deleted flag value (if $is_deleted parameter is defined) and return current flag value.
	 *
	 * @param boolean $is_deleted
	 *
	 * @return boolean
	 */
	public function isDeleted( $is_deleted = null ) {
		$result = $this->isDeleted;
		if ( null !== $is_deleted ) {
			$this->isDeleted = $is_deleted;
		}

		return $result;
	}

	/**
	 * Get data change status.
	 *
	 * @return boolean
	 */
	public function hasDataChanges() {
		return $this->hasDataChanges;
	}

	/**
	 * Set name of object id field.
	 *
	 * @param string $name
	 *
	 * @return Entity
	 */
	public function setIdFieldName( $name ) {
		$this->idFieldName = $name;

		return $this;
	}

	/**
	 * Get name of object id field.
	 *
	 * @return string
	 */
	public function getIdFieldName() {
		return $this->idFieldName;
	}

	/**
	 * Get object id.
	 *
	 * @return mixed
	 */
	public function getId() {
		if ( $this->getIdFieldName() ) {
			return $this->_get_data( $this->getIdFieldName() );
		}

		return $this->_get_data( 'id' );
	}

	/**
	 * Set object id field value.
	 *
	 * @param mixed $value
	 *
	 * @return Entity
	 */
	public function setId( $value ) {
		if ( $this->getIdFieldName() ) {
			$this->setData( $this->getIdFieldName(), $value );
		} else {
			$this->setData( 'id', $value );
		}

		return $this;
	}

	/**
	 * Add data to the object.
	 *
	 * Retains previous data in the object.
	 *
	 * @param array $arr
	 *
	 * @return Entity
	 */
	public function addData( array $arr ) {
		foreach ( $arr as $index => $value ) {
			$this->setData( $index, $value );
		}

		return $this;
	}

	/**
	 * Overwrite data in the object.
	 *
	 * $key can be string or array.
	 * If $key is string, the attribute value will be overwritten by $value
	 *
	 * If $key is an array, it will overwrite all the data in the object.
	 *
	 * @param string|array $key
	 * @param mixed $value
	 *
	 * @return Entity
	 */
	public function setData( $key, $value = null ) {
		$this->hasDataChanges = true;

		if ( is_array( $key ) ) {
			$this->data = $key;
			$this->addFullNames();
		} else {
			$this->data[ $key ] = $value;

			/**
			 * @var $key string
			 */
			if ( isset( $this->syncFieldsMap[ $key ] ) ) {
				$fullFieldName                = $this->syncFieldsMap[ $key ];
				$this->data[ $fullFieldName ] = $value;
			}
		}

		return $this;
	}

	/**
	 * Unset data from the object.
	 *
	 * $key can be a string only. Array will be ignored.
	 *
	 * @param null|string $key
	 *
	 * @return Entity
	 */
	public function unsetData( $key = null ) {
		$this->hasDataChanges = true;

		if ( null === $key ) {
			$this->data = array();
		} else {
			unset( $this->data[ $key ] );
			if ( isset( $this->syncFieldsMap[ $key ] ) ) {
				$fullFieldName = $this->syncFieldsMap[ $key ];
				unset( $this->data[ $fullFieldName ] );
			}
		}

		return $this;
	}

	/**
	 * Unset old field data from the object.
	 *
	 * $key can be a string only. Array will be ignored.
	 *
	 * @param null|string $key
	 *
	 * @return Entity
	 */
	public function unsetOldData( $key = null ) {
		if ( null === $key ) {
			foreach ( array_keys( $this->syncFieldsMap ) as $k ) {
				unset( $this->data[ $k ] );
			}
		} else {
			unset( $this->data[ $key ] );
		}

		return $this;
	}

	/**
	 * Retrieve data from the object.
	 *
	 * If $key is empty, will return all the data as an array.
	 * Otherwise it will return the value of the attribute specified by $key.
	 *
	 * If $index is specified, it will assume that attribute data is an array
	 * and retrieve the corresponding member.
	 *
	 * @param string $key
	 * @param null|string|int $index
	 *
	 * @return array
	 */
	public function getData( $key = '', $index = null ) {
		if ( $key === '' ) {
			return $this->data;
		}

		$default = null;

		if ( strpos( $key, '/' ) ) {
			$keyArr = explode( '/', $key );
			$data   = $this->data;
			foreach ( $keyArr as $k ) {
				if ( $k === '' ) {
					return $default;
				}
				if ( is_array( $data ) ) {
					if ( ! isset( $data[ $k ] ) ) {
						return $default;
					}
					$data = $data[ $k ];
				} elseif ( $data instanceof \Varien_Object ) {
					$data = $data->get_data( $k );
				} else {
					return $default;
				}
			}

			return $data;
		}

		// legacy functionality for $index
		if ( isset( $this->data[ $key ] ) ) {
			if ( null === $index ) {
				return $this->data[ $key ];
			}

			$value = $this->data[ $key ];
			if ( is_array( $value ) ) {
				// use any existing data, even if it's empty
				if ( isset( $value[ $index ] ) ) {
					return $value[ $index ];
				}

				return null;
			} elseif ( is_string( $value ) ) {
				$arr = explode( "\n", $value );
				if ( isset( $arr[ $index ] ) && ( ! empty( $arr[ $index ] ) || strlen( $arr[ $index ] ) > 0 ) ) {
					$aux = $arr[ $index ];
				} else {
					$aux = null;
				}

				return $aux;
			} elseif ( $value instanceof \Varien_Object ) {
				return $value->get_data( $index );
			}

			return $default;
		}

		return $default;
	}

	/**
	 * Get value from _data array without parse key.
	 *
	 * @param   string $key
	 *
	 * @return  mixed
	 */
	protected function _get_data( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			$aux = $this->data[ $key ];
		} else {
			$aux = null;
		}

		return $aux;
	}

	/**
	 * Set object data by calling setter method.
	 *
	 * @param string $key
	 * @param mixed $args
	 *
	 * @return self
	 */
	public function set_data_using_method( $key, array $args = array() ) {
		$method = 'set' . $this->camelize( $key );
		$this->$method( $args );

		return $this;
	}

	/**
	 * Get object data by key by calling getter method.
	 *
	 * @param string $key
	 * @param mixed $args
	 *
	 * @return mixed
	 */
	public function get_data_using_method( $key, $args = null ) {
		$method = 'get' . $this->camelize( $key );

		return $this->$method( $args );
	}

	/**
	 * Get data or set default value, if value is not available.
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get_data_set_default( $key, $default ) {
		if ( ! isset( $this->data[ $key ] ) ) {
			$this->data[ $key ] = $default;
		}

		return $this->data[ $key ];
	}

	/**
	 * Check, if there's any data in the object, if $key is empty.
	 * Otherwise check, if the specified attribute is set.
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has_data( $key = '' ) {
		if ( empty( $key ) || ! is_string( $key ) ) {
			return ! empty( $this->data );
		}

		return array_key_exists( $key, $this->data );
	}

	/**
	 * Convert object attributes to array.
	 *
	 * @param array $arrAttributes array of required attributes
	 *
	 * @return array
	 */
	public function to_array( array $arrAttributes = array() ) {
		if ( empty( $arrAttributes ) ) {
			return $this->data;
		}

		$arrRes = array();
		foreach ( $arrAttributes as $attribute ) {
			if ( isset( $this->data[ $attribute ] ) ) {
				$arrRes[ $attribute ] = $this->data[ $attribute ];
			} else {
				$arrRes[ $attribute ] = null;
			}
		}

		return $arrRes;
	}

	/**
	 * Set required array elements.
	 *
	 * @param array $arr
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function _prepare_array( &$arr, array $elements = array() ) {
		foreach ( $elements as $element ) {
			if ( ! isset( $arr[ $element ] ) ) {
				$arr[ $element ] = null;
			}
		}

		return $arr;
	}

	/**
	 * Convert object attributes to XML.
	 *
	 * @param array $arrAttributes array of required attributes
	 * @param string $rootName name of the root element
	 * @param boolean $addOpenTag
	 * @param boolean $addCdata
	 *
	 * @return string
	 */
	public function to_xml( array $arrAttributes = array(), $rootName = 'item', $addOpenTag = false, $addCdata = true ) {
		$xml = '';

		if ( $addOpenTag ) {
			$xml .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		}

		if ( ! empty( $rootName ) ) {
			$xml .= '<' . $rootName . '>' . "\n";
		}

		$xmlModel = new \Varien_Simplexml_Element( '<node></node>' );

		$arrData = $this->to_array( $arrAttributes );
		foreach ( $arrData as $fieldName => $fieldValue ) {
			if ( $addCdata === true ) {
				$fieldValue = "<! [CDATA[$fieldValue]]>";
			} else {
				$fieldValue = $xmlModel->xmlentities( $fieldValue );
			}

			$xml .= "<$fieldName>$fieldValue</$fieldName>" . "\n";
		}

		if ( ! empty( $rootName ) ) {
			$xml .= '</' . $rootName . '>' . "\n";
		}

		return $xml;
	}

	/**
	 * Convert object attributes to JSON.
	 *
	 * @param array $arrAttributes array of required attributes
	 *
	 * @return string
	 */
	public function to_json( array $arrAttributes = array() ) {
		$arrData = $this->to_array( $arrAttributes );

		return wp_json_encode( $arrData );
	}

	/**
	 * Public wrapper for __to_string.
	 *
	 * Uses $format as an template and substitute {{key}} for attributes
	 *
	 * @param string $format
	 *
	 * @return string
	 */
	public function to_string( $format = '' ) {
		if ( empty( $format ) ) {
			$str = implode( ', ', $this->getData() );
		} else {
			preg_match_all( '/\{\{([a-z0-9_]+)\}\}/is', $format, $matches );
			foreach ( $matches[1] as $var ) {
				$format = str_replace( '{{' . $var . '}}', $this->getData( $var ), $format );
			}
			$str = $format;
		}

		return $str;
	}

	/**
	 * Get / set attribute wrapper.
	 *
	 * @param string $method
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		switch ( substr( $method, 0, 3 ) ) {
			case 'get':
				$key = $this->_underscore( substr( $method, 3 ) );
				if ( isset( $args[0] ) ) {
					$aux = $args[0];
				} else {
					$aux = null;
				}
				$data = $this->getData( $key, $aux );

				return $data;

			case 'set':
				$key = $this->_underscore( substr( $method, 3 ) );
				if ( isset( $args[0] ) ) {
					$aux = $args[0];
				} else {
					$aux = null;
				}
				$data = $this->setData( $key, $aux );

				return $data;

			case 'uns':
				$key = $this->_underscore( substr( $method, 3 ) );

				return $this->unsetData( $key );

			case 'has':
				$key = $this->_underscore( substr( $method, 3 ) );

				return isset( $this->data[ $key ] );
		}

		throw new \Varien_Exception(
			'Invalid method ' . get_class( $this ) . '::' . $method
		);
	}

	/**
	 * Attribute getter (deprecated).
	 *
	 * @param string $var
	 *
	 * @return mixed
	 */
	public function __get( $var ) {
		$var = $this->_underscore( $var );

		return $this->getData( $var );
	}

	/**
	 * Attribute setter (deprecated).
	 *
	 * @param string $var
	 *
	 * @param mixed $value
	 */
	public function __set( $var, $value ) {
		$var = $this->_underscore( $var );

		$this->setData( $var, $value );
	}

	/**
	 * Check, if the object is empty.
	 *
	 * @return boolean
	 */
	public function is_empty() {
		if ( empty( $this->data ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert field names for setters and getters.
	 *
	 * $this->setMyField($value) === $this->set_data('my_field', $value)
	 * Uses cache to eliminate unnecessary preg_replace
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function _underscore( $name ) {
		if ( isset( self::$underscoreCache[ $name ] ) ) {
			return self::$underscoreCache[ $name ];
		}

		$result = strtolower( preg_replace( '/(.)([A-Z])/', '$1_$2', $name ) );

		self::$underscoreCache[ $name ] = $result;

		return $result;
	}

	/**
	 * Convert a string to camelCase.
	 *
	 * @param string $name a string to convert to camelCase
	 *
	 * @return string the string in camelCase
	 */
	protected function camelize( $name ) {
		return ucwords( $name, '' );
	}

	/**
	 * Serialize object attributes.
	 *
	 * @param array $attributes
	 * @param string $valueSeparator
	 * @param string $fieldSeparator
	 * @param string $quote
	 *
	 * @return string $serialized_object_attributes
	 */
	public function serialize( array $attributes = array(), $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"' ) {
		$data = array();

		if ( empty( $attributes ) ) {
			$attributes = array_keys( $this->data );
		}

		foreach ( $this->data as $key => $value ) {
			if ( in_array( $key, $attributes, true ) ) {
				$data[] = $key . $valueSeparator . $quote . $value . $quote;
			}
		}

		// convert array to string
		return implode( $fieldSeparator, $data );
	}

	/**
	 * Get object's loaded data (original data).
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOriginalData( $key = null ) {
		if ( null === $key ) {
			return $this->originalData;
		}

		if ( isset( $this->originalData[ $key ] ) ) {
			$aux = $this->originalData[ $key ];
		} else {
			$aux = null;
		}

		return $aux;
	}

	/**
	 * Initialize object's original data.
	 *
	 * @param string $key
	 * @param mixed $data
	 *
	 * @return self
	 */
	public function setOriginalData( $key = null, $data = null ) {
		if ( null === $key ) {
			$this->originalData = $this->data;
		} else {
			$this->originalData[ $key ] = $data;
		}

		return $this;
	}

	/**
	 * Compare object data with original data.
	 *
	 * @param string $field
	 *
	 * @return boolean
	 */
	public function dataHasChangedFor( $field ) {
		$newData  = $this->getData( $field );
		$origData = $this->getOriginalData( $field );

		return $newData !== $origData;
	}

	/**
	 * Clear data changes status.
	 *
	 * @param boolean $value
	 *
	 * @return self
	 */
	public function setDataChanges( $value ) {
		$this->hasDataChanges = (bool) $value;

		return $this;
	}

	/**
	 * Render object data as string in debug mode.
	 *
	 * @param mixed $data
	 * @param array $objects
	 *
	 * @return string|array
	 */
	public function debug( $data = null, &$objects = array() ) {
		if ( null === $data ) {
			$hash = spl_object_hash( $this );

			if ( ! empty( $objects[ $hash ] ) ) {
				return '*** RECURSION ***';
			}

			$objects[ $hash ] = true;
			$data             = $this->getData();
		}

		$debug = array();
		foreach ( $data as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$debug[ $key ] = $value;
			} elseif ( is_array( $value ) ) {
				$debug[ $key ] = $this->debug( $value, $objects );
			} elseif ( $value instanceof \Varien_Object ) {
				$debug[ $key . ' (' . get_class( $value ) . ')' ] = $value->debug( null, $objects );
			}
		}

		return $debug;
	}

	/**
	 * Implementation of ArrayAccess::offsetSet().
	 *
	 * @link http://www.php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		$this->data[ $offset ] = $value;
	}

	/**
	 * Implementation of ArrayAccess::offsetExists().
	 *
	 * @link http://www.php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param string $offset
	 *
	 * @return boolean
	 */
	public function offsetExists( $offset ) {
		return isset( $this->data[ $offset ] );
	}

	/**
	 * Implementation of ArrayAccess::offsetUnset().
	 *
	 * @link http://www.php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	/**
	 * Implementation of ArrayAccess::offsetGet().
	 *
	 * @link http://www.php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param string $offset
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( isset( $this->data[ $offset ] ) ) {
			$aux = $this->data[ $offset ];
		} else {
			$aux = null;
		}

		return $aux;
	}

	/**
	 * @param string $field
	 *
	 * @return boolean
	 */
	public function is_dirty( $field = null ) {
		if ( empty( $this->_dirty ) ) {
			return false;
		}

		if ( null === $field ) {
			return true;
		}

		return isset( $this->_dirty[ $field ] );
	}

	/**
	 * Flag a field as dirty.
	 *
	 * @param string $field
	 * @param boolean $flag
	 *
	 * @return Entity
	 */
	public function flagDirty( $field, $flag = true ) {
		if ( null === $field ) {
			foreach ( array_keys( $this->getData() ) as $f ) {
				$this->flagDirty( $f, $flag );
			}
		} else {
			if ( $flag ) {
				$this->_dirty[ $field ] = true;
			} else {
				unset( $this->_dirty[ $field ] );
			}
		}

		return $this;
	}
}
