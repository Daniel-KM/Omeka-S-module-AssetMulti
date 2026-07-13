<?php declare(strict_types=1);

namespace AssetMulti\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Resource assets'; // @translate

    protected $elementGroups = [
        'resources' => 'Resource assets', // @translate
    ];

    protected $assetTypes = [];

    public function init(): void
    {
        $types = ['' => ''] + $this->assetTypes;

        $this
            ->setAttribute('id', 'asset-multi-site-fieldset')
            ->setOption('element_groups', $this->elementGroups)

            ->add([
                'name' => 'assetmulti_type_items_browse',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Complementary asset: Asset type for items browse', // @translate
                    'value_options' => $types,
                ],
                'attributes' => [
                    'id' => 'assetmulti_type_items_browse',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'assetmulti_type_items_show',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Complementary asset: Asset type for items show', // @translate
                    'value_options' => $types,
                ],
                'attributes' => [
                    'id' => 'assetmulti_type_items_show',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'assetmulti_type_media_show',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Complementary asset: Asset type for media show', // @translate
                    'value_options' => $types,
                ],
                'attributes' => [
                    'id' => 'assetmulti_type_media_show',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'assetmulti_type_item_sets_browse',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Complementary asset: Asset type for item sets browse', // @translate
                    'value_options' => $types,
                ],
                'attributes' => [
                    'id' => 'assetmulti_type_item_sets_browse',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'assetmulti_type_item_sets_show',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Complementary asset: Asset type for item sets show', // @translate
                    'value_options' => $types,
                ],
                'attributes' => [
                    'id' => 'assetmulti_type_item_sets_show',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'assetmulti_fallback_thumbnail',
                'type' => \Laminas\Form\Element\Checkbox::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Complementary asset: Fall back to resource thumbnail', // @translate
                    'info' => 'When no asset of the configured type exists, display the resource thumbnail instead.', // @translate
                ],
                'attributes' => [
                    'id' => 'assetmulti_fallback_thumbnail',
                ],
            ])
            ->add([
                'name' => 'assetmulti_type_digital_objects_show',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'element_group' => 'resources',
                    'label' => 'Complementary asset: Asset type for digital objects show', // @translate
                    'value_options' => $types,
                ],
                'attributes' => [
                    'id' => 'assetmulti_type_digital_objects_show',
                    'class' => 'chosen-select',
                ],
            ])
        ;
    }

    public function setAssetTypes(array $types): self
    {
        $this->assetTypes = $types;
        return $this;
    }
}
