<?php


class XMLParser
{
    /**
     * @var SimpleXMLElement
     */
    protected $iterator;

    /**
     * @var Query
     */
    protected $query;

    /**
     * XMLParser constructor.
     *
     * @param $fileName
     */
    public function __construct($xmlString, $query)
    {
        if (!empty($xmlString)) {
            try {
                $this->iterator = @new SimpleXMLElement($xmlString, 0);
            } catch (\Exception $exception) {
                throw new InvalidInputFileFormatException('Could not parse input file: ' . $xmlString . '. More info: '. $exception->getMessage());
            }
        }

        $this->query = $query;

        libxml_use_internal_errors(); //supress warnings
    }

    /**
     * kazde volani children vrati iterator pro zanorene elementy.
     *
     * postup hledani:
     * 1. iterace hned po rewind -> hledame v rootu
     * 2. iterace root->getChildren -> hledani v zanoreni
     *
     * @param Closure           $decisionMaker Closure which is used to determine whether the element is the one which we are looking for or not
     * @param SimpleXMLElement $iterator
     *
     * @return SimpleXMLElement[]
     */
    public function findFromElements(Closure $decisionMaker, SimpleXMLElement $iterator = null, $checkRoot = true, $goDeeper = true)
    {
        if ($iterator === null) {
            $iterator = $this->iterator; //root iterator
        }

        $nameRoot = $iterator->getName();

        //check if we are looking for the root element
        $attributes = $this->getAttributes($iterator);
        if ($checkRoot && $decisionMaker($iterator, $attributes)) {
            return [$iterator];
        }

        $returnElements = [];

        /** @var SimpleXMLElement $child */
        foreach ($iterator->children() as $child) {
            $childName = $child->getName();
            if ($child === null) {
                break;
            }
//            echo 'Element: ' . $child->getName() . ' children count:  '.(int)count($child->children()) . PHP_EOL;

            $attributes = $this->getAttributes($child);
            if ($decisionMaker($child, $attributes)) {
                $returnElements[] = $child;
            } elseif ($goDeeper) {
                $foundElements = $this->findFromElements($decisionMaker, $child);
                $returnElements = array_merge($returnElements, $foundElements);
            }
        }

        return $returnElements;
    }

    public function findSelectElements(SimpleXMLElement $fromElement)
    {
        //dva pripady:
        //1. element v SELECT je shodny s elementem v WHERE -> hledame vsehcny elementy, ktere maji tuto vlasnost(hodnotu atributu nebo elementu samotneho)
        //2. element ve WHERE je podlementem elementu v SELECT - hledame v podlelementech

        $selectElement = $this->query->getSelectElement();
        if ($selectElement->getType() !== Token::TOKEN_ELEMENT) {
            throw new InvalidQueryException('Invalid select element');
        }

        $selectElementName = $selectElement->getValue();
        $strategy = $this->getStrategyForQuery();


        $decisionMaker = function (SimpleXMLElement $rootElement, $attributes) use ($selectElementName, $strategy) {
            return $strategy->meetsCondition($rootElement);
        };

        $this->findFromElements($decisionMaker, $fromElement, true, false);
        $foundElements = $strategy->getSelectedElements();

        return $foundElements;
    }

    /**
     * Get attribtes from the element.
     *
     * @param SimpleXMLElement $element
     *
     * @return array
     */
    public function getAttributes(SimpleXMLElement $element)
    {
        $attributes = [];

        foreach ($element->attributes() as $name => $value) {
            $attributes[$name] = (string) $value;
        }

        return $attributes;
    }

    /**
     * @return BaseConditionStrategy
     *
     * @throws InvalidQueryException
     */
    protected function getStrategyForQuery()
    {
        $strategyParser = new self(null, $this->query);

        if ($this->query->getConditionLeft() === null) {
            return new ElementConditionStrategy($this->query, $strategyParser);
        }

        if ($this->query->getConditionLeft()->getType() === Token::TOKEN_ELEMENT) {
            return new ElementConditionStrategy($this->query, $strategyParser);
        } elseif ($this->query->getConditionLeft()->getType() === Token::TOKEN_ATTRIBUTE) {
            return new AttributeConditionStrategy($this->query, $strategyParser);
        } elseif ($this->query->getConditionLeft()->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            return new ElementWithAttributeStrategy($this->query, $strategyParser);
        } else {
            throw new InvalidQueryException('The element in condition is not valid');
        }
    }

    /**
     * @return SimpleXMLElement
     */
    public function getIterator()
    {
        return $this->iterator;
    }
}
