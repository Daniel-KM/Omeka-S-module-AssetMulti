<?php declare(strict_types=1);

namespace AssetMulti\Service\Form;

use AssetMulti\Form\SiteSettingsFieldset;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SiteSettingsFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $banners = $settings->get('assetmulti_banners') ?: [];

        return (new SiteSettingsFieldset(null, $options ?? []))
            ->setAssetTypes($banners);
    }
}
