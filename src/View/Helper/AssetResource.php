<?php declare(strict_types=1);

namespace AssetMulti\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class AssetResource extends AbstractHelper
{
    /**
     * Get the asset or all assets or all resource assets for resources or types.
     *
     * The original asset is not returned.
     * @todo Use type "default" for original asset.
     *
     * @param \Omeka\Api\Representation\AbstractResourceEntityRepresentation[]|int[]|\Omeka\Api\Representation\AbstractResourceEntityRepresentation|int|null $resource
     * @param string[]|string|null $type
     * @return \Omeka\Api\Representation\AssetRepresentation[]|\Omeka\Api\Representation\AssetRepresentation|\AssetMulti\Api\Representation\ResourceAssetRepresentation[]|array[]|null
     * The output depends on input:
     * - no resource and no type: output an empty array.
     * - a single resource and a single type: output a single asset, because
     *   there is only one asset by type and by resource.
     * - a single resource and none or multiple types: output the asset for each type.
     * - none or multiple resources and a single type: output the list of resource assets.
     * - none or multiple resources and none or multiple types: output the list
     *   of resource assets for each type.
     * When a list of types is set, all types are returned, even without value.
     */
    public function __invoke($resource = null, $type = null)
    {
        if (empty($resource) && empty($type)) {
            return [];
        }

        $view = $this->getView();

        $plugins = $view->getHelperPluginManager();
        $api = $plugins->get('api');

        $query = [];

        if ($resource) {
            if (is_array($resource)) {
                $first = reset($resource);
                $query['resource_id'] = is_numeric($first)
                    ? $resource
                    : array_map(fn ($v) => method_exists($v, 'id') ? $v->id() : $v->getId(), $resource);
            } else {
                $query['resource_id'] = is_numeric($resource)
                    ? (int) $resource
                    : $resource->id();
            }
        }

        if ($type) {
            $query['type'] = is_array($type)
                ? array_map('strval', $type)
                : (string) $type;
            $query['sort_by'] = $resource ? 'asset' : 'resource';
            $query['sort_order'] = 'ASC';
        } else {
            $query['sort_by'] = 'type';
            $query['sort_order'] = 'ASC';
        }

        $isSingleResource = isset($query['resource_id']) && is_scalar($query['resource_id']);
        $isSingleType = isset($query['type']) && is_scalar($query['type']);
        $isSingle = $isSingleResource && $isSingleType;

        // Useless, but quicker?
        if ($isSingle) {
            $query['limit'] = 1;
        }

        /** @var \AssetMulti\Api\Representation\ResourceAssetRepresentation[] $resourceAssets */
        $resourceAssets = $api->search('resource_assets', $query)->getContent();

        if ($isSingle) {
            // Single asset.
            return count($resourceAssets)
                ? (reset($resourceAssets))->asset()
                : null;
        }

        // Multiple resource and multiple assets, so return resource assets.
        if ($isSingleType) {
            return $resourceAssets;
        }

        if ($isSingleResource) {
            // Multiple assets for a single resource.
            // Keep original order and all provided types.
            $result = is_array($type) ? array_fill_keys($type, null) : [];
            foreach ($resourceAssets as $resourceAsset) {
                $result[$resourceAsset->type()] = $resourceAsset->asset();
            }
        } else {
            // Multiple resources, assets and types.
            // Keep original order and all provided types.
            $result = is_array($type) ? array_fill_keys($type, []) : [];
            foreach ($resourceAssets as $resourceAsset) {
                $result[$resourceAsset->type()][] = $resourceAsset;
            }
        }

        return $result;
    }
}
