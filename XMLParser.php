<?php


class XMLParser
{
    /**
     * @var SimpleXMLIterator
     */
    protected $iterator;

    /**
     * XMLParser constructor.
     *
     * @param $fileName
     */
    public function __construct($fileName)
    {
        $this->iterator = @new SimpleXMLIterator($fileName, 0, true);
        $this->iterator->rewind();

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
     * @return SimpleXMLIterator|null
     */
    public function findElement(Closure $decisionMaker, SimpleXMLIterator $iterator = null, $getRoot = false)
    {
        if ($iterator === null) {
            $iterator = $this->iterator; //root iterator
        }

        if ($getRoot === true) {
            return $iterator;
        }

        $nameRoot = $iterator->getName();

        //check if we are looking for the root element
        $attributes = $this->getAttributes($iterator);
        if ($decisionMaker($iterator, $attributes)) {
            return $iterator;
        }

        foreach ($iterator as $rootElement) {
            if ($rootElement === null) {
                break;
            }

            $nameChild = $rootElement->getName();

            $attributes = $this->getAttributes($rootElement);
            if ($decisionMaker($rootElement, $attributes)) {
                return $rootElement;
            } else {
                $foundElement = $this->findElement($decisionMaker, $rootElement);
                if ( $foundElement !== null) {
                    return $foundElement;
                }
            }

//            foreach ($rootElement as $element) {
//                $name = $element->getName();
//                $attrs = $element->attributes();
//                var_dump($attrs);
//            }
        }

        //the element was not found...

        return null;

//        /** @var SimpleXMLIterator $child */
//        foreach ($iterator->getChildren() as $child) {
//            $attrs = $child->attributes();
//            var_dump($attrs);
//            $neco = $child->getChildren();
//            var_dump($neco);
//        }
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
}
