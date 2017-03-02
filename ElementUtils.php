<?php


class ElementUtils
{
    /**
     * Get attribtes from the element.
     *
     * @param SimpleXMLElement $element
     *
     * @return array
     */
    public static function getAttributes(SimpleXMLElement $element)
    {
        $attributes = [];

        foreach ($element->attributes() as $name => $value) {
            $attributes[$name] = (string) $value;
        }

        return $attributes;
    }

    /**
     * @param SimpleXMLElement $element
     * @return bool
     */
    public static function hasAttribute(SimpleXMLElement $element, $attributeName)
    {
        $attributeName = str_replace('.', '', $attributeName); //remove the dot

        foreach (self::getAttributes($element) as $key => $value) {
            if ($key === $attributeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param SimpleXMLElement $element
     * @return mixed
     */
    public static function getAttributeValue(SimpleXMLElement $element, $attributeName)
    {
        $attributeName = str_replace('.', '', $attributeName); //remove the dot

        //todo: this may return the SimpleXMLElement instance!!! type to string?
        return $element->attributes()[$attributeName];
    }

    /**
     * @param SimpleXMLElement[] $elements
     * @param bool $generateXmlHeader
     * @param string $rootElementName
     * @return string
     */
    public static function getXmlString($elements, $generateXmlHeader, $rootElementName)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $emptyDocumentHeader = $document->saveXML();
        $document->formatOutput = true;
        $rootElement = null; //either the whole document or the artificial root

        if ($rootElementName !== '') {
            $rootElement = $document->createElement($rootElementName);
            $document->appendChild($rootElement);
        } else {
            $rootElement = $document;
        }

        foreach ($elements as $selectElement) {
            $node = dom_import_simplexml($selectElement);
            $rootElement->appendChild($document->importNode($node, true));
        }

        if ($generateXmlHeader) {
            return $document->saveXML();
        } else {
            return str_replace($emptyDocumentHeader, '', $document->saveXML());
        }
    }
}