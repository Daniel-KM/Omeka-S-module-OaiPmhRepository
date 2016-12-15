<?php
/**
 * @author Julian Maurice <julian.maurice@biblibre.com>
 * @copyright BibLibre, 2016
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace OaiPmhRepository\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class OaiPmhRepositoryTokenAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritdoc}
     */
    protected $sortFields = [
        'id' => 'id',
    ];

    /**
     * {@inheritdoc}
     */
    public function getResourceName()
    {
        return 'oaipmh_repository_tokens';
    }

    /**
     * {@inheritdoc}
     */
    public function getRepresentationClass()
    {
        return 'OaiPmhRepository\Api\Representation\OaiPmhRepositoryTokenRepresentation';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'OaiPmhRepository\Entity\OaiPmhRepositoryToken';
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if ($this->shouldHydrate($request, 'o:verb')) {
            $entity->setVerb($request->getValue('o:verb'));
        }
        if ($this->shouldHydrate($request, 'o:metadata_prefix')) {
            $entity->setMetadataPrefix($request->getValue('o:metadata_prefix'));
        }
        if ($this->shouldHydrate($request, 'o:cursor')) {
            $entity->setCursor($request->getValue('o:cursor'));
        }
        if ($this->shouldHydrate($request, 'o:from')) {
            $from = $request->getValue('o:from');
            if ($from) {
                $entity->setFrom(new DateTime($from));
            }
        }
        if ($this->shouldHydrate($request, 'o:until')) {
            $until = $request->getValue('o:until');
            if ($until) {
                $entity->setUntil(new DateTime($until));
            }
        }
        if ($this->shouldHydrate($request, 'o:set')) {
            $entity->setSet($request->getValue('o:set'));
        }
        if ($this->shouldHydrate($request, 'o:expiration')) {
            $expiration = $request->getValue('o:expiration');
            if ($expiration) {
                $entity->setExpiration(new DateTime($expiration));
            }
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['verb'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.verb',
                $this->createNamedParameter($qb, $query['verb']))
            );
        }
        if (isset($query['expired']) && $query['expired']) {
            $qb->andWhere($qb->expr()->lte(
                $this->getEntityClass() . '.expiration',
                $this->createNamedParameter($qb, (new DateTime)->format(DateTime::ATOM))
            ));
        }
    }
}
