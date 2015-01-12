<?php

namespace App\Model;

/**
 * Class Order
 * @package App\Model
 *
 * @SWG\Model(id="Order")
 */
class Order
{
    /**
     * @var integer
     *
     * @SWG\Property(type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @SWG\Property(type="string")
     */
    public $name;
}
