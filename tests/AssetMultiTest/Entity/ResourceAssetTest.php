<?php declare(strict_types=1);

namespace AssetMultiTest\Entity;

use AssetMulti\Entity\ResourceAsset;
use Omeka\Entity\Asset;
use Omeka\Entity\Item;
use PHPUnit\Framework\TestCase;

class ResourceAssetTest extends TestCase
{
    /**
     * @var ResourceAsset
     */
    protected $resourceAsset;

    public function setUp(): void
    {
        $this->resourceAsset = new ResourceAsset();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->resourceAsset->getId());
        $this->assertNull($this->resourceAsset->getResource());
        $this->assertNull($this->resourceAsset->getAsset());
        $this->assertNull($this->resourceAsset->getType());
    }

    public function testSetResource(): void
    {
        $item = new Item();
        $this->assertSame($this->resourceAsset, $this->resourceAsset->setResource($item));
        $this->assertSame($item, $this->resourceAsset->getResource());
    }

    public function testSetAsset(): void
    {
        $asset = new Asset();
        $this->assertSame($this->resourceAsset, $this->resourceAsset->setAsset($asset));
        $this->assertSame($asset, $this->resourceAsset->getAsset());
    }

    public function testSetType(): void
    {
        $this->assertSame($this->resourceAsset, $this->resourceAsset->setType('square'));
        $this->assertSame('square', $this->resourceAsset->getType());
    }
}
