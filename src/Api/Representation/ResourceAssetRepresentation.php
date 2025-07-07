<?php declare(strict_types=1);

namespace AssetMulti\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\AssetRepresentation;

class ResourceAssetRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var \AssetMulti\Entity\ResourceAsset
     */
    protected $resource;

    public function getControllerName(): string
    {
        return 'resource-asset';
    }

    public function getJsonLdType(): string
    {
        return 'o:ResourceAsset';
    }

    public function getJsonLd()
    {
        return [
            'o:resource' => $this->resource()->getReference()->jsonSerialize(),
            'o:asset' => $this->asset()->getReference()->jsonSerialize(),
            'o:type' => $this->type(),
        ];
    }

    public function resource(): AbstractResourceEntityRepresentation
    {
        return $this->getAdapter('resources')
            ->getRepresentation($this->resource->getResource());
    }

    public function asset(): AssetRepresentation
    {
        return $this->getAdapter('assets')
            ->getRepresentation($this->resource->getAsset());
    }

    public function thumbnail(): AssetRepresentation
    {
        return $this->getAdapter('assets')
            ->getRepresentation($this->resource->getAsset());
    }

    public function type(): string
    {
        return $this->resource->getType();
    }
}
