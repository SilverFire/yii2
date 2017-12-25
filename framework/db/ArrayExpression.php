<?php

namespace yii\db;

/**
 * Class ArrayExpression represents an array SQL expression.
 *
 * Expressions of this type can be used for example in conditions, like:
 *
 * ```php
 * $query->andWhere(['@>', 'items', new ArrayExpression([1, 2, 3], 'integer')])
 * ```
 *
 * which, depending on DBMS, will result in a well-prepared condition. For example, in
 * PostgreSQL it will be compiled to `WHERE "items" @> ARRAY[1, 2, 3]::integer[]`.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0.14
 */
class ArrayExpression implements ExpressionInterface
{
    /**
     * @var null|string the type of the array elements. Defaults to `null` which means the type is
     * not explicitly specified.
     *
     * Note that in case when type is not specified explicitly and DBMS can not guess it from the context,
     * SQL error will be raised.
     */
    protected $type;
    /**
     * @var array|QueryInterface|mixed the array content. Either represented as an array of values or a [[Query]] that
     * returns these values. A single value will be considered as an array containing one element.
     */
    protected $value;
    /**
     * @var int
     */
    private $dimension;

    /**
     * ArrayExpression constructor.
     *
     * @param array|QueryInterface|mixed $value the array content. Either represented as an array of values or a Query that
     * returns these values. A single value will be considered as an array containing one element.
     * @param string|null $type the type of the array elements. Defaults to `null` which means the type is
     * not explicitly specified. In case when type is not specified explicitly and DBMS can not guess it from the context,
     * SQL error will be raised.
     * @param int $dimension
     */
    public function __construct($value, $type = null, $dimension = 1)
    {
        $this->value = $value;
        $this->type = $type;
        $this->dimension = $dimension;
    }

    /**
     * @return null|string
     * @see type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array|mixed|QueryInterface
     * @see value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int
     * @see dimensions
     */
    public function getDimension()
    {
        return $this->dimension;
    }
}
