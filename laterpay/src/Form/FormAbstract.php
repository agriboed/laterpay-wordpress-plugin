<?php

namespace LaterPay\Form;

/**
 * LaterPay abstract form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
abstract class FormAbstract
{

    /**
     * Form fields
     *
     * @var array
     */
    protected $fields;

    /**
     * Validation errors
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Array of no strict names
     *
     * @var array
     */
    protected $nostrict;

    /**
     * Default filters set
     *
     * @var array
     */
    public static $filters = array(
        // sanitize string value
        'text'       => 'sanitize_text_field',
        // sanitize email
        'email'      => 'sanitize_email',
        // sanitize xml
        'xml'        => 'ent2ncr',
        // sanitize url
        'url'        => 'esc_url',
        // sanitize js
        'js'         => 'esc_js',
        // sanitize sql
        'sql'        => 'esc_sql',
        // convert to int, abs
        'to_int'     => 'absint',
        // convert to string
        'to_string'  => 'strval',
        // delocalize
        'delocalize' => array('LaterPay\Helper\View', 'normalize'),
        // convert to float
        'to_float'   => 'floatval',
        // replace part of value with other
        // params:
        // type    - replace type (str_replace, preg_replace)
        // search  - searched value or pattern
        // replace - replacement
        'replace'    => array('LaterPay\Form\FormAbstract', 'replace'),
        // format number with given decimal places
        'format_num' => 'number_format',
        // strip slashes
        'unslash'    => 'wp_unslash',
    );

    /**
     * Constructor.
     *
     * @param array $data
     *
     * @return void
     */
    final public function __construct(array $data = array())
    {
        // Call init method from child class
        $this->init();

        // set data to form, if specified
        if (! empty($data)) {
            $this->setData($data);
        }
    }

    /**
     * Init form
     *
     * @return void
     */
    abstract protected function init();

    /**
     * Set new field, options for its validation, and filter options (sanitizer).
     *
     * @param       $name
     * @param array $options
     *
     * @return bool field was created or already exists
     */
    public function setField($name, $options = array())
    {
        $fields = $this->getFields();

        // check, if field already exists
        if (isset($fields[$name])) {
            return false;
        }

        // field name
        $data = array();
        // validators
        $data['validators'] = isset($options['validators']) ? $options['validators'] : array();
        // filters (sanitize)
        $data['filters'] = isset($options['filters']) ? $options['filters'] : array();
        // default value
        $data['value'] = isset($options['default_value']) ? $options['default_value'] : null;
        // do not apply filters to null value
        $data['can_be_null'] = isset($options['can_be_null']) ? $options['can_be_null'] : false;

        // name not strict, value searched in data by part of the name (for dynamic params)
        if (! empty($options['not_strict_name'])) {
            $this->setNostrict($name);
        }

        $this->saveFieldData($name, $data);

        return true;
    }

    /**
     * Save data in field.
     *
     * @param $name
     * @param $data
     *
     * @return void
     */
    protected function saveFieldData($name, $data)
    {
        $this->fields[$name] = $data;
    }

    /**
     * Get all fields.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get all filters.
     *
     * @return array
     */
    protected function getFilters()
    {
        return self::$filters;
    }

    /**
     * Get field value.
     *
     * @param $field_name
     *
     * @return mixed
     */
    public function getFieldValue($field_name)
    {
        $fields = $this->getFields();

        if (isset($fields[$field_name])) {
            return $fields[$field_name]['value'];
        }

        return null;
    }

    /**
     * Set field value.
     *
     * @param $field_name
     * @param $value
     *
     * @return void
     */
    protected function setFieldValue($field_name, $value)
    {
        $this->fields[$field_name]['value'] = $value;
    }

    /**
     * Add field name to nostrict array.
     *
     * @param $name
     *
     * @return void
     */
    protected function setNostrict($name)
    {
        if (null === $this->nostrict) {
            $this->nostrict = array();
        }

        $this->nostrict[] = $name;
    }

    /**
     * Check if field value is null and can be null
     *
     * @param $field
     *
     * @return bool
     */
    protected function checkIfFieldCanBeNull($field)
    {
        $fields = $this->getFields();

        if ($fields[$field]['can_be_null']) {
            return true;
        }

        return false;
    }

    /**
     * Add condition to the field validation
     *
     * @param $field
     * @param array $condition
     *
     * @return void
     */
    public function addValidation($field, array $condition = array())
    {
        $fields = $this->getFields();

        if (is_array($condition) && ! empty($condition) && isset($fields[$field])) {
            // condition should be correct
            $fields[$field]['validators'][] = $condition;
        }
    }

    /**
     * Validate data in fields
     *
     * @param $data
     *
     * @return bool is data valid
     */
    public function isValid(array $data = array())
    {
        $this->errors = array();
        // If data passed set data to the form
        if (! empty($data)) {
            $this->setData($data);
        }

        $fields = $this->getFields();

        // validation logic
        if (is_array($fields)) {
            foreach ($fields as $name => $field) {
                $validators = $field['validators'];
                /**
                 * @var array $validators
                 */
                foreach ($validators as $validator_key => $validator_value) {
                    $validator_option = is_int($validator_key) ? $validator_value : $validator_key;
                    $validator_params = is_int($validator_key) ? null : $validator_value;

                    // continue loop if field can be null and has null value
                    if ($this->checkIfFieldCanBeNull($name) && $this->getFieldValue($name) === null) {
                        continue;
                    }

                    $is_valid = $this->validateValue($field['value'], $validator_option, $validator_params);
                    if (! $is_valid) {
                        // data not valid
                        $this->errors[] = array(
                            'name'      => $name,
                            'value'     => $field['value'],
                            'validator' => $validator_option,
                            'options'   => $validator_params,
                        );
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrors()
    {
        $aux          = $this->errors;
        $this->errors = array();

        return $aux;
    }

    /**
     * Apply filters to form data.
     *
     * @return void
     */
    protected function sanitize()
    {
        $fields = $this->getFields();

        // get all form filters
        if (is_array($fields)) {
            foreach ($fields as $name => $field) {
                $filters = $field['filters'];
                /**
                 * @var array $filters
                 */
                foreach ($filters as $filter_key => $filter_value) {
                    $filter_option = is_int($filter_key) ? $filter_value : $filter_key;
                    $filter_params = is_int($filter_key) ? null : $filter_value;

                    // continue loop if field can be null and has null value
                    if ($this->checkIfFieldCanBeNull($name) && $this->getFieldValue($name) === null) {
                        continue;
                    }

                    $this->setFieldValue(
                        $name,
                        $this->sanitizeValue($this->getFieldValue($name), $filter_option, $filter_params)
                    );
                }
            }
        }
    }

    /**
     * Apply filter to the value.
     *
     * @param $value
     * @param $filter
     * @param null $filter_params
     *
     * @return mixed
     */
    public function sanitizeValue($value, $filter, $filter_params = null)
    {
        // get filters
        $filters = $this->getFilters();

        // sanitize value according to selected filter
        $sanitizer = isset($filters[$filter]) ? $filters[$filter] : '';

        if ($sanitizer && is_callable($sanitizer)) {
            if ($filter_params) {
                if (is_array($filter_params)) {
                    array_unshift($filter_params, $value);
                    $value = call_user_func_array($sanitizer, $filter_params);
                } else {
                    $value = call_user_func($sanitizer, $value, $filter_params);
                }
            } else {
                $value = call_user_func($sanitizer, $value);
            }
        }

        return $value;
    }

    /**
     * Call str_replace with array of options.
     *
     * @param $value
     * @param $options
     *
     * @return mixed
     */
    public static function replace($value, $options)
    {
        if (is_array($options) && isset($options['type']) && is_callable($options['type'])) {
            $value = $options['type']($options['search'], $options['replace'], $value);
        }

        return $value;
    }

    /**
     * Validate value by selected validator and its value optionally.
     *
     * @param $value
     * @param $validator
     * @param null $validatorParams
     *
     * @return bool
     */
    public function validateValue($value, $validator, $validatorParams = null)
    {
        $isValid = false;

        switch ($validator) {
            // compare value with set
            case 'cmp':
                if ($validatorParams && is_array($validatorParams)) {
                    // OR realization, all validators inside validators set used like AND
                    // if at least one set correct then validation passed
                    foreach ($validatorParams as $validators_set) {
                        /**
                         * @var array $validators_set
                         */
                        foreach ($validators_set as $operator => $param) {
                            $isValid = $this->compareValues($operator, $value, $param);
                            // if comparison not valid break the loop and go to the next validation set
                            if (! $isValid) {
                                break;
                            }
                        }

                        // if comparison valid after full validation set check then do not need to check others
                        if ($isValid) {
                            break;
                        }
                    }
                }
                break;

            // check, if value is an int
            case 'is_int':
                $isValid = is_int($value);
                break;

            // check, if value is a string
            case 'is_string':
                $isValid = is_string($value);
                break;

            // check, if value is a float
            case 'is_float':
                $isValid = is_float($value);
                break;

            // check string length
            case 'strlen':
                if ($validatorParams && is_array($validatorParams)) {
                    foreach ($validatorParams as $extra_validator => $validator_data) {
                        // recursively call extra validator
                        $isValid = $this->validateValue(strlen($value), $extra_validator, $validator_data);
                        // break loop if something not valid
                        if (! $isValid) {
                            break;
                        }
                    }
                }
                break;

            // check array values
            case 'array_check':
                if ($validatorParams && is_array($validatorParams)) {
                    foreach ($validatorParams as $extra_validator => $validator_data) {
                        if (is_array($value)) {
                            foreach ($value as $v) {
                                // recursively call extra validator
                                $isValid = $this->validateValue($v, $extra_validator, $validator_data);
                                if (! $isValid) {
                                    break;
                                }
                            }
                        } else {
                            $isValid = false;
                        }

                        if (! $isValid) {
                            break;
                        }
                    }
                }
                break;

            // check, if value is in array
            case 'in_array':
                if ($validatorParams && is_array($validatorParams)) {
                    //convert each element to string that allows to compare in strict mode
                    foreach ($validatorParams as $key => $v) {
                        $validatorParams[$key] = (string)$v;
                    }

                    $isValid = in_array((string)$value, $validatorParams, true);
                }
                break;

            // check if value is an array
            case 'is_array':
                $isValid = is_array($value);
                break;

            case 'match':
                if ($validatorParams && ! is_array($validatorParams)) {
                    $isValid = preg_match($validatorParams, $value);
                }
                break;

            case 'match_url':
                $isValid = preg_match_all(
                    '/[-a-zA-Z0-9@:%_\+.~#?&\/\/=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)?/i',
                    $value
                );
                break;

            case 'depends':
                if ($validatorParams && is_array($validatorParams)) {
                    //get all dependency
                    foreach ($validatorParams as $dependency) {
                        // if dependency match
                        if (! isset($dependency['value'])
                             || $value === $dependency['value']
                             || (is_array($dependency['value'])
                                 && in_array(
                                     $value,
                                     $dependency['value'],
                                     true
                                 ))) {
                            // loop for dependencies conditions and check if all of them is valid
                            foreach ($dependency['conditions'] as $vkey => $vparams) {
                                $extra_validator = is_int($vkey) ? $vparams : $vkey;
                                $validator_data  = is_int($vkey) ? null : $vparams;
                                // recursively call extra validator
                                $isValid = $this->validateValue(
                                    $this->getFieldValue($dependency['field']),
                                    $extra_validator,
                                    $validator_data
                                );
                                // break loop if something not valid
                                if (! $isValid) {
                                    break;
                                }
                            }

                            // dependency matched, break process
                            break;
                        }

                        $isValid = true;
                    }
                }
                break;
            case 'verify_nonce':
                if ($validatorParams) {
                    if (is_array($validatorParams)) {
                        if (isset($validatorParams['action'])) {
                            wp_verify_nonce($value, $validatorParams['action']);
                        }
                    } else {
                        wp_verify_nonce($value);
                    }
                }
                break;
            case 'post_exist':
                $post    = get_post($value);
                $isValid = $post !== null;
                break;
            default:
                // incorrect validator specified, do nothing
                break;
        }

        return $isValid;
    }

    /**
     * Compare two values
     *
     * @param $comparisonOperator
     * @param $firstValue
     * @param $secondValue
     *
     * @return bool
     */
    protected function compareValues($comparisonOperator, $firstValue, $secondValue)
    {
        $result = false;

        switch ($comparisonOperator) {
            // equal ===
            case 'eq':
                $result = ($firstValue === $secondValue);
                break;

            // not equal !==
            case 'ne':
                $result = ($firstValue !== $secondValue);
                break;

            // greater than >
            case 'gt':
                $result = ($firstValue > $secondValue);
                break;

            // greater than or equal >=
            case 'gte':
                $result = ($firstValue >= $secondValue);
                break;

            // less than <
            case 'lt':
                $result = ($firstValue < $secondValue);
                break;

            // less than or equal <=
            case 'lte':
                $result = ($firstValue <= $secondValue);
                break;

            // search if string present in value
            case 'like':
                $result = (strpos($firstValue, $secondValue) !== false);
                break;

            default:
                // incorrect comparison operator, do nothing
                break;
        }

        return $result;
    }

    /**
     * Set data into fields and sanitize it.
     *
     * @param $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $fields = $this->getFields();

        // set data and sanitize it
        if (is_array($data)) {
            foreach ($data as $name => $value) {
                // set only, if name field was created
                if (isset($fields[$name])) {
                    $this->setFieldValue($name, $value);
                    continue;
                } elseif (null !== $this->nostrict && is_array($this->nostrict)) {
                    // if field name is not strict
                    foreach ($this->nostrict as $fieldName) {
                        if (strpos($name, $fieldName) !== false) {
                            $this->setFieldValue($fieldName, $value);
                            break;
                        }
                    }
                }
            }

            // sanitize data, if filters were specified
            $this->sanitize();
        }

        return $this;
    }

    /**
     * Get form values.
     *
     * @param bool $notNull get only not null values
     * @param string $prefix get values with selected prefix
     * @param array $exclude array of names for exclude
     *
     * @return array
     */
    public function getFormValues($notNull = false, $prefix = null, array $exclude = array())
    {
        $fields = $this->getFields();
        $data   = array();

        foreach ($fields as $name => $fieldData) {
            if ($notNull && ($fieldData['value'] === null)) {
                continue;
            }
            if ($prefix && (strpos($name, $prefix) === false)) {
                continue;
            }
            if (is_array($exclude) && in_array($name, $exclude, true)) {
                continue;
            }
            $data[$name] = $fieldData['value'];
        }

        return $data;
    }
}
