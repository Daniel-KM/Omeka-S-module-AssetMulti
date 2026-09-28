Asset Multi (module for Omeka S)
================================

> __New versions of this module and support for Omeka S version 3.0 and above
> are available on [GitLab], which seems to respect users and privacy better
> than the previous repository.__

[Asset Multi] is a module for [Omeka S] that allows to attach multiple assets
(thumbnails) to resources, for example to display an horizontal banner in home
page, a skyscraper in another page and a square in lists.

No derivative thumbnails are created: each asset should be attached manually or
via import to resources.

For now, the theme should be adapted to be display specific thumbnails, because
Omeka manages only one thumbnail by resource.


Installation
------------

See general end user documentation for [installing a module].

This module requires the module [Common], that should be installed first.

* From the zip

Download the last release [AssetMulti.zip] from the list of releases, and
uncompress it in the `modules` directory.

* From the source and for development

If the module was installed from the source, rename the name of the folder of
the module to `AssetMulti`.

Then install it like any other Omeka module and follow the config instructions.

* For test

The module includes a comprehensive test suite with unit and functional tests.
Run them from the root of Omeka:

```sh
vendor/bin/phpunit -c modules/AssetMulti/phpunit.xml --testdox
```


Usage
-----

First, set the list of types in main settings. A default list is provided, but
it can be replaced by any pair of type/label. The types "default" and "original"
are reserved for future purpose.

Add new assets in the tab "Advanced" of the resource (item, item set, media,
[digital object], or [thesaurus] concept).
Warning: a resource can only have one asset by type.

To use them in themes for now, the theme should be adapted. So use the view
helper "assetResource":

```php
// In most of the cases, a specific asset is needed.
$asset = $this->assetResource($resource, $type);
// Or if there are no or multiple types, output is an array ordered by type:
$assets = $this->assetResource($resource, $types);
// When there are multiple resources and one type, the output is a list of ResourceAssets, not Assets.
$resourceAssets = $this->assetResource($resources, $type);
// When there are multiple resources and no or multiple types, the output is a list of ResourceAssets by type.
$resourceAssetsByType = $this->assetResource($resources, $types);
```

This view helper is just a wrapper to the api. So it is possible to search all
resources with the same asset, or all assets of a resource, or all assets with a
specific type, etc., via the api name `resource_assets`:

```php
$resourceAssets = $this->api()->search('resource_assets', ['type' => 'home'])->getContent();
foreach ($resourceAssets as $resourceAsset) {
    $asset = $resourceAsset->asset();
    // …
}
```


TODO
----

- [x] Add resource page block.
- [x] Add site settings to configure which asset type to display per page context.
- [x] Use an open list of types in the advanced tab, so the user can add any specific type for an asset.
- [x] Display a link to all resources with a specific assets in admin / assets.
- [-] Allow multiple assets by resource with the same type. No: the use cases are a lot less than one asset by type.
- [-] Store default asset (resource thumbnail id) with other assets with type "default" or "original". Useless: it will create duplicate for o:thumbnail.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [module issues] page on GitLab.


License
-------

This module is published under the [CeCILL v2.1] license, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

This software is governed by the CeCILL license under French law and abiding by
the rules of distribution of free software. You can use, modify and/ or
redistribute the software under the terms of the CeCILL license as circulated by
CEA, CNRS and INRIA at the following URL "http://www.cecill.info".

As a counterpart to the access to the source code and rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors have only limited liability.

In this respect, the user’s attention is drawn to the risks associated with
loading, using, modifying and/or developing or reproducing the software by the
user in light of its specific status of free software, that may mean that it is
complicated to manipulate, and that also therefore means that it is reserved for
developers and experienced professionals having in-depth computer knowledge.
Users are therefore encouraged to load and test the software’s suitability as
regards their requirements in conditions enabling the security of their systems
and/or data to be ensured and, more generally, to use and operate it in the same
conditions as regards security.

The fact that you are presently reading this means that you have had knowledge
of the CeCILL license and that you accept its terms.


Copyright
---------

* Copyright Daniel Berthereau, 2024-2026 (see [Daniel-KM] on GitLab)

This module was built for the migration of the digital library [Collections]
of the [Musée de Bretagne], previously under a non-free software.


[Asset Multi]: https://gitlab.com/Daniel-KM/Omeka-S-module-AssetMulti
[Omeka S]: https://omeka.org/s
[installing a module]: https://omeka.org/s/docs/user-manual/modules/
[AssetMulti.zip]: https://gitlab.com/Daniel-KM/Omeka-S-module-AssetMulti/-/releases
[module issues]: https://gitlab.com/Daniel-KM/Omeka-S-module-AssetMulti/-/work_items
[Common]: https://gitlab.com/Daniel-KM/Omeka-S-module-Common
[digital object]: https://gitlab.com/Daniel-KM/Omeka-S-module-DigitalObject
[thesaurus]: https://gitlab.com/Daniel-KM/Omeka-S-module-Thesaurus
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Collections]: http://collections.musee-bretagne.fr
[Musée de Bretagne]: https://musee-bretagne.fr
[GitLab]: https://gitlab.com/Daniel-KM
[Daniel-KM]: https://gitlab.com/Daniel-KM "Daniel Berthereau"
