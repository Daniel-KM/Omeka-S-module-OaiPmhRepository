<?php declare(strict_types=1);
/**
 * @author John Flatness, Yu-Hsun Lin
 * @copyright Copyright 2009 John Flatness, Yu-Hsun Lin
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2023
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Plugin;

use DOMElement;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

/**
 * Utility class for dealing with OAI identifiers.
 *
 * OaiIdentifier represents an instance of a unique identifier for the
 * repository conforming to the oai-identifier recommendation.
 * The class can parse the local ID out of a given identifier string, or create
 * a new identifier by specifing the local ID of the item.
 */
class OaiIdentifier
{
    const OAI_IDENTIFIER_NAMESPACE_URI = 'http://www.openarchives.org/OAI/2.0/oai-identifier';
    const OAI_IDENTIFIER_SCHEMA_URI = 'http://www.openarchives.org/OAI/2.0/oai-identifier.xsd';

    private static $namespaceId;

    public static function initializeNamespace($namespaceId): void
    {
        self::$namespaceId = $namespaceId;
    }

    /**
     * Extract the oai local identifier from the given OAI identifier.
     *
     * The local identifier is generally the Omeka item ID, but it may be
     * prefixed by a set identifier or it may be any specific identifier.
     *
     * @param string $oaiId OAI identifier
     *
     * @return string The oai local identifier, generally the Omeka item ID.
     */
    public static function oaiIdToItem($oaiId): ?string
    {
        $scheme = strtok($oaiId, ':');
        $namespaceId = strtok(':');
        // No need of mbstrings: they are forbidden by standard.
        $localId = mb_substr($oaiId, mb_strlen($scheme . ':' . $namespaceId . ':'));
        if ($scheme !== 'oai' || $namespaceId !== self::$namespaceId || !strlen($localId)) {
            return null;
        }
        return $localId;
    }

    /**
     * Converts the given Omeka item ID or any oai local id to a OAI identifier.
     *
     * @param mixed $itemOrLocalId Omeka item or oai local identifier
     *
     * @return string OAI identifier
     */
    public static function itemToOaiId($itemOrLocalId): string
    {
        if (!$itemOrLocalId instanceof AbstractResourceEntityRepresentation) {
            return 'oai:' . self::$namespaceId . ':' . $itemOrLocalId;
        }
        $id = $itemOrLocalId->id();
        return 'oai:' . self::$namespaceId . ':' . $id;
    }

    /**
     * Outputs description element child describing the repository's OAI
     * identifier implementation.
     *
     * @param DOMElement $parentElement Parent DOM element for XML output
     */
    public static function describeIdentifier($parentElement): void
    {
        $elements = [
            'scheme' => 'oai',
            'repositoryIdentifier' => self::$namespaceId,
            'delimiter' => ':',
            'sampleIdentifier' => self::itemtoOaiId(1),
        ];
        $oaiIdentifier = $parentElement->ownerDocument->createElement('oai-identifier');

        foreach ($elements as $tag => $value) {
            $oaiIdentifier->appendChild($parentElement->ownerDocument->createElement($tag, (string) $value));
        }
        $parentElement->appendChild($oaiIdentifier);

        // Must set xmlns attribute manually to avoid DOM extension appending
        // default: prefix to element name.
        $oaiIdentifier->setAttribute('xmlns', self::OAI_IDENTIFIER_NAMESPACE_URI);
        $oaiIdentifier->setAttribute('xsi:schemaLocation',
            self::OAI_IDENTIFIER_NAMESPACE_URI . ' ' . self::OAI_IDENTIFIER_SCHEMA_URI);
    }
}
