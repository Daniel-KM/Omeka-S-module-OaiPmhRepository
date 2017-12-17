<?php
/**
 * @author John Flatness
 * @copyright Copyright 2012 John Flatness
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\Metadata;

use DOMElement;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implmenting MODS metadata output format.
 *
 * @link http://www.loc.gov/standards/mods/
 */
class Mets extends AbstractMetadata
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'mets';

    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.loc.gov/METS/';

    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://www.loc.gov/standards/mets/mets.xsd';

    /** XML namespace for unqualified Dublin Core */
    const DC_NAMESPACE_URI = 'http://purl.org/dc/elements/1.1/';

    /**
     * Appends MODS metadata.
     *
     * Appends a metadata element, an child element with the required format,
     * and further children for each of the Dublin Core fields present in the
     * item.
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item)
    {
        $document = $metadataElement->ownerDocument;
        $mets = $document->createElementNS(self::METADATA_NAMESPACE, 'mets');
        $metadataElement->appendChild($mets);

        /* Must manually specify XML schema uri per spec, but DOM won't include
         * a redundant xmlns:xsi attribute, so we just set the attribute
         */
        $mets->setAttribute('xmlns:xsi', self::XML_SCHEMA_NAMESPACE_URI);
        $mets->setAttribute('xsi:schemaLocation', self::METADATA_NAMESPACE
            . ' ' . self::METADATA_SCHEMA);

        $mets->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

        $metadataSection = $this->appendNewElement($mets, 'dmdSec');
        $itemDmdId = 'dmd-' . $item->id();
        $metadataSection->setAttribute('ID', $itemDmdId);
        $dcWrap = $this->appendNewElement($metadataSection, 'mdWrap');
        $dcWrap->setAttribute('MDTYPE', 'DC');

        $dcXml = $this->appendNewElement($dcWrap, 'xmlData');
        $dcXml->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);

        $dcElementNames = [
            'title', 'creator', 'subject', 'description', 'publisher',
            'contributor', 'date', 'type', 'format', 'identifier', 'source',
            'language', 'relation', 'coverage', 'rights',
        ];

        foreach ($dcElementNames as $elementName) {
            $values = $item->value("dcterms:$elementName", ['all' => true]);
            foreach ($values as $value) {
                $this->appendNewElement($dcXml, "dc:$elementName", (string) $value);
            }
        }

        $fileIds = [];
        $mediaList = $item->media();
        if (count($mediaList)) {
            $fileSection = $this->appendNewElement($mets, 'fileSec');
            $fileGroup = $this->appendNewElement($fileSection, 'fileGrp');
            $fileGroup->setAttribute('USE', 'ORIGINAL');

            foreach ($mediaList as $media) {
                $fileDmdId = 'dmd-file-' . $media->id();
                $fileId = 'file-' . $media->id();

                $fileElement = $this->appendNewElement($fileGroup, 'file');
                $fileElement->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);
                $fileElement->setAttribute('ID', $fileId);
                //$fileElement->setAttribute('MIMETYPE', $file->mime_type);
                $fileElement->setAttribute('CHECKSUM', $media->sha256());
                $fileElement->setAttribute('CHECKSUMTYPE', 'SHA-256');
                $fileElement->setAttribute('DMDID', $fileDmdId);

                $location = $this->appendNewElement($fileElement, 'FLocat');

                $location->setAttribute('LOCTYPE', 'URL');
                $location->setAttribute('xlink:type', 'simple');
                $location->setAttribute('xlink:title', $media->filename());
                $location->setAttribute('xlink:href', $media->originalUrl());

                $fileContentMetadata = $this->appendNewElement($mets, 'dmdSec');
                $fileContentMetadata->setAttribute('ID', $fileDmdId);

                $fileDcWrap = $this->appendNewElement($fileContentMetadata, 'mdWrap');
                $fileDcWrap->setAttribute('MDTYPE', 'DC');

                $fileDcXml = $this->appendNewElement($fileDcWrap, 'xmlData');
                $fileDcXml->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);

                $fileIds[] = $fileId;

                foreach ($dcElementNames as $elementName) {
                    $upperName = ucfirst($elementName);
                    $dcElements = $media->value("dcterms:$elementName", ['all' => true]);

                    if (isset($dcElements)) {
                        $this->appendNewElement($fileDcXml, "dc:$elementName", $dcElements);
                    }
                }
            }
        }

        $structMap = $this->appendNewElement($mets, 'structMap');
        $topDiv = $this->appendNewElement($structMap, 'div');
        $topDiv->setAttribute('DMDID', $itemDmdId);
        foreach ($fileIds as $id) {
            $fptr = $this->appendNewElement($topDiv, 'fptr');
            $fptr->setAttribute('FILEID', $id);
        }
    }

    /**
     * Returns the OAI-PMH metadata prefix for the output format.
     *
     * @return string Metadata prefix
     */
    public function getMetadataPrefix()
    {
        return self::METADATA_PREFIX;
    }

    /**
     * Returns the XML schema for the output format.
     *
     * @return string XML schema URI
     */
    public function getMetadataSchema()
    {
        return self::METADATA_SCHEMA;
    }

    /**
     * Returns the XML namespace for the output format.
     *
     * @return string XML namespace URI
     */
    public function getMetadataNamespace()
    {
        return self::METADATA_NAMESPACE;
    }
}
