<?php declare(strict_types=1);
/**
 * @author Julian Maurice <julian.maurice@biblibre.com>
 * @copyright BibLibre, 2016
 * @author Daniel Berthereau <daniel.github@berthereau.net>
 * @copyright Daniel Berthereau, 2017-2023
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
namespace OaiPmhRepository\Entity;

use DateTime;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Exception\InvalidArgumentException;

/**
 * @Entity
 * @Table(
 *      indexes={@Index(columns={
 *          "expiration"
 *      })}
 * )
 */
class OaiPmhRepositoryToken extends AbstractEntity
{
    const VERB_LIST_IDENTIFIERS = 'ListIdentifiers';
    const VERB_LIST_RECORDS = 'ListRecords';
    const VERB_LIST_SETS = 'ListSets';

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="string", length=190)
     */
    protected $verb;

    /**
     * @Column(type="string", length=190)
     */
    protected $metadataPrefix;

    /**
     * @Column(name="`cursor`", type="integer")
     */
    protected $cursor;

    /**
     * @Column(name="`from`", type="datetime", nullable=true)
     */
    protected $from;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $until;

    /**
     * @Column(name="`set`", type="string", length=190, nullable=true)
     */
    protected $set;

    /**
     * @Column(type="datetime")
     */
    protected $expiration;

    public function getId()
    {
        return $this->id;
    }

    public function setVerb($verb): void
    {
        if (!in_array($verb, [
            self::VERB_LIST_IDENTIFIERS,
            self::VERB_LIST_RECORDS,
            self::VERB_LIST_SETS,
        ])) {
            throw new InvalidArgumentException('Invalid OAI-PMH verb.');
        }
        $this->verb = $verb;
    }

    public function getVerb()
    {
        return $this->verb;
    }

    public function setMetadataPrefix($metadataPrefix): void
    {
        $this->metadataPrefix = $metadataPrefix;
    }

    public function getMetadataPrefix()
    {
        return $this->metadataPrefix;
    }

    public function setCursor($cursor): void
    {
        $this->cursor = $cursor;
    }

    public function getCursor()
    {
        return $this->cursor;
    }

    public function setFrom($from): void
    {
        $this->from = $from;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setUntil($until): void
    {
        $this->until = $until;
    }

    public function getUntil()
    {
        return $this->until;
    }

    public function setSet($set): void
    {
        $this->set = $set;
    }

    public function getSet()
    {
        return $this->set;
    }

    public function setExpiration(DateTime $expiration): void
    {
        $this->expiration = $expiration;
    }

    public function getExpiration()
    {
        return $this->expiration;
    }
}
