<?php declare(strict_types=1);
/**
 * @author Julian Maurice <julian.maurice@biblibre.com>
 * @copyright BibLibre, 2016
 * @copyright Daniel Berthereau, 2014-2023
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\Api\Representation;

use DateTime;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class OaiPmhRepositoryTokenRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o:OaiPmhRepositoryToken';
    }

    public function getJsonLd()
    {
        $entity = $this->resource;

        return [
            'o-oai-pmh:verb' => $entity->getVerb(),
            'o-oai-pmh:metadata_prefix' => $entity->getMetadataPrefix(),
            'o-oai-pmh:cursor' => $entity->getCursor(),
            'o-oai-pmh:from' => $this->getDateTime($entity->getFrom()),
            'o-oai-pmh:until' => $this->getDateTime($entity->getUntil()),
            'o-oai-pmh:set' => $entity->getSet(),
            'o-oai-pmh:expiration' => $this->getDateTime($entity->getExpiration()),
        ];
    }

    public function verb(): string
    {
        return $this->resource->getVerb();
    }

    public function metadataPrefix(): string
    {
        return $this->resource->getMetadataPrefix();
    }

    public function cursor(): int
    {
        return $this->resource->getCursor();
    }

    public function from(): ?DateTime
    {
        return $this->resource->getFrom();
    }

    public function until(): ?DateTime
    {
        return $this->resource->getUntil();
    }

    public function set(): ?string
    {
        return $this->resource->getSet();
    }

    public function expiration(): DateTime
    {
        return $this->resource->getExpiration();
    }
}
