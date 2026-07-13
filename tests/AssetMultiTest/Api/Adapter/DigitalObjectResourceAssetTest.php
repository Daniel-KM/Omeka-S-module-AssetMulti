<?php declare(strict_types=1);

namespace AssetMultiTest\Api\Adapter;

use AssetMultiTest\AssetMultiTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Cover the support of the resource type digital object.
 */
class DigitalObjectResourceAssetTest extends AbstractHttpControllerTestCase
{
    use AssetMultiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        if (!$this->hasDigitalObject()) {
            $this->markTestSkipped('Module DigitalObject not installed.');
        }
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        $this->logout();
        parent::tearDown();
    }

    public function testCreateResourceAssetForDigitalObject(): void
    {
        $digitalObject = $this->createDigitalObject();
        $asset = $this->createAsset();

        $resourceAsset = $this->createResourceAsset($digitalObject->id(), $asset->id(), 'square');

        $this->assertSame($digitalObject->id(), $resourceAsset->resource()->id());
        $this->assertInstanceOf(
            \DigitalObject\Api\Representation\DigitalObjectRepresentation::class,
            $resourceAsset->resource()
        );
        $this->assertSame($asset->id(), $resourceAsset->asset()->id());
    }

    public function testSearchResourceAssetsOfDigitalObject(): void
    {
        $digitalObject = $this->createDigitalObject();
        $asset = $this->createAsset();
        $this->createResourceAsset($digitalObject->id(), $asset->id(), 'square');

        $this->assertSame(1, $this->api()
            ->search('resource_assets', ['resource_id' => $digitalObject->id()])
            ->getTotalResults());
    }

    public function testAssetResourceHelperWithDigitalObject(): void
    {
        $digitalObject = $this->createDigitalObject();
        $asset = $this->createAsset();
        $this->createResourceAsset($digitalObject->id(), $asset->id(), 'square');

        $result = $this->getServiceLocator()
            ->get('ViewRenderer')
            ->plugin('assetResource')
            ->__invoke($digitalObject, 'square');

        $this->assertSame($asset->id(), $result->id());
    }

    public function testListenersAreAttachedToDigitalObjectAdapter(): void
    {
        $listeners = $this->getApplication()
            ->getEventManager()
            ->getSharedManager()
            ->getListeners([\DigitalObject\Api\Adapter\DigitalObjectAdapter::class], 'api.create.post');

        $module = new \AssetMulti\Module();
        $found = false;
        foreach ($listeners as $listenersByPriority) {
            foreach ($listenersByPriority as $listener) {
                if (is_array($listener)
                    && ($listener[0] ?? null) instanceof \AssetMulti\Module
                    && $listener[1] === 'handleCreateUpdateResource'
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found, 'No listener of AssetMulti on the digital object adapter.');
        $this->assertInstanceOf(\AssetMulti\Module::class, $module);
    }
}
