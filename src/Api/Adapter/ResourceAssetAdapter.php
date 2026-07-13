<?php declare(strict_types=1);

namespace AssetMulti\Api\Adapter;

use Common\Stdlib\PsrMessage;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ResourceAssetAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'type' => 'type',
        'resource' => 'resource',
        'asset' => 'asset',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'type' => 'type',
        'resource' => 'resource',
        'asset' => 'asset',
    ];

    public function getResourceName()
    {
        return 'resource_assets';
    }

    public function getRepresentationClass()
    {
        return \AssetMulti\Api\Representation\ResourceAssetRepresentation::class;
    }

    public function getEntityClass()
    {
        return \AssetMulti\Entity\ResourceAsset::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query): void
    {
        $expr = $qb->expr();

        if (isset($query['resource_id']) && $query['resource_id'] !== '' && $query['resource_id'] !== []) {
            $ids = $query['resource_id'];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $ids = array_filter(array_map('intval', $ids));
            if ($ids) {
                $qb->andWhere($qb->expr()->in(
                    'omeka_root.resource',
                    $this->createNamedParameter($qb, $ids)
                ));
            } else {
                // Avoid an issue with a resource_id is set in query but empty.
                $qb->andWhere($expr->eq('omeka_root.resource', -1));
            }
        }

        if (isset($query['asset_id']) && $query['asset_id'] !== '' && $query['asset_id'] !== []) {
            $ids = $query['asset_id'];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $ids = array_filter(array_map('intval', $ids));
            if ($ids) {
                $qb->andWhere($qb->expr()->in(
                    'omeka_root.asset',
                    $this->createNamedParameter($qb, $ids)
                    ));
            } else {
                // Avoid an issue with a asset_id is set in query but empty.
                $qb->andWhere($expr->eq('omeka_root.asset', -1));
            }
        }

        if (isset($query['type']) && $query['type'] !== '' && $query['type'] !== []) {
            $ids = $query['type'];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $ids = array_filter(array_map('strval', $ids), 'strlen');
            if ($ids) {
                $qb->andWhere($qb->expr()->in(
                    'omeka_root.type',
                    $this->createNamedParameter($qb, $ids)
                ));
            } else {
                // Avoid an issue with a type is set in query but empty.
                $qb->andWhere($expr->eq('omeka_root.id', -1));
            }
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            if (!$request->getValue('o:resource')) {
                $errorStore->addError('o:resource', 'An asset for resource must have a resource.'); // @translate
            }
            if (!$request->getValue('o:asset')) {
                $errorStore->addError('o:asset', 'An asset for resource must have an asset.'); // @translate
            }
            $type = $request->getValue('o:type');
            if ($type === null || $type === '') {
                $errorStore->addError('o:type', 'An asset for resource must have a type.'); // @translate
            }
        }
    }

    public function hydrate(
        Request $request,
        EntityInterface $entity,
        ErrorStore $errorStore
    ): void {
        /** @var \AssetMulti\Entity\ResourceAsset $entity */

        $data = $request->getContent();

        if ($this->shouldHydrate($request, 'o:resource')) {
            if (isset($data['o:resource'])) {
                if (is_array($data['o:resource'])) {
                    $resource = isset($data['o:resource']['o:id']) && is_numeric($data['o:resource']['o:id'])
                        ? $this->getAdapter('resources')->findEntity($data['o:resource']['o:id'])
                        : null;
                } elseif ($data['o:resource'] instanceof \Omeka\Api\Representation\AbstractResourceEntityRepresentation) {
                    $resource = $this->getAdapter('resources')->findEntity($data['o:resource']->id());
                } elseif ($data['o:resource'] instanceof \Omeka\Entity\Resource) {
                    $resource = $data['o:resource'];
                } else {
                    $resource = null;
                }
                if ($resource) {
                    $entity->setResource($resource);
                }
            }
        }

        if ($this->shouldHydrate($request, 'o:asset')) {
            if (isset($data['o:asset'])) {
                if (is_array($data['o:asset'])) {
                    $asset = isset($data['o:asset']['o:id']) && is_numeric($data['o:asset']['o:id'])
                        ? $this->getAdapter('assets')->findEntity($data['o:asset']['o:id'])
                        : null;
                } elseif ($data['o:asset'] instanceof \Omeka\Api\Representation\AssetRepresentation) {
                    $asset = $this->getAdapter('assets')->findEntity($data['o:asset']->id());
                } elseif ($data['o:asset'] instanceof \Omeka\Entity\Asset) {
                    $asset = $data['o:asset'];
                } else {
                    $asset = null;
                }
                if ($asset) {
                    $entity->setAsset($asset);
                }
            }
        }

        if ($this->shouldHydrate($request, 'o:type')) {
            $entity->setType($request->getValue('o:type'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        /** @var \AssetMulti\Entity\ResourceAsset $entity */

        $resource = $entity->getResource();
        $asset = $entity->getAsset();
        $type = $entity->getType();

        if (!$resource) {
            $errorStore->addError('o:resource', 'The resource cannot be empty.'); // @translate
        }
        if (!$asset) {
            $errorStore->addError('o:asset', 'The asset cannot be empty.'); // @translate
        }
        $hasType = $type !== null && $type !== '';
        if (!$hasType) {
            $errorStore->addError('o:type', 'The type cannot be empty.'); // @translate
        }
        if ($resource && $asset && $hasType && !$this->isUnique($entity, [
            'resource' => $resource,
            'asset' => $asset,
            'type' => $type,
        ])) {
            $errorStore->addError('o:type', new PsrMessage(
                'The type "{type}" is not unique for the pair resource {resource_id} and asset {asset_id}.', // @translate
                ['type' => $type, 'resource_id' => $resource->getId(), 'asset_id' => $asset->getId()]
            ));
        }
    }
}
