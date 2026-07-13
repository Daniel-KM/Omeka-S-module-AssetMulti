<?php declare(strict_types=1);

namespace AssetMultiTest;

use Omeka\Test\AbstractHttpControllerTestCase;

class ModuleTest extends AbstractHttpControllerTestCase
{
    use AssetMultiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        $this->logout();
        parent::tearDown();
    }

    public function testDefaultBannersAreSet(): void
    {
        $banners = $this->getSettings()->get('assetmulti_banners');
        $this->assertIsArray($banners);
        $this->assertArrayHasKey('square', $banners);
    }

    public function testCreateResourceWithAssets(): void
    {
        $asset = $this->createAsset();
        $otherAsset = $this->createAsset('Other asset');

        $item = $this->createItemWithAssets([
            ['o:type' => 'square', 'o:asset' => ['o:id' => $asset->id()]],
            ['o:type' => 'medium', 'o:asset' => ['o:id' => $otherAsset->id()]],
        ]);

        $resourceAssets = $this->resourceAssetsByType($item->id());

        $this->assertCount(2, $resourceAssets);
        $this->assertSame($asset->id(), $resourceAssets['square']);
        $this->assertSame($otherAsset->id(), $resourceAssets['medium']);
    }

    public function testCreateResourceWithoutTypeIsSkipped(): void
    {
        $asset = $this->createAsset();

        $item = $this->createItemWithAssets([
            ['o:type' => '', 'o:asset' => ['o:id' => $asset->id()]],
        ]);

        $this->assertCount(0, $this->resourceAssetsByType($item->id()));
    }

    public function testCreateResourceWithCustomTypeIsAccepted(): void
    {
        $asset = $this->createAsset();

        $item = $this->createItemWithAssets([
            ['o:type' => 'custom_type', 'o:asset' => ['o:id' => $asset->id()]],
        ]);

        $resourceAssets = $this->resourceAssetsByType($item->id());

        $this->assertCount(1, $resourceAssets);
        $this->assertSame($asset->id(), $resourceAssets['custom_type']);
    }

    public function testCreateResourceKeepsOneAssetByType(): void
    {
        $asset = $this->createAsset();
        $otherAsset = $this->createAsset('Other asset');

        $item = $this->createItemWithAssets([
            ['o:type' => 'square', 'o:asset' => ['o:id' => $asset->id()]],
            ['o:type' => 'square', 'o:asset' => ['o:id' => $otherAsset->id()]],
        ]);

        $resourceAssets = $this->resourceAssetsByType($item->id());

        $this->assertCount(1, $resourceAssets);
        $this->assertSame($otherAsset->id(), $resourceAssets['square']);
    }

    public function testUpdateResourceReplacesAssets(): void
    {
        $asset = $this->createAsset();
        $otherAsset = $this->createAsset('Other asset');

        $item = $this->createItemWithAssets([
            ['o:type' => 'square', 'o:asset' => ['o:id' => $asset->id()]],
        ]);

        $this->updateItemWithAssets($item->id(), [
            ['o:type' => 'medium', 'o:asset' => ['o:id' => $otherAsset->id()]],
        ]);

        $resourceAssets = $this->resourceAssetsByType($item->id());

        $this->assertCount(1, $resourceAssets);
        $this->assertSame($otherAsset->id(), $resourceAssets['medium']);
    }

    public function testUpdateResourceRemovesAssets(): void
    {
        $asset = $this->createAsset();

        $item = $this->createItemWithAssets([
            ['o:type' => 'square', 'o:asset' => ['o:id' => $asset->id()]],
        ]);

        $this->updateItemWithAssets($item->id(), []);

        $this->assertCount(0, $this->resourceAssetsByType($item->id()));
    }

    /**
     * @return \Omeka\Api\Representation\ItemRepresentation
     */
    protected function createItemWithAssets(array $resourceAssets)
    {
        $propertyId = $this->getServiceLocator()
            ->get('Common\EasyMeta')
            ->propertyId('dcterms:title');

        $item = $this->api()->create('items', [
            'dcterms:title' => [[
                'type' => 'literal',
                'property_id' => $propertyId,
                '@value' => 'Test item',
            ]],
            'o:resource_asset' => $resourceAssets,
        ])->getContent();

        $this->createdResources[] = ['type' => 'items', 'id' => $item->id()];

        return $item;
    }

    protected function updateItemWithAssets(int $itemId, array $resourceAssets): void
    {
        $this->api()->update('items', $itemId, [
            'o:resource_asset' => $resourceAssets,
        ], [], ['isPartial' => true]);
    }

    /**
     * @return int[] Asset ids by type.
     */
    protected function resourceAssetsByType(int $resourceId): array
    {
        /** @var \AssetMulti\Api\Representation\ResourceAssetRepresentation[] $resourceAssets */
        $resourceAssets = $this->api()
            ->search('resource_assets', ['resource_id' => $resourceId])
            ->getContent();

        $result = [];
        foreach ($resourceAssets as $resourceAsset) {
            $result[$resourceAsset->type()] = $resourceAsset->asset()->id();
        }

        return $result;
    }
}
