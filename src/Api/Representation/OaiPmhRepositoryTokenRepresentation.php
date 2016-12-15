<?php
/**
 * @author Julian Maurice <julian.maurice@biblibre.com>
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class OaiPmhRepositoryTokenRepresentation extends AbstractEntityRepresentation
{
    /**
     * {@inheritdoc}
     */
    public function getJsonLdType()
    {
        return 'o:OaiPmhRepositoryToken';
    }

    public function getJsonLd()
    {
        $entity = $this->resource;

        return [
            'o:verb' => $entity->getVerb(),
            'o:metadata_prefix' => $entity->getMetadataPrefix(),
            'o:cursor' => $entity->getCursor(),
            'o:from' => $this->getDateTime($entity->getFrom()),
            'o:until' => $this->getDateTime($entity->getUntil()),
            'o:set' => $entity->getSet(),
            'o:expiration' => $this->getDateTime($entity->getExpiration()),
        ];
    }

    public function verb()
    {
        return $this->resource->getVerb();
    }

    public function metadataPrefix()
    {
        return $this->resource->getMetadataPrefix();
    }

    public function cursor()
    {
        return $this->resource->getCursor();
    }

    public function from()
    {
        return $this->resource->getFrom();
    }

    public function until()
    {
        return $this->resource->getUntil();
    }

    public function set()
    {
        return $this->resource->getSet();
    }

    public function expiration()
    {
        return $this->resource->getExpiration();
    }
}
