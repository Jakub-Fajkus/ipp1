<?php


class Token
{
    const TOKEN_SELECT = 'SELECT';
    const TOKEN_WHERE = 'WHERE';
    const TOKEN_FROM = 'FROM';
    const TOKEN_NOT = 'NOT';
    const TOKEN_LIMIT = 'LIMIT';
    const TOKEN_ROOT = 'ROOT';
    const TOKEN_CONTAINS = 'CONTAINS';
    const TOKEN_ORDER_BY = 'ORDER_BY';
    const TOKEN_DESC = 'DESC';
    const TOKEN_ASC = 'ASC';
    const TOKEN_SPACE = 'SPACE';
    const TOKEN_OPERATOR_MORE = 'OPERATOR_MORE';
    const TOKEN_OPERATOR_LESS = 'OPERATOR_LESS';
    const TOKEN_OPERATOR_EQUALS = 'OPERATOR_EQUALS';
    const TOKEN_BRACE_LEFT = 'BRACE_LEFT';
    const TOKEN_BRACE_RIGHT = 'BRACE_RIGHT';
    const TOKEN_ATTRIBUTE = 'ATTRIBUTE';
    const TOKEN_ELEMENT_WITH_ATTRIBUTE = 'ELEMENT_WITH_ATTRIBUTE';
    const TOKEN_ELEMENT = 'ELEMENT';
    const TOKEN_INTEGER = 'INTEGER';
    const TOKEN_STRING = 'STRING';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Token constructor.
     *
     * @param string $type
     * @param mixed $value
     */
    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function isElementOrAttribute()
    {
        return $this->type === self::TOKEN_ELEMENT ||
            $this->type === self::TOKEN_ELEMENT_WITH_ATTRIBUTE ||
            $this->type === self::TOKEN_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isConditionOperator()
    {
        return  $this->isArithmeticalOperator() ||
            $this->type === self::TOKEN_CONTAINS;
    }

    /**
     * @return bool
     */
    public function isArithmeticalOperator()
    {
        return $this->type === self::TOKEN_OPERATOR_EQUALS ||
            $this->type === self::TOKEN_OPERATOR_LESS ||
            $this->type === self::TOKEN_OPERATOR_MORE;
    }

    /**
     * @return bool
     */
    public function isLiteral()
    {
        return $this->type === self::TOKEN_STRING || $this->type === self::TOKEN_INTEGER;
    }
}