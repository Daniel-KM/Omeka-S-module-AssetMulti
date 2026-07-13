<?php declare(strict_types=1);

namespace AssetMulti\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class ResourceAsset implements ResourcePageBlockLayoutInterface
{
    public function getLabel(): string
    {
        return 'Complementary asset'; // @translate
    }

    public function getCompatibleResourceNames(): array
    {
        return [
            'items',
            'media',
            'item_sets',
            'digital_objects',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        return $view->partial('common/resource-page-block-layout/resource-asset', [
            'resource' => $resource,
        ]);
    }
}
