<?php declare(strict_types = 1);

namespace AssetMulti\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Asset;
use Omeka\Entity\Resource;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"`type`", "resource_id", "asset_id"}
 *         )
 *     }
 * )
 */
class ResourceAsset extends AbstractEntity
{
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
     * @var \Omeka\Entity\Resource
     *
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Resource"
     * )
     * @JoinColumn(
     *     name="resource_id",
     *     referencedColumnName="id",
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $resource;

    /**
     * @var \Omeka\Entity\Asset
     *
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Asset"
     * )
     * @JoinColumn(
     *     name="asset_id",
     *     referencedColumnName="id",
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $asset;

    /**
     * @var string
     *
     * @Column(
     *     name="`type`",
     *     type="string",
     *     length=190,
     *     nullable=false
     * )
     */
    protected $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(Resource $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(Asset $asset): self
    {
        $this->asset = $asset;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
}
