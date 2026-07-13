<?php declare(strict_types=1);

namespace AssetMulti\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class AssetTypeFieldset extends Fieldset
{
    // protected $label = 'Complementary assets {index}}'; // @translate

    protected $assetTypes = [];

    public function init(): void
    {
        $this
            ->setName('azerty')
            ->setAttribute('id', 'asset-type')
            ->setAttribute('class', 'form-collection-target asset-type-fieldset')

            // Use the full name because it is called from view, not from form.

            ->add([
                'name' => 'o:resource_asset[__index__][o:type]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Type', // @translate
                ],
                'attributes' => [
                    'id' => 'o-resource-asset-o-type',
                    'list' => 'assetmulti-types',
                    'placeholder' => 'Type',
                ],
            ])
            ->add([
                'name' => 'o:resource_asset[__index__][o:asset][o:id]',
                'type' => OmekaElement\Asset::class,
                'options' => [
                    'label' => 'Asset', // @translate
                ],
                'attributes' => [
                    'id' => 'o-resource-o-asset-o-id',
                ],
            ]);
    }

    public function setAssetTypes(array $types): self
    {
        $this->assetTypes = $types;
        return $this;
    }
}
