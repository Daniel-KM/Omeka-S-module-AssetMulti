<?php declare(strict_types=1);

namespace AssetMulti\Form;

use Omeka\Form\Element as OmekaElement;
use Laminas\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Resource assets'; // @translate

    public function init(): void
    {
        $this
            ->setAttribute('id', 'asset-multi-fieldset')

            ->add([
                'name' => 'assetmulti_banners',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'element_group' => 'editing',
                    'label' => 'Types and labels to attach multiple assets by resource', // @translate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'id' => 'assetmulti_banners',
                    'rows' => '5',
                    'placeholder' => <<<'TXT'
                        header = Header
                        sidebar = Sidebar
                        TXT,
                ],
            ])
        ;
    }
}
