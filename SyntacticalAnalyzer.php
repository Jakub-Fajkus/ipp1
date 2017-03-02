<?php


class SyntacticalAnalyzer
{
    /**
     * @var Token[]
     */
    protected $tokens = [];

    /**
     * @var Query
     */
    protected $query;

    /**
     * SyntacticalAnalyzer constructor.
     *
     * @param array $tokens
     */
    public function __construct(array $tokens)
    {
        //reset the inner pointer to the first element
        $this->tokens = $tokens;
        reset($this->tokens);

        $this->query = new Query();
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    public function analyze()
    {
        return $this->query();
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return bool
     *
     * @throws \InvalidQueryException
     */
    protected function query()
    {
        //<QUERY> --> SELECT element FROM <FROM-ELM> <WHERE-CLAUSE> <ORDER-CLAUSE> <LIMITn>
        $token = $this->getCurrentToken(); //do not call getNextToken!

        if ($token === false) {
            return false;
        }

        if ($token->getType() === Token::TOKEN_SELECT) {
            $token = $this->getNextToken();
            if ($token->getType() === Token::TOKEN_ELEMENT) {
                $this->query->setSelectElement($token);

                $token = $this->getNextToken();
                if ($token->getType() === Token::TOKEN_FROM) {
                    if ($this->fromElm()) {
                        if ($this->whereClause() || $this->orderClause() || $this->limitN()) {
                                return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     *
     * @throws \InvalidQueryException
     */
    protected function limitN()
    {
        //<LIMITn> --> empty
        $token = $this->getNextToken(true);
        if ($token === false) {
            return true;
        //<LIMITn> --> LIMIT number
        } elseif ($token->getType() === Token::TOKEN_LIMIT) {
            $token = $this->getNextToken();
            if ($token->getType() === Token::TOKEN_INTEGER) {
                $this->query->setLimit($token);

                $token = $this->getNextToken(true);
                //expecting end of the input
                return $token === false;
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function fromElm()
    {
        //<FROM-ELM> --> empty - end of the query
        $token = $this->getNextToken(true);
        if ($token === false) {
            return true;
        //<FROM-ELM> --> ROOT
        } elseif ($token->getType() === Token::TOKEN_ROOT) {
            $this->query->setFromElement($token);

            return true;
        } else {
            $this->returnToken();
        }

        //<FROM-ELM> --> <ELEMENT-OR-ATTRIBUTE>
        if ($this->elementOrAttribute()) {
            //the current token was accepted by the rule elementOrAttribute()
            $this->query->setFromElement($this->getCurrentToken());

            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function whereClause()
    {
        //<WHERE-CLAUSE> --> empty
        $token = $this->getNextToken(true);
        if ($token === false) {
            return true;
        //<WHERE-CLAUSE> --> WHERE <CONDITION>
        } elseif ($token->getType() === Token::TOKEN_WHERE) {
            return $this->condition();
        } else {
            $this->returnToken();
            return false;
        }
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function condition()
    {
        //<CONDITION> --> NOT <CONDITION>
        $token = $this->getNextToken();
        if ($token->getType() === Token::TOKEN_NOT) {
            $this->query->negateCondition();

            if ($this->condition()) {
                return true;
            } else {
                return false;
            }
        } else {
            $this->returnToken();
            //return false; //do not return false.. the other rule would not be used at all
        }

        //<CONDITION> --> <ELEMENT-OR-ATTRIBUTE> <RELATION-OPERATOR> <LITERAL>
        if ($this->elementOrAttribute()) {
            //the current token was accepted by the rule elementOrAttribute()
            $this->query->setConditionLeft($this->getCurrentToken());

            if ($this->relationOperator()) {
                //the current token was accepted by the rule conditionOperator()
                $this->query->setConditionOperator($this->getCurrentToken());
                if ($this->literal()) {
                    //the current token was accepted by the rule literal()
                    $this->query->setConditionRight($this->getCurrentToken());

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function literal()
    {
        $token = $this->getNextToken();

        if ($token->getType() === Token::TOKEN_STRING) {
            return true;
        } elseif ($token->getType() == Token::TOKEN_INTEGER) {
            return true;
        } else {
            $this->returnToken();

            return false;
        }
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function relationOperator()
    {
        $token = $this->getNextToken();

        if ($token->getType() === Token::TOKEN_CONTAINS) {
            return true;
        } elseif ($token->getType() === Token::TOKEN_OPERATOR_EQUALS) {
            return true;
        } elseif ($token->getType() === Token::TOKEN_OPERATOR_MORE) {
            return true;
        } elseif ($token->getType() === Token::TOKEN_OPERATOR_LESS) {
            return true;
        } else {
            $this->returnToken();

            return false;
        }
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function elementOrAttribute()
    {
        $token = $this->getNextToken();

        if ($token->getType() === Token::TOKEN_ELEMENT) {
            return true;
        } elseif ($token->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            return true;
        } elseif ($token->getType() === Token::TOKEN_ATTRIBUTE) {
            return true;
        } else {
            $this->returnToken();

            return false;
        }
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function orderClause()
    {
        //<ORDER-CLAUSE> --> ORDER BY <ELEMENT-OR-ATTRIBUTE> <ORDERING>
        $token = $this->getNextToken(true);

        if ($token === false) {
            return true;
        }

        if ($token->getType() === Token::TOKEN_ORDER_BY) {
            if ($this->elementOrAttribute()) {
                //the current token was accepted by the rule elementOrAttribute()
                $this->query->setOrderElement($this->getCurrentToken());
                if ($this->ordering()) {
                    return true;
                }
            } else {
                $this->returnToken();

                return false;
            }
        }

        $this->returnToken();

        return false;
    }

    /**
     * @return bool
     * @throws \InvalidQueryException
     */
    protected function ordering()
    {
        $token = $this->getNextToken();

        //<ORDERING> --> ASC
        if ($token->getType() === Token::TOKEN_ASC) {
            $this->query->setOrdering($token);
            return true;
        //<ORDERING> --> DESC
        } elseif ($token->getType() === Token::TOKEN_DESC) {
            $this->query->setOrdering($token);
            return true;
        } else {
            $this->returnToken();

            return false;
        }
    }

    /**
     * @param bool $expectingEnd
     *
     * @return Token|false
     *
     * @throws InvalidQueryException
     */
    protected function getNextToken($expectingEnd = false)
    {
        $token = next($this->tokens);

        if ($token === false && !$expectingEnd) {
            throw new InvalidQueryException('Unexpected end of the query');
        }

        return $token;
    }

    /**
     * @return Token|false
     */
    protected function getCurrentToken()
    {
        return current($this->tokens);
    }

    /**
     * @return mixed
     */
    protected function returnToken()
    {
        return prev($this->tokens);
    }
}
