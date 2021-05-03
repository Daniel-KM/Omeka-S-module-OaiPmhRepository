<?php declare(strict_types=1);
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2018
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh;

use DOMElement;

/**
 * Parent class for all XML-generating classes.
 */
class AbstractXmlGenerator
{
    const XML_SCHEMA_NAMESPACE_URI = 'http://www.w3.org/2001/XMLSchema-instance';

    /**
     * Creates a new XML element with the specified children.
     *
     * Creates a parent element with the given name, with children with names
     * and values as given.  Adds the resulting element as a child of the given
     * element
     *
     * @param DomElement $parent   Existing parent of all the new nodes
     * @param string     $name     Name of the new parent element
     * @param array      $children Child names and values, as name => value
     * @return DomElement The new tree of elements
     */
    protected function createElementWithChildren(DOMElement $parent, $name, $children)
    {
        $document = $parent->ownerDocument;
        $newElement = $document->createElement($name);
        foreach ($children as $tag => $value) {
            if (is_array($value)) {
                $this->createElementWithChildren($newElement, $tag, $value);
            } else {
                $element = $document->createElement($tag);
                $element->appendChild($document->createTextNode((string) $value));
                $newElement->appendChild($element);
            }
        }
        $parent->appendChild($newElement);

        return $newElement;
    }

    /**
     * Creates a parent element with the given name, with text as given.
     *
     * Adds the resulting element as a child of the given parent node.
     *
     * @param DomElement $parent Existing parent of all the new nodes
     * @param string     $name   Name of the new parent element
     * @param string     $text   Text of the new element
     * @param array      $attributes List of attributes
     * @return DomElement The new element
     */
    protected function appendNewElement(DOMElement $parent, $name, $text = null, array $attributes = [])
    {
        $document = $parent->ownerDocument;
        $newElement = $document->createElement($name);
        // Use a TextNode, causes escaping of input text
        if ($text) {
            $text = $document->createTextNode((string) $text);
            $newElement->appendChild($text);
        }
        foreach ($attributes as $name => $attribute) {
            $newElement->setAttribute($name, $attribute);
        }
        $parent->appendChild($newElement);

        return $newElement;
    }
}
