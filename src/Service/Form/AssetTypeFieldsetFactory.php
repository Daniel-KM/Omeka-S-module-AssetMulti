<?php declare(strict_types=1);

namespace AssetMulti\Service\Form;

use AssetMulti\Form\AssetTypeFieldset;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AssetTypeFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $banners = $settings->get('assetmulti_banners') ?: [];

        return (new AssetTypeFieldset(null, $options ?? []))
            ->setAssetTypes($banners);
    }
}
