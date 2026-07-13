<?php declare(strict_types=1);

namespace AssetMultiTest;

use AssetMulti\Api\Representation\ResourceAssetRepresentation;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AssetRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Entity\Asset;

trait AssetMultiTestTrait
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array
     */
    protected $createdResources = [];

    /**
     * @var int[]
     */
    protected $createdAssets = [];

    /**
     * @var int[]
     */
    protected $createdDigitalObjects = [];

    protected function api(): ApiManager
    {
        return $this->getServiceLocator()->get('Omeka\ApiManager');
    }

    protected function getServiceLocator(): ServiceLocatorInterface
    {
        if ($this->services === null) {
            $this->services = $this->getApplication()->getServiceManager();
        }
        return $this->services;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    protected function getSettings(): \Omeka\Settings\Settings
    {
        return $this->getServiceLocator()->get('Omeka\Settings');
    }

    protected function getResourceAssetAdapter(): \AssetMulti\Api\Adapter\ResourceAssetAdapter
    {
        return $this->getServiceLocator()
            ->get('Omeka\ApiAdapterManager')
            ->get('resource_assets');
    }

    protected function loginAdmin(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter();
        $adapter->setIdentity('admin@example.com');
        $adapter->setCredential('root');
        $auth->authenticate();
    }

    protected function logout(): void
    {
        $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')
            ->clearIdentity();
    }

    /**
     * @return \Omeka\Entity\User|null
     */
    protected function getCurrentUser()
    {
        return $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')
            ->getIdentity();
    }

    protected function createItem(string $title = 'Test item'): ItemRepresentation
    {
        $propertyId = $this->getServiceLocator()
            ->get('Common\EasyMeta')
            ->propertyId('dcterms:title');

        $response = $this->api()->create('items', [
            'dcterms:title' => [[
                'type' => 'literal',
                'property_id' => $propertyId,
                '@value' => $title,
            ]],
        ]);

        $item = $response->getContent();
        $this->createdResources[] = ['type' => 'items', 'id' => $item->id()];

        return $item;
    }

    /**
     * Create an asset directly with the entity manager: no real file is needed
     * to attach an asset to a resource.
     */
    protected function createAsset(string $name = 'Test asset'): AssetRepresentation
    {
        $entityManager = $this->getEntityManager();

        $asset = new Asset();
        $asset->setName($name);
        $asset->setMediaType('image/png');
        $asset->setStorageId(bin2hex(random_bytes(10)));
        $asset->setExtension('png');
        $asset->setOwner($this->getCurrentUser());

        $entityManager->persist($asset);
        $entityManager->flush();

        $this->createdAssets[] = $asset->getId();

        return $this->getServiceLocator()
            ->get('Omeka\ApiAdapterManager')
            ->get('assets')
            ->getRepresentation($asset);
    }

    protected function createResourceAsset(
        int $resourceId,
        int $assetId,
        string $type
    ): ResourceAssetRepresentation {
        return $this->api()->create('resource_assets', [
            'o:resource' => ['o:id' => $resourceId],
            'o:asset' => ['o:id' => $assetId],
            'o:type' => $type,
        ])->getContent();
    }

    protected function hasDigitalObject(): bool
    {
        return class_exists('DigitalObject\Module', false);
    }

    /**
     * Create a digital object directly with the entity manager: the file is not
     * required to attach assets.
     *
     * @return \DigitalObject\Api\Representation\DigitalObjectRepresentation
     */
    protected function createDigitalObject(string $title = 'Test digital object')
    {
        $entityManager = $this->getEntityManager();
        $easyMeta = $this->getServiceLocator()->get('Common\EasyMeta');

        $digitalObject = new \DigitalObject\Entity\DigitalObject();
        $digitalObject->setOwner($this->getCurrentUser());
        $digitalObject->setIsPublic(true);
        $digitalObject->setCreated(new \DateTime('now'));
        $entityManager->persist($digitalObject);
        $entityManager->flush();

        $value = new \Omeka\Entity\Value();
        $value->setResource($digitalObject);
        $value->setProperty($entityManager->find(\Omeka\Entity\Property::class, $easyMeta->propertyId('dcterms:title')));
        $value->setType('literal');
        $value->setValue($title);
        $value->setIsPublic(true);
        $entityManager->persist($value);
        $entityManager->flush();

        $this->createdDigitalObjects[] = $digitalObject->getId();

        return $this->getServiceLocator()
            ->get('Omeka\ApiAdapterManager')
            ->get('digital_objects')
            ->getRepresentation($digitalObject);
    }

    protected function cleanupResources(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->clear();
        $connection = $entityManager->getConnection();

        foreach ($this->createdResources as $resource) {
            try {
                $this->api()->delete($resource['type'], $resource['id']);
            } catch (\Exception $e) {
                // Already removed.
            }
        }
        $this->createdResources = [];

        foreach ($this->createdDigitalObjects as $digitalObjectId) {
            try {
                $connection->executeStatement('DELETE FROM `value` WHERE `resource_id` = ?', [$digitalObjectId]);
                $connection->executeStatement('DELETE FROM `resource` WHERE `id` = ?', [$digitalObjectId]);
            } catch (\Exception $e) {
                // Already removed.
            }
        }
        $this->createdDigitalObjects = [];

        foreach ($this->createdAssets as $assetId) {
            try {
                $connection->executeStatement('DELETE FROM `asset` WHERE `id` = ?', [$assetId]);
            } catch (\Exception $e) {
                // Already removed.
            }
        }
        $this->createdAssets = [];

        $entityManager->clear();
        $this->reloadIdentity();
    }

    /**
     * Reset the authenticated identity after a clear of the entity manager.
     */
    protected function reloadIdentity(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $identity = $auth->getIdentity();
        if (!$identity) {
            return;
        }
        $user = $this->getEntityManager()->find(\Omeka\Entity\User::class, $identity->getId());
        if ($user) {
            $auth->getStorage()->write($user);
        }
    }
}
