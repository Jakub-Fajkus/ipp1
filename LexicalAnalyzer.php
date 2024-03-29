<?php

/**
 * Class LexicalAnalyzer
 *
 * Parses the query string into an array of tokens.
 */
class LexicalAnalyzer
{
    /**
     * @var string
     */
    protected $inputString;

    /**
     * @var array terminals ordered from the specific ones to the general ones
     */
    protected static $terminals = [
        '/^(SELECT)/'                                             => Token::TOKEN_SELECT,
        '/^(FROM)/'                                               => Token::TOKEN_FROM,
        '/^(WHERE)/'                                              => Token::TOKEN_WHERE,
        '/^(NOT)/'                                                => Token::TOKEN_NOT,
        '/^(LIMIT)/'                                              => Token::TOKEN_LIMIT,
        '/^(ROOT)/'                                               => Token::TOKEN_ROOT,
        '/^(CONTAINS)/'                                           => Token::TOKEN_CONTAINS,
        '/^(DESC)/'                                               => Token::TOKEN_DESC,
        '/^(ASC)/'                                                => Token::TOKEN_ASC,
        '/^(ORDER\s+BY)/'                                         => Token::TOKEN_ORDER_BY,
        '/^(\s+)/'                                                => Token::TOKEN_SPACE,
        '/^(>)/'                                                  => Token::TOKEN_OPERATOR_MORE,
        '/^(<)/'                                                  => Token::TOKEN_OPERATOR_LESS,
        '/^(=)/'                                                  => Token::TOKEN_OPERATOR_EQUALS,
        /*
         * Naming conventions - NOT IMPLEMENTED!
         *
         *  Element names are case-sensitive
         *  Element names must start with a letter or underscore
         *  Element names cannot start with the letters xml (or XML, or Xml, etc)
         *  Element names can contain letters, digits, hyphens, underscores, and periods
         *  Element names cannot contain spaces
         */
        '/^(\.[a-zA-Z_][a-zA-Z0-9\-_]*)/'                         => Token::TOKEN_ATTRIBUTE, //todo: valid xml name
        '/^([a-zA-Z_][a-zA-Z0-9\-_]*\.[a-zA-Z_][a-zA-Z0-9\-_]*)/' => Token::TOKEN_ELEMENT_WITH_ATTRIBUTE, //todo: valid xml name
        '/^([a-zA-Z_][a-zA-Z0-9\-_]*)/'                           => Token::TOKEN_ELEMENT,//todo: valid xml name
        '/^(".*?")/'                                              => Token::TOKEN_STRING, //everything between the two "
        '/^(\'.*?\')/'                                            => Token::TOKEN_STRING, //everything between the two '
        '/^([+|-]?[0-9]+)/'                                       => Token::TOKEN_INTEGER,
    ];

    /**
     * LexicalAnalyzer constructor.
     *
     * @param string $inputString
     */
    public function __construct($inputString)
    {
        $this->inputString = $inputString;
    }

    /**
     * @param $line
     * @param $offset
     *
     * @return Token
     *
     * @throws \InvalidQueryException
     */
    protected function match($line, $offset)
    {
        $string = mb_substr($line, $offset);

        foreach (static::$terminals as $pattern => $name) {
            if (preg_match($pattern, $string, $matches)) {
                return new Token($name, $matches[0]);
            }
        }

        throw new InvalidQueryException('Unable to parse: '.mb_substr($this->inputString, $offset));
    }

    /**
     * @return array
     *
     * @throws \InvalidQueryException
     */
    public function getTokens()
    {
        /** @var Token[] $tokens */
        $tokens = [];
        $offset = 0;

        while ($offset < mb_strlen($this->inputString)) {
            $token = $this->match($this->inputString, $offset);

            if ($token->getType() !== Token::TOKEN_SPACE) {
                $tokens[] = $token;
            }

            $offset += mb_strlen($token->getValue());

            if ($token->getType() === Token::TOKEN_INTEGER) {
                //get the integer value
                $token->setValue((int)$token->getValue());
            }

            if ($token->getType() === Token::TOKEN_STRING) {
                //replace the " from the string
                $token->setValue(str_replace('"', '', $token->getValue()));
                $token->setValue(str_replace('\'', '', $token->getValue()));
            }
        }

        return $tokens;
    }
}
