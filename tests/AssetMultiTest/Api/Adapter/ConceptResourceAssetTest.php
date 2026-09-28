<?php declare(strict_types=1);

namespace AssetMultiTest\Api\Adapter;

use AssetMultiTest\AssetMultiTestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Cover the support of the resource type concept, from module Thesaurus.
 */
class ConceptResourceAssetTest extends AbstractHttpControllerTestCase
{
    use AssetMultiTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        if (!$this->hasThesaurus()) {
            $this->markTestSkipped('Module Thesaurus not installed.');
        }
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        $this->logout();
        parent::tearDown();
    }

    public function testCreateResourceAssetForConcept(): void
    {
        $concept = $this->createConcept();
        $asset = $this->createAsset();

        $resourceAsset = $this->createResourceAsset($concept->id(), $asset->id(), 'square');

        $this->assertSame($concept->id(), $resourceAsset->resource()->id());
        $this->assertInstanceOf(
            \Thesaurus\Api\Representation\ConceptRepresentation::class,
            $resourceAsset->resource()
        );
        $this->assertSame($asset->id(), $resourceAsset->asset()->id());
    }

    public function testSearchResourceAssetsOfConcept(): void
    {
        $concept = $this->createConcept();
        $asset = $this->createAsset();
        $this->createResourceAsset($concept->id(), $asset->id(), 'square');

        $this->assertSame(1, $this->api()
            ->search('resource_assets', ['resource_id' => $concept->id()])
            ->getTotalResults());
    }

    public function testAssetResourceHelperWithConcept(): void
    {
        $concept = $this->createConcept();
        $asset = $this->createAsset();
        $this->createResourceAsset($concept->id(), $asset->id(), 'square');

        $result = $this->getServiceLocator()
            ->get('ViewRenderer')
            ->plugin('assetResource')
            ->__invoke($concept, 'square');

        $this->assertSame($asset->id(), $result->id());
    }

    public function testListenersAreAttachedToConceptAdapter(): void
    {
        $listeners = $this->getApplication()
            ->getEventManager()
            ->getSharedManager()
            ->getListeners([\Thesaurus\Api\Adapter\ConceptAdapter::class], 'api.create.post');

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

        $this->assertTrue($found, 'No listener of AssetMulti on the concept adapter.');
    }
}
