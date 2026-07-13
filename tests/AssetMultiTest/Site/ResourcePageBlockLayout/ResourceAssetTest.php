<?php declare(strict_types=1);

namespace AssetMultiTest\Site\ResourcePageBlockLayout;

use AssetMulti\Site\ResourcePageBlockLayout\ResourceAsset;
use Omeka\Test\AbstractHttpControllerTestCase;

class ResourceAssetTest extends AbstractHttpControllerTestCase
{
    public function testBlockIsRegistered(): void
    {
        $manager = $this->getApplication()->getServiceManager()
            ->get('Omeka\ResourcePageBlockLayoutManager');
        $this->assertTrue($manager->has('resourceAsset'));
        $this->assertInstanceOf(ResourceAsset::class, $manager->get('resourceAsset'));
    }

    public function testLabel(): void
    {
        $block = new ResourceAsset();
        $this->assertSame('Complementary asset', $block->getLabel());
    }

    public function testCompatibleResourceNames(): void
    {
        $block = new ResourceAsset();
        $names = $block->getCompatibleResourceNames();
        $this->assertContains('items', $names);
        $this->assertContains('media', $names);
        $this->assertContains('item_sets', $names);
        $this->assertContains('digital_objects', $names);
    }
}
