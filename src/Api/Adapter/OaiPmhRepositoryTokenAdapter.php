<?php declare(strict_types=1);
/**
 * @author Julian Maurice <julian.maurice@biblibre.com>
 * @copyright BibLibre, 2016
 * @author Daniel Berthereau <daniel.github@berthereau.net>
 * @copyright Daniel Berthereau, 2014-2023
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use OaiPmhRepository\Api\Representation\OaiPmhRepositoryTokenRepresentation;
use OaiPmhRepository\Entity\OaiPmhRepositoryToken;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class OaiPmhRepositoryTokenAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'verb' => 'verb',
        'metadata_prefix' => 'metadataPrefix',
        'cursor' => 'cursor',
        'from' => 'from',
        'until' => 'until',
        'set' => 'set',
        'expiration' => 'expiration',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'verb' => 'verb',
        'metadata_prefix' => 'metadataPrefix',
        'cursor' => 'cursor',
        'from' => 'from',
        'until' => 'until',
        'set' => 'set',
        'expiration' => 'expiration',
    ];

    public function getResourceName()
    {
        return 'oaipmh_repository_tokens';
    }

    public function getRepresentationClass()
    {
        return OaiPmhRepositoryTokenRepresentation::class;
    }

    public function getEntityClass()
    {
        return OaiPmhRepositoryToken::class;
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ): void {
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
                $entity->setFrom($from);
            }
        }
        if ($this->shouldHydrate($request, 'o:until')) {
            $until = $request->getValue('o:until');
            if ($until) {
                $entity->setUntil($until);
            }
        }
        if ($this->shouldHydrate($request, 'o:set')) {
            $entity->setSet($request->getValue('o:set'));
        }
        if ($this->shouldHydrate($request, 'o:expiration')) {
            $expiration = $request->getValue('o:expiration');
            if ($expiration) {
                $entity->setExpiration($expiration);
            }
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query): void
    {
        $expr = $qb->expr();

        if (isset($query['verb'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.verb',
                $this->createNamedParameter($qb, $query['verb']))
            );
        }
        if (isset($query['expired']) && $query['expired']) {
            $qb->andWhere($expr->lte(
                'omeka_root.expiration',
                $this->createNamedParameter($qb, (new DateTime)->format(DateTime::ATOM))
            ));
        }
    }
}
