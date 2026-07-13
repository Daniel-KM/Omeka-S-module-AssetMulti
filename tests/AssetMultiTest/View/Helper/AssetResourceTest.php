<?php declare(strict_types=1);

namespace AssetMultiTest\View\Helper;

use AssetMulti\Api\Representation\ResourceAssetRepresentation;
use AssetMulti\View\Helper\AssetResource;
use AssetMultiTest\AssetMultiTestTrait;
use Omeka\Api\Representation\AssetRepresentation;
use Omeka\Test\AbstractHttpControllerTestCase;

class AssetResourceTest extends AbstractHttpControllerTestCase
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

    public function testHelperIsRegistered(): void
    {
        $viewHelpers = $this->getServiceLocator()->get('ViewHelperManager');
        $this->assertTrue($viewHelpers->has('assetResource'));
        $this->assertInstanceOf(AssetResource::class, $viewHelpers->get('assetResource'));
    }

    public function testWithoutResourceAndTypeReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->assetResource());
    }

    public function testSingleResourceAndSingleTypeReturnsAsset(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $result = $this->assetResource($item, 'square');

        $this->assertInstanceOf(AssetRepresentation::class, $result);
        $this->assertSame($asset->id(), $result->id());
    }

    public function testSingleResourceAndMissingTypeReturnsNull(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $this->assertNull($this->assetResource($item, 'medium'));
    }

    public function testSingleResourceWithoutTypeReturnsAssetsByType(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $otherAsset = $this->createAsset('Other asset');
        $this->createResourceAsset($item->id(), $asset->id(), 'square');
        $this->createResourceAsset($item->id(), $otherAsset->id(), 'medium');

        $result = $this->assetResource($item->id());

        $this->assertSame(['medium', 'square'], array_keys($result));
        $this->assertSame($asset->id(), $result['square']->id());
        $this->assertSame($otherAsset->id(), $result['medium']->id());
    }

    public function testSingleResourceWithTypesKeepsOrderAndEmptyTypes(): void
    {
        $item = $this->createItem();
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');

        $result = $this->assetResource($item, ['medium', 'square']);

        $this->assertSame(['medium', 'square'], array_keys($result));
        $this->assertNull($result['medium']);
        $this->assertSame($asset->id(), $result['square']->id());
    }

    public function testMultipleResourcesAndSingleTypeReturnsResourceAssets(): void
    {
        $item = $this->createItem();
        $otherItem = $this->createItem('Other item');
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');
        $this->createResourceAsset($otherItem->id(), $asset->id(), 'square');

        $result = $this->assetResource([$item, $otherItem], 'square');

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(ResourceAssetRepresentation::class, $result);
    }

    public function testMultipleResourcesAndMultipleTypesReturnsResourceAssetsByType(): void
    {
        $item = $this->createItem();
        $otherItem = $this->createItem('Other item');
        $asset = $this->createAsset();
        $this->createResourceAsset($item->id(), $asset->id(), 'square');
        $this->createResourceAsset($otherItem->id(), $asset->id(), 'medium');

        $result = $this->assetResource([$item->id(), $otherItem->id()], ['square', 'medium', 'large']);

        $this->assertSame(['square', 'medium', 'large'], array_keys($result));
        $this->assertCount(1, $result['square']);
        $this->assertCount(1, $result['medium']);
        $this->assertCount(0, $result['large']);
    }

    /**
     * @return mixed
     */
    protected function assetResource($resource = null, $type = null)
    {
        return $this->getServiceLocator()
            ->get('ViewRenderer')
            ->plugin('assetResource')
            ->__invoke($resource, $type);
    }
}
