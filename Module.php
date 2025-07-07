<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Berthereau
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software. You can use, modify and/or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software’s author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user’s attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software’s suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace AssetMulti;

if (!class_exists('Common\TraitModule', false)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\Stdlib\PsrMessage;
use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Omeka\Module\AbstractModule;

/**
 * Asset Multi.
 *
 * @copyright Daniel Berthereau, 2024-2025
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    protected $dependencies = [
        'Common',
    ];

    /**
     * @var bool
     */
    protected $isBatchUpdate;

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translate = $services->get('ControllerPluginManager')->get('translate');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.70')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.70'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }
    }

    protected function postInstall(): void
    {
        /**
         * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
         */
        $services = $this->getServiceLocator();
        $url = $services->get('ViewHelperManager')->get('url');
        $translator = $services->get('MvcTranslator');
        $messenger = $services->get('ControllerPluginManager')->get('messenger');

        $html = <<<HTML
        <p>{message_1}</p>
        <ul>
            <li>{message_2}</li>
            <li>{message_3}</li>
            <li>{message_4}</li>
        </ul>
        HTML;
        $context = [];
        $context['message_1'] = new PsrMessage('To get multiple assets for a resource:'); // @translate
        $context['message_2'] = new PsrMessage(
            'First, define wanted asset type names in {link}main settings{link_end}.', // @translate
            [
                'link' => sprintf('<a href="%s">', $url('admin/default', ['controller' => 'setting', 'action' => 'browse'], ['fragment' => 'resource'])),
                'link_end' => '</a>',
            ]
        );
        $context['message_3'] = new PsrMessage(
            'Second, add new assets to resources in the tab "advanced" of the {link}resource form{link_end}.', // @translate
            [
                'link' => sprintf('<a href="%s">', $url('admin/default', ['controller' => 'item', 'action' => 'add'], ['fragment' => 'advanced-settings'])),
                'link_end' => '</a>',
            ]
        );
        $context['message_4'] = new PsrMessage(
            'Third, adapt the site theme to use new resource assets.' // @translate
        );
        // Translate early because it is stored in session.
        $messenger->addSuccess((new PsrMessage($html, array_map(fn ($v) => $v->setEscapeHtml(false)->setTranslator($translator)->translate(), $context)))->setEscapeHtml(false));
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $adaptersAndControllers = [
            \Omeka\Api\Adapter\ItemAdapter::class => 'Omeka\Controller\Admin\Item',
            \Omeka\Api\Adapter\MediaAdapter::class => 'Omeka\Controller\Admin\Media',
            \Omeka\Api\Adapter\ItemSetAdapter::class => 'Omeka\Controller\Admin\ItemSet',
            // \Annotate\Api\Adapter\AnnotationAdapter::class => \Annotate\Controller\Admin\AnnotationController::class,
        ];
        foreach ($adaptersAndControllers as $adapter => $controller) {
            // Avoid to do something during batch process.
            $sharedEventManager->attach(
                \Omeka\Api\Adapter\ItemAdapter::class,
                'api.batch_update.pre',
                [$this, 'preBatchUpdateResource'],
                -100
            );

            $sharedEventManager->attach(
                $adapter,
                'api.create.post',
                [$this, 'handleCreateUpdateResource']
            );
            $sharedEventManager->attach(
                $adapter,
                'api.update.post',
                [$this, 'handleCreateUpdateResource']
            );

            // Display the form in advanced tab.
            $sharedEventManager->attach(
                $controller,
                'view.add.form.advanced',
                [$this, 'addAdvancedTabElements']
            );
            $sharedEventManager->attach(
                $controller,
                'view.edit.form.advanced',
                [$this, 'addAdvancedTabElements']
            );

            /*
            $sharedEventManager->attach(
                $controller,
                'view.details',
                [$this, 'handleResourceDetails']
            );
            $sharedEventManager->attach(
                $controller,
                'view.show.sidebar',
                [$this, 'handleResourceSidebar']
            );
            */
        }
        // Handle main settings.
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
    }

    public function preBatchUpdateResource(Event $event): void
    {
        $this->isBatchUpdate = true;
    }

    /**
     * Create resource assets according to resource add request.
     */
    public function handleCreateUpdateResource(Event $event): void
    {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         * @var \Omeka\Api\Request $request
         * @var \Omeka\Entity\Resource $resource
         * @var \Omeka\Api\Manager $api
         * @var \Omeka\Settings\Settings $settings
         * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
         */
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $settings = $services->get('Omeka\Settings');
        $messenger = $services->get('ControllerPluginManager')->get('messenger');

        $request = $event->getParam('request');
        $resourceData = $request->getContent();

        $banners = $settings->get('assetmulti_banners') ?: [];
        $banners = array_fill_keys(array_keys($banners), null);

        // This is an api-post event, so id is ready and checks are done.
        $resource = $event->getParam('response')->getContent();

        // Add new assets and remove old ones from the resource.
        $newAssets = $resourceData['o:resource_asset'] ?? [];

        // Clean request.
        // First, remove asset type not filled to avoid useless message.
        $newAssets = array_filter($newAssets, fn ($v) => !empty($v['o:type']) || !empty($v['o:asset']['o:id']));
        $count = count($newAssets);
        // Second, remove missing type or missing asset.
        $newAssets = array_filter($newAssets, fn ($v) => !empty($v['o:type']) && !empty($v['o:asset']['o:id']));
        // Third, keep only one asset by type.
        $newAssets = array_column(array_map(fn ($v) => ['type' => $v['o:type'], 'assetId' => (int) $v['o:asset']['o:id']], $newAssets), 'assetId', 'type');
        if (count($newAssets) !== $count) {
            $messenger->addWarning(new PsrMessage(
                'Some complementary assets were removed because the type is missing or duplicated.') // @translate
            );
        }
        // Fourth, keep only defined banners. And set order defined in settings.
        $tmpAssets = $newAssets;
        $newAssets = array_filter(array_replace($banners, $newAssets));
        if (count($newAssets) !== count($tmpAssets)) {
            $messenger->addWarning(new PsrMessage(
                'Some complementary assets were removed because the types {types} are not in the list of allowed types.', // @translate
                ['types' => implode(', ', array_keys(array_diff_key($tmpAssets, $banners)))]
            ));
        }

        // Fill all resource assets independantly of the existing ones in order
        // to keep ids, removing the remaining or creating new ones.
        $resourceId = method_exists($resource, 'id') ? $resource->id() : $resource->getId();
        $existingResourceAssetIds = $api->search('resource_assets', ['resource_id' => $resourceId], ['returnScalar' => 'id'])->getContent();
        $exception = null;
        foreach ($newAssets as $type => $assetId) {
            $data = [
                'o:resource' => ['o:id' => $resourceId],
                'o:asset' => ['o:id' => $assetId],
                'o:type' => $type,
            ];
            if (count($existingResourceAssetIds)) {
                $resourceAssetId = array_shift($existingResourceAssetIds);
                // A batch update is not possible because all values are
                // different.
                try {
                    $api->update('resource_assets', $resourceAssetId, $data);
                } catch (\Exception $e) {
                    $exception = $e;
                }
            } else {
                // A batch create is only a loop on create, so useless here
                // because the number or thumbnail is small in real cases.
                // It should work anyway with any number of assets.
                try {
                    $api->create('resource_assets', $data);
                } catch (\Exception $e) {
                    $exception = $e;
                }
            }
        }

        if ($existingResourceAssetIds) {
            // For the same reason, the batch delete is useless.
            foreach ($existingResourceAssetIds as $resourceAssetId) {
                try {
                    $api->delete('resource_assets', $resourceAssetId);
                } catch (\Exception $e) {
                    // Already removed.
                }
            }
        }

        if ($exception) {
            // It should never occur, so add a log.
            $messenger->addError(new PsrMessage(
                'Some complementary assets were not attached. Check data or ask an administrator. {msg}', // @translate
                ['msg' => $exception->getMessage()]
            ));
            $services->get('Omeka\Logger')->err(
                '[Asset Multi] Some complementary assets were not attached: {msg}', // @translate
                ['msg' => $exception->getMessage()]
            );
        }
    }

    public function addAdvancedTabElements(Event $event): void
    {
        /**
         * @var \Laminas\View\Renderer\PhpRenderer $view
         * @var \Omeka\Api\Manager $api
         * @var \Omeka\Settings\Settings $settings
         */
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $settings = $services->get('Omeka\Settings');

        $banners = $settings->get('assetmulti_banners') ?: [];
        if (!$banners) {
            return;
        }

        $view = $event->getTarget();

        $resource = $view->resource;
        $resourceId = method_exists($resource, 'id') ? $resource->id() : $resource->getId();

        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/asset-multi.css', 'AssetMulti'));
        $view->headScript()
            ->appendFile($assetUrl('js/asset-multi.js', 'AssetMulti'), 'text/javascript', ['defer' => 'defer']);

        /** @var \AssetMulti\Api\Representation\ResourceAssetRepresentation[] $resourceAssets */
        $resourceAssets = $api->search('resource_assets', ['resource_id' => $resourceId])->getContent();
        $values = [];
        $index = 0;
        foreach ($resourceAssets as $resourceAsset) {
            $values[] = [
                // 'o:type' => $resourceAsset->type(),
                // 'o:asset' => ['o:id' => $resourceAsset->asset()->id()],
                'o:resource_asset[' . $index . '][o:type]' => $resourceAsset->type(),
                'o:resource_asset[' . $index . '][o:asset][o:id]' => $resourceAsset->asset()->id(),
            ];
            ++$index;
        }

        /**
         * @var \Laminas\Form\Element\Collection $collection
         * @var \AssetMulti\Form\AssetTypeFieldset $assetTypeFieldset
         *
         * @see \SingleSignOn\Form\ConfigForm
         */
        $formManager = $services->get('FormElementManager');
        $assetTypeFieldset = $formManager->get(\AssetMulti\Form\AssetTypeFieldset::class);
        $collection = $formManager->get(\Laminas\Form\Element\Collection::class)
            ->setName('o:resource_asset')
            ->setOptions([
                'label' => 'Complementary thumbnails', // @ŧranslate
                'count' => count($values),
                'allow_add' => true,
                'allow_remove' => true,
                'should_create_template' => true,
                'template_placeholder' => '__index__',
                'create_new_objects' => true,
                'target_element' => $assetTypeFieldset,
            ])
            ->setAttributes([
                'id' => 'o-resource_asset',
                'required' => false,
                'class' => 'form-fieldset-collection fieldset-resource-assets',
                'data-label-index' => $view->translate('Complementary thumbnail {index}'), // @ŧranslate
            ]);

        $collection
            ->populateValues($values);

        // The placeholders are not converted here, because the collection is
        // not rendered with the form. The same, the values are not really
        // populated.
        // TODO Find the right laminas way to fill a partial collection.
        $index = 0;
        $vals = array_merge(...$values);
        foreach ($collection->getFieldsets() as $fieldset) {
            foreach ($fieldset->getElements() as $element) {
                $name = strtr($element->getName(), ['__index__' => $index]);
                $element
                    ->setName($name)
                    ->setValue($vals[$name] ?? null);
            }
            ++$index;
        }

        echo $view->formCollection($collection, true);
    }
}
