<?php


class LexicalAnalyzer
{
    /**
     * @var string
     */
    protected $inputString;

    protected static $terminals = [
        '/^(SELECT)/' => 'SELECT',
        '/^(FROM)/' => 'FROM',
        '/^(WHERE)/' => 'WHERE',
        '/^(NOT)/' => 'NOT',
        '/^(LIMIT)/' => 'LIMIT',
        '/^(ROOT)/' => 'ROOT',
        '/^(CONTAINS)/' => 'CONTAINS',
        '/^(DESC)/' => 'DESC',
        '/^(ASC)/' => 'ASC',
        '/^(\s+)/' => 'SPACE',
        '/^(>)/' => 'OPERATOR_MORE',
        '/^(<)/' => 'OPERATOR_LESS',
        '/^(=)/' => 'OPERATOR_EQUALS',
        '/^(\()/' => 'BRACE_LEFT',
        '/^(\))/' => 'BRACE_RIGHT',

        /*
         *  Element names are case-sensitive
            Element names must start with a letter or underscore
            Element names cannot start with the letters xml (or XML, or Xml, etc)
            Element names can contain letters, digits, hyphens, underscores, and periods
            Element names cannot contain spaces
         */
        '/^(\.[a-zA-Z_][a-zA-Z0-9\-_]*)/' => 'ATRIBUTE',
        '/^([a-zA-Z_][a-zA-Z0-9\-_]*\.[a-zA-Z_][a-zA-Z0-9\-_]*)/' => 'ELEMENT_WITH_ATRIBUTE',
        '/^([a-zA-Z_][a-zA-Z0-9\-_]*)/' => 'ELEMENT',
        '/^(".*?")/' => 'STRING', //everything between the two "
        '/^([+|-]?[0-9]+)/' => 'INTEGER',

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
     * @return array
     *
     * @throws \InvalidQueryException
     */
    protected function match($line, $offset)
    {
        $string = mb_substr($line, $offset);

        foreach (static::$terminals as $pattern => $name) {
            if (preg_match($pattern, $string, $matches)) {
                return [
                    'match' => $matches[0],
                    'token' => $name,
                ];
            }
        }

        throw new InvalidQueryException('Unable to parse: '.mb_substr($this->inputString, $offset));
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        $tokens = [];
        $offset = 0;

        while ($offset < mb_strlen($this->inputString)) {
            $result = $this->match($this->inputString, $offset);

            if ($result['token'] !== 'SPACE') {
                $tokens[] = $result;
            }

            $offset += mb_strlen($result['match']);
        }

        foreach ($tokens as &$token) {
            if ($token['token'] === 'STRING') {
                $token['match'] = str_replace('"', '', $token['match']);
            }
        }
        unset($token); //prevent sideefects when possible when working with the $token variable

        var_dump($tokens);

        return $tokens;
    }
}
