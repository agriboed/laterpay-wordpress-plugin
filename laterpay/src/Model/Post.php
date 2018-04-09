<?php

namespace LaterPay\Model;

use LaterPay\Helper\Pricing;

class Post
{
    const OPTION = 'laterpay_post';

    /**
     * @var string
     */
    protected $businessModel;

    protected $data;

    /**
     * Post constructor.
     */
    public function __construct()
    {
        $this->data          = get_option(static::OPTION, array());
        $this->businessModel = get_option('laterpay_business_model');
    }

    /**
     *
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param int $postID
     *
     * @return array
     */
    public function getPrice($postID)
    {
        if (empty($this->data[$this->businessModel][Pricing::TYPE_INDIVIDUAL_CONTRIBUTION][$postID])) {
            return array();
        }

        return $this->data[$this->businessModel][Pricing::TYPE_INDIVIDUAL_CONTRIBUTION][$postID];
    }

    public function updatePrice($postID, $price, $type)
    {
        $this->data[$this->businessModel][$type][$postID] = (array)$price;

        $this->flush();
    }

    /**
     *
     */
    protected function flush()
    {
        update_option(static::OPTION, $this->data);
    }
}
