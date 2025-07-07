<?php declare(strict_types=1);

namespace AssetMulti\Form;

use Common\Form\Element as CommonElement;
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
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'label' => 'Type', // @translate
                    'value_options' => $this->assetTypes,
                ],
                'attributes' => [
                    'id' => 'o-resource-asset-o-type',
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
