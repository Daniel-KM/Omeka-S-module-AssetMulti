<?php declare(strict_types=1);

namespace AssetMulti;

return [
    'api_adapters' => [
        'invokables' => [
            'resource_assets' => Api\Adapter\ResourceAssetAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'assetResource' => View\Helper\AssetResource::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
        ],
        'factories' => [
            Form\AssetTypeFieldset::class => Service\Form\AssetTypeFieldsetFactory::class,
            Form\SiteSettingsFieldset::class => Service\Form\SiteSettingsFieldsetFactory::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'resourceAsset' => Site\ResourcePageBlockLayout\ResourceAsset::class,
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => \Laminas\I18n\Translator\Loader\Gettext::class,
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'assetmulti' => [
        'site_settings' => [
            'assetmulti_fallback_thumbnail' => false,
            'assetmulti_type_items_browse' => '',
            'assetmulti_type_items_show' => '',
            'assetmulti_type_media_show' => '',
            'assetmulti_type_item_sets_browse' => '',
            'assetmulti_type_item_sets_show' => '',
            'assetmulti_type_digital_objects_show' => '',
        ],
        'settings' => [
            'assetmulti_banners' => [
                // These types are the default ones, they can be replaced.
                'home' => 'Home', // @translate
                'item_set' => 'Item set page', // @translate
                'results' => 'Search results', // @translate
                // Omeka sizes, to be more consistent when a resource has a
                // specific thumbnail.
                'large' => 'Large', // @translate
                // Use "Midsized" to avoid issue with translation of "medium".
                'medium' => 'Midsized', // @translate
                'square' => 'Square', // @translate
                /*
                // Web page sizes.
                'home' => 'Home', // @translate
                'header' => 'Header', // @translate
                'sidebar' => 'Sidebar', // @translate
                'footer' => 'Footer', // @translate
                // Ad sizes.
                'leaderboard' => 'Leaderboard', // @translate
                'large_rectangle' => 'Large rectangle', // @translate
                'medium_rectangle' => 'Medium rectangle', // @translate
                'wide_skyscraper' => 'Wide skyscraper', // @translate
                'skyscraper' => 'Skyscraper', // @translate
                'mobile_banner' => 'Mobile banner', // @translate
                */
            ],
        ],
    ],
];
