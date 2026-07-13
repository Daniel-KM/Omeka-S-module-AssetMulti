<?php declare(strict_types=1);

namespace AssetMultiTest\Api\Adapter;

use AssetMulti\Api\Representation\ResourceAssetRepresentation;
use AssetMulti\Entity\ResourceAsset;
use AssetMultiTest\AssetMultiTestTrait;
use Omeka\Api\Exception\ValidationException;
use Omeka\Test\AbstractHttpControllerTestCase;

class ResourceAssetAdapterTest extends AbstractHttpControllerTestCase
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

    public function testAdapterIsRegistered(): void
    {
        $adapter = $this->getResourceAssetAdapter();
        $this->assertSame('resource_assets', $adapter->getResourceName());
        $this->assertSame(ResourceAssetRepresentation::class, $adapter->getRepresentationClass());
        $this->assertSame(ResourceAsset::class, $adapter->getEntityClass());
    }

    public function testCreateResourceAsset(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();

        $resourceAsset = $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $this->assertNotNull($resourceAsset->id());
        $this->assertSame($item->id(), $resourceAsset->resource()->id());
        $this->assertSame($asset->id(), $resourceAsset->asset()->id());
        $this->assertSame($asset->id(), $resourceAsset->thumbnail()->id());
        $this->assertSame('square', $resourceAsset->type());
    }

    public function testCreateRequiresResource(): void
    {
        $asset = $this->createAsset();

        $this->expectException(ValidationException::class);
        $this->api()->create('resource_assets', [
            'o:asset' => ['o:id' => $asset->id()],
            'o:type' => 'square',
        ]);
    }

    public function testCreateRequiresAsset(): void
    {
        $item = $this->createItem();

        $this->expectException(ValidationException::class);
        $this->api()->create('resource_assets', [
            'o:resource' => ['o:id' => $item->id()],
            'o:type' => 'square',
        ]);
    }

    public function testCreateRequiresType(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();

        $this->expectException(ValidationException::class);
        $this->api()->create('resource_assets', [
            'o:resource' => ['o:id' => $item->id()],
            'o:asset' => ['o:id' => $asset->id()],
            'o:type' => '',
        ]);
    }

    public function testCreateRequiresUniqueTriple(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $this->expectException(ValidationException::class);
        $this->createResourceAsset($item->id(), $asset->id(), 'square');
    }

    public function testUpdateResourceAsset(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $otherAsset = $this->createAsset('Other asset');
        $resourceAsset = $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $updated = $this->api()->update('resource_assets', $resourceAsset->id(), [
            'o:resource' => ['o:id' => $item->id()],
            'o:asset' => ['o:id' => $otherAsset->id()],
            'o:type' => 'medium',
        ])->getContent();

        $this->assertSame($resourceAsset->id(), $updated->id());
        $this->assertSame($otherAsset->id(), $updated->asset()->id());
        $this->assertSame('medium', $updated->type());
    }

    public function testDeleteResourceAsset(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $resourceAsset = $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $this->api()->delete('resource_assets', $resourceAsset->id());

        $this->assertSame(0, $this->api()
            ->search('resource_assets', ['resource_id' => $item->id()])
            ->getTotalResults());
    }

    public function testDeleteResourceRemovesResourceAssets(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $this->api()->delete('items', $item->id());
        $this->createdResources = [];

        $this->assertSame(0, $this->api()
            ->search('resource_assets', ['resource_id' => $item->id()])
            ->getTotalResults());
    }

    public function testSearchByResourceId(): void
    {
        $item = $this->createItem();
        $otherItem = $this->createItem('Other item');
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');
        $this->createResourceAsset($otherItem->id(), $asset->id(), 'square');

        $this->assertSame(1, $this->api()
            ->search('resource_assets', ['resource_id' => $item->id()])
            ->getTotalResults());
        $this->assertSame(2, $this->api()
            ->search('resource_assets', ['resource_id' => [$item->id(), $otherItem->id()]])
            ->getTotalResults());
    }

    public function testSearchByEmptyResourceIdReturnsNothing(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $this->assertSame(0, $this->api()
            ->search('resource_assets', ['resource_id' => ['']])
            ->getTotalResults());
    }

    public function testSearchByAssetId(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $otherAsset = $this->createAsset('Other asset');
        $this->createResourceAsset($item->id(), $asset->id(), 'square');
        $this->createResourceAsset($item->id(), $otherAsset->id(), 'medium');

        $this->assertSame(1, $this->api()
            ->search('resource_assets', ['asset_id' => $asset->id()])
            ->getTotalResults());
    }

    public function testSearchByType(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $otherAsset = $this->createAsset('Other asset');
        $this->createResourceAsset($item->id(), $asset->id(), 'square');
        $this->createResourceAsset($item->id(), $otherAsset->id(), 'medium');

        $this->assertSame(1, $this->api()
            ->search('resource_assets', ['type' => 'square'])
            ->getTotalResults());
        $this->assertSame(2, $this->api()
            ->search('resource_assets', ['type' => ['square', 'medium']])
            ->getTotalResults());
        $this->assertSame(0, $this->api()
            ->search('resource_assets', ['type' => ['']])
            ->getTotalResults());
    }

    public function testSearchReturnScalarId(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $resourceAsset = $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $ids = $this->api()
            ->search('resource_assets', ['resource_id' => $item->id()], ['returnScalar' => 'id'])
            ->getContent();

        $this->assertSame([$resourceAsset->id()], array_values($ids));
    }

    public function testJsonLd(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $resourceAsset = $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $jsonLd = $resourceAsset->jsonSerialize();

        $this->assertSame('o:ResourceAsset', $resourceAsset->getJsonLdType());
        $this->assertSame('square', $jsonLd['o:type']);
        $this->assertSame($item->id(), $jsonLd['o:resource']['o:id']);
        $this->assertSame($asset->id(), $jsonLd['o:asset']['o:id']);
    }
}
