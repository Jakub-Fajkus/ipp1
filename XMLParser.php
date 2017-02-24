<?php


class XMLParser
{
    /**
     * @var SimpleXMLIterator
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
    public function __construct($fileName, $query)
    {
        $this->iterator = @new SimpleXMLIterator($fileName, 0, true);
        $this->iterator->rewind();
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
     * @param Closure $decisionMaker Closure which is used to determine whether the element is the one which we are looking for or not
     * @param SimpleXMLIterator $iterator
     *
     * @return SimpleXMLIterator[]
     */
    public function findFromElements(Closure $decisionMaker, SimpleXMLIterator $iterator = null, $checkRoot = true, $goDeeper = true)
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

        foreach ($iterator as $rootElement) {
            if ($rootElement === null) {
                break;
            }

            $attributes = $this->getAttributes($rootElement);
            if ($decisionMaker($rootElement, $attributes)) {
                $returnElements[] = $rootElement;
            } elseif ($goDeeper) {
                $foundElements = $this->findFromElements($decisionMaker, $rootElement);
                $returnElements = array_merge($returnElements, $foundElements);
            }
        }

        return $returnElements;
    }
    
    public function findSelectElements(SimpleXMLIterator $fromElement)
    {
        //dva pripady:
        //1. element v SELECT je shodny s elementem v WHERE -> hledame vsehcny elementy, ktere maji tuto vlasnost(hodnotu atributu nebo elementu samotneho)
        //2. element ve WHERE je podlementem elementu v SELECT - hledame v podlelementech

        $selectElement = $this->query->getSelectElement();
        if ($selectElement->getType() !== Token::TOKEN_ELEMENT) {
            throw new InvalidQueryException('Invalid select element');
        }

        $filteredElements = [];
        $selectElementName = $selectElement->getValue();
        $strategy = $this->getStrategyForQuery();

        //the element in where is identical to the element in the select
        if ($this->query->getSelectElement()->getValue() === $this->query->getConditionLeft()->getValue()) {
            //go through the elements and look for select element. when it is found check ensure the condition is met

            $decisionMaker = function (SimpleXMLIterator $rootElement, $attributes) use ($selectElementName, $strategy) {
                return $strategy->meetsCondition($rootElement);
            };

            $els = $this->findFromElements($decisionMaker, $fromElement, true);
//            foreach ($elements as $element) {
//                if ($strategy->meetsCondition($element)) {
//                     $filteredElements[] = $element;
//                }
//            }
        } else {
            //todo: this will be funnier!
        }
    }

    public function neco()
    {
//            $decisionMaker = function (SimpleXMLIterator $rootElement, $attributes) use ($selectElementName, $strategy) {
//
//
//
//                return $rootElement->getName() === $selectElementName;
//            };
//
//        $fromElement->rewind();
//        $foundElements = $this->xmlParser->findFromElements($decisionMaker, $fromElement, $findRoot, false, true);
//
//        if ($this->query->getConditionLeft() === '') {
//            return $foundElements;
//        }
//
//        return $this->filterSelectElements($foundElements);
    }


    public function getRoot()
    {
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
            $attributes[$name] = (string)$value;
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
        if ($this->query->getConditionLeft()->getType() === Token::TOKEN_ELEMENT) {
            return new ElementConditionStrategy($this->query);
        } elseif ($this->query->getConditionLeft()->getType() === Token::TOKEN_ATTRIBUTE) {
            return new AttributeConditionStrategy($this->query);
        } elseif ($this->query->getConditionLeft()->getType() === Token::TOKEN_ELEMENT_WITH_ATTRIBUTE) {
            return new ElementWithAttributeStrategy($this->query);
        } else {
            throw new InvalidQueryException('The element in condition is not valid');
        }
    }
}
