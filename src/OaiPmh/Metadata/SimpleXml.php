<?php declare(strict_types=1);
/**
 * @copyright Daniel Berthereau, 2014-2022
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\OaiPmh\Metadata;

use DOMElement;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Class implementing metadata output for the  simple_xml metadata format.
 *
 * This format contains all the metadata of each resource, but is not standardized.
 *
 * Note: like oai_dcterms, the namespace and the schema don’t exist.
 */
class SimpleXml extends AbstractMetadata
{
    const METADATA_PREFIX = 'simple_xml';
    const METADATA_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/simple_xml/';
    const METADATA_SCHEMA = 'http://www.openarchives.org/OAI/2.0/simple_xml.xsd';

    /**
     * Appends all metadata.
     *
     * {@inheritDoc}
     */
    public function appendMetadata(DOMElement $metadataElement, ItemRepresentation $item): void
    {
        $document = $metadataElement->ownerDocument;

        $values = $this->filterValuesPre($item);

        // Append all schemas used by current resource, not all vocabularies.
        // Prepend omeka namespace to append resource metadata.
        // Include dcterms in all cases to manage single identifier, media urls…
        // Include dctype to manage resource classes.
        // TODO Include namespaces of the modules when used and needed (mapping…).
        $usedVocabularies = [
            'o' => null,
            'dcterms' => null,
            'dctype' => null,
        ];
        foreach (array_keys($values) as $term) {
            $usedVocabularies[strtok($term, ':')] = null;
        }

        // When media are exposed, include their vocabularies.
        if ($this->params['expose_media']) {
            $medias = $item->media();
            foreach ($medias as $media) {
                $mediaValues = $this->filterValuesPre($media);
                foreach (array_keys($mediaValues) as $term) {
                    $usedVocabularies[strtok($term, ':')] = null;
                }
            }
        }

        // Keep omeka, dcterms and dctype first and order alphabetically.
        $usedVocabularies = array_intersect_key($this->params['simple_xml']['vocabularies'], $usedVocabularies);

        // Create the main node.
        $oai = $document->createElementNS(self::METADATA_NAMESPACE, 'simple_xml');
        $metadataElement->appendChild($oai);

        // Include resource metadata on the main node.
        $meta = $this->mainResourceMetadata($item);
        foreach ($meta as $name => $value) {
            if (!is_null($value)) {
                $oai->setAttribute($name, (string) $value);
            }
        }

        // The XML schema uris are included one time at upper level at last.
        foreach ($usedVocabularies as $prefix => $namespaceUri) {
            $oai->setAttribute('xmlns:' . $prefix, $namespaceUri);
        }

        $oai->setAttribute('xmlns:xsi', self::XML_SCHEMA_NAMESPACE_URI);
        /* // Don't include a non-existing schema, even simple.
        $oai->setAttribute('xsi:schemaLocation',
            self::METADATA_NAMESPACE . ' ' . self::METADATA_SCHEMA);
        */

        $this->appendValues($oai, $values);

        $appendIdentifier = $this->singleIdentifier($item);
        if ($appendIdentifier) {
            $this->appendNewElement($oai, 'dcterms:identifier', $appendIdentifier, ['xsi:type' => 'dcterms:URI']);
        }

        // Append medias if needed.
        if ($this->params['expose_media']) {
            foreach ($medias as $media) {
                $meta = $this->mainResourceMetadata($media);
                $meta['o:ingester'] = $media->ingester();
                $meta['o:renderer'] = $media->renderer();
                $meta['o:source'] = $media->source();
                $meta['o:media_type'] = $media->mediaType();
                $meta['o:sha256'] = $media->sha256();
                $meta['o:size'] = $media->size();
                $meta['o:filename'] = $media->filename();
                $meta['o:lang'] = $media->lang();
                $meta['o:alt_text'] = $media->altText();
                $meta['o:original_url'] = $media->originalUrl();
                if ($meta['o:title'] === $meta['o:source']) {
                    unset($meta['o:title']);
                }
                $meta = array_filter($meta);
                $mediaNode = $this->appendNewElement($oai, 'o:media', null, $meta);
                $values = $this->filterValuesPre($media);
                $this->appendValues($mediaNode, $values);
                $data = $media->mediaData();
                if ($data) {
                    $this->appendNewElement($mediaNode, 'o:data', json_encode($data, 320), ['status' => 'experimental']);
                }
            }
        }
    }

    /**
     * Get common resource metadata.
     */
    protected function mainResourceMetadata(AbstractResourceEntityRepresentation $resource): array
    {
        $result = [];

        $class = $resource->resourceClass();
        $result['o:resource_class'] = $class ? $class->term() : null;

        $template = $resource->resourceTemplate();
        $result['o:resource_template'] = $template ? $template->label() : null;

        $thumbnail = $resource->thumbnailDisplayUrl('medium');
        $result['o:thumbnail'] = $thumbnail ?: null;

        $created = $resource->created();
        $result['o:created'] = $created->format('c');

        $modified = $resource->modified();
        $result['o:modified'] = $modified ? $modified->format('c') : null;

        $title = $resource->displayTitle();
        $result['o:title'] = $title;

        return $result;
    }

    protected function appendValues($xmlNode, array $values)
    {
        foreach ($values as $term => $propertyData) {
            /** @var \Omeka\Api\Representation\ValueRepresentation $value */
            foreach ($propertyData['values'] as $value) {
                list($text, $attributes) = $this->formatValue($value);
                $dataType = $value->type();
                if ($dataType !== 'literal') {
                    $attributes['o:type'] = $dataType;
                }
                $this->appendNewElement($xmlNode, $term, $text, $attributes);
            }
        }
        return $this;
    }
}
