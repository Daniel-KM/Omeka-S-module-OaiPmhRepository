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
 *     name="oaipmhrepository_token",
 *     indexes={
 *         @Index(
 *             columns={
 *                 "expiration"
 *             }
 *         )
 *     }
 * )
 */
class OaiPmhRepositoryToken extends AbstractEntity
{
    const VERB_LIST_IDENTIFIERS = 'ListIdentifiers';
    const VERB_LIST_RECORDS = 'ListRecords';
    const VERB_LIST_SETS = 'ListSets';

    /**
     * @var int
     *
     * @Id
     * @Column(
     *     type="integer"
     * )
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @Column(
     *     type="string",
     *     length=15,
     *     nullable=false
     * )
     */
    protected $verb;

    /**
     * @var string
     *
     * @Column(
     *     type="string",
     *     length=190,
     *     nullable=false
     * )
     */
    protected $metadataPrefix;

    /**
     * @var int
     *
     * @Column(
     *     name="`cursor`",
     *     type="integer",
     *     nullable=false
     * )
     */
    protected $cursor;

    /**
     * @var DateTime
     *
     * @Column(
     *     name="`from`",
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $from;

    /**
     * @var \DateTime
     *
     * @Column(
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $until;

    /**
     * @var string
     *
     * @Column(
     *     name="`set`",
     *     type="string",
     *     length=190,
     *     nullable=true
     * )
     */
    protected $set;

    /**
     * @var \DateTime
     *
     * @Column(
     *     type="datetime",
     *     nullable=false
     * )
     */
    protected $expiration;

    public function getId()
    {
        return $this->id;
    }

    public function setVerb(string $verb): self
    {
        if (!in_array($verb, [
            self::VERB_LIST_IDENTIFIERS,
            self::VERB_LIST_RECORDS,
            self::VERB_LIST_SETS,
        ])) {
            throw new InvalidArgumentException('Invalid OAI-PMH verb.');
        }
        $this->verb = $verb;
        return $this;
    }

    public function getVerb(): string
    {
        return $this->verb;
    }

    public function setMetadataPrefix(string $metadataPrefix): self
    {
        $this->metadataPrefix = $metadataPrefix;
        return $this;
    }

    public function getMetadataPrefix(): string
    {
        return $this->metadataPrefix;
    }

    public function setCursor(int $cursor): self
    {
        $this->cursor = $cursor;
        return $this;
    }

    public function getCursor(): int
    {
        return $this->cursor;
    }

    public function setFrom(?DateTime $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function getFrom(): ?DateTime
    {
        return $this->from;
    }

    public function setUntil(?DateTime $until): self
    {
        $this->until = $until;
        return $this;
    }

    public function getUntil(): ?DateTime
    {
        return $this->until;
    }

    public function setSet($set): self
    {
        $set = (string) $set;
        $this->set = strlen($set) ? $set : null;
        return $this;
    }

    public function getSet(): ?string
    {
        return $this->set;
    }

    public function setExpiration(DateTime $expiration): self
    {
        $this->expiration = $expiration;
        return $this;
    }

    public function getExpiration(): DateTime
    {
        return $this->expiration;
    }
}
