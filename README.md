# Bitrix iBlock Builder
Builder classes for bitrix's iBlock and iSection

## How to install
`composer require polyspirit/bitrix-builder`

## How to use
### Create instance of IBlock class
```php
use \polyspirit\Bitrix\Builder\IBlock;

// set iBlock ID as first parameter
$iBlockById = new IBlock(12);

// iBlock CODE as first parameter
$iBlockByCode = new IBlock('news');

// iBlock CODE as first parameter and site ID as second parameter
$iBlockByCodeAndSiteID = new IBlock('news', 's1');
```
### Get list of elements
```php
$iBlock = new IBlock('news');
$arResult = $iBlock->getElements();
```
### Get element detail
```php
$iBlock = new IBlock('news');
$arResultDetail = $iBlock->filter(['ID' => 42])->getElement();
```
Every result's element has a PROPS property, which contains all element's properties.
```php
// show SOME_CODE property value
echo $arResultDetail['PROPS']['SOME_CODE']['VALUE'];
```
Also every result's element has a PICTURE_SRC property, which contains path to DETAIL_PICTURE by default and PREVIEW_PICTURE if DETAIL_PICTURE not in result's fields.
```php
// show path to picture
echo $arResultDetail['PICTURE_SRC']; // /upload/resize_cache/iblock/xxx/xxx_xxx_1/some_picture.ext
```

## Methods

### FILTER, SORT AND ETC.

#### IBlock::active
Get only active elements.
```php
public IBlock::active(): IBlock
```
```php
// example: 
$iBlock->active()->getElements();
```

#### IBlock::sort
Merge sort by default with your array.
```php
public IBlock::sort(array $array): IBlock
```
```php
// sort by default: 
['sort' => 'ASC', 'date_active_from' => 'DESC', 'created_date' => 'DESC']
```
```php
// example: 
$iBlock->sort(['sort' => 'DESC', 'ID' => 'DESC'])->getElements();
```

#### IBlock::sortReset
Reset default sort value to empty array.
```php
public IBlock::sortReset(): IBlock
```
```php
// example: 
$iBlock->sortReset()->sort(['ID' => 'DESC'])->getElements();
```

#### IBlock::filter
Filter result's elements.
```php
public IBlock::filter(array $array): IBlock
```
```php
// example:
$iBlock->filter(['>=TIMESTAMP_X' => date('Y-m-d h:i:s', 'yesterday')])->getElements();
```

#### IBlock::fields
Merge fields by default with your array.
If you don't use this method - all fields will be selected. 
```php
public IBlock::fields(array $array): IBlock
```
```php
// fields by default: 
['ID', 'IBLOCK_ID']

// fields by default if method is not used: 
['*']
```
```php
// example (select ID, IBLOCK_ID, NAME, PREVIEW_PICTURE): 
$iBlock->fields(['NAME', 'PREVIEW_PICTURE'])->getElements();
```

#### IBlock::navs
Navigation parameters.
```php
public IBlock::navs(array $array): IBlock
```
```php
$iBlock->navs(['nPageSize' => 4, 'iNumPage' => 1, 'checkOutOfRange' => true])->getElements();
```

#### IBlock::sizes
Sizes for element's pictures.
```php
public IBlock::sizes(array $array): IBlock
```
```php
// sizes by default: 
['width' => 640, 'height' => 640]
```
```php
// example: 
$iBlock->sizes(['width' => 1280, 'height' => 720])->getElements();
```

#### IBlock::params
Set all properties in one array
```php
public IBlock::params(array $array): IBlock
```
```php
// example: 
$params = [
    'sort' => ['ID' => 'DESC'],
    'filter' => ['>=TIMESTAMP_X' => date('Y-m-d h:i:s', 'yesterday')],
    'navs' => ['nPageSize' => 4, 'iNumPage' => 1, 'checkOutOfRange' => true],
    'fields' => ['NAME', 'CODE'],
    'sizes' => ['width' => 1280, 'height' => 720]
];
$iBlock->params($params)->getElements();
```

### GET

#### IBlock::getElement
Get first element from result.
```php
public IBlock::getElement(Closure $closure = null): array
```
```php
// example: 
$handler = function (&$element) {
    $element['ID_CODE'] = $element['ID'] . '|' . $element['CODE'];
}
$arDetail = $iBlock->filter(['ID' => 42])->fields(['CODE'])->getElement($handler);

echo $arDetail['ID_CODE']; // 42|ELEMENT_CODE
```

### IBlock::getElements
Get list of elements.
```php
public IBlock::getElements(Closure $closure = null): array
```
```php
// example: 
$handler = function (&$element) {
    $element['ID_CODE'] = $element['ID'] . '|' . $element['CODE'];
}
$arResult = $iBlock->filter(['>=ID' => 42])->fields(['CODE'])->getElements($handler);

foreach ($arResult as $element) {
    echo $element['ID_CODE']; // 42|ELEMENT_CODE
}
```

### ADD & MODIFY
#### IBlock::add
Add a new element with fields and properties.
```php
public IBlock::add(array $fields, array $props = []): int
```
```php
// example:
(new IBlock('news'))->add(
    [
        'NAME' => 'Some',
        'PREVIEW_TEXT' => 'Some text'
    ], 
    [
        'SOME_PROPERTY_CODE' => 42
    ]
);
```

#### IBlock::update
Update element's fields and properties.
```php
public IBlock::update(string|int $id, array $fields, array $props = []): bool
```
```php
// example:
(new IBlock('news'))->update(
    [
        'NAME' => 'Updated name',
        'PREVIEW_TEXT' => 'Updated text'
    ], 
    [
        'SOME_PROPERTY_CODE' => 24
    ]
);
```

#### IBlock::delete
Delete element by id.
If id parameter is not setted, the last added or updated element will be deleted.
```php
public IBlock::delete(string|int|null $id = null): bool
```
```php
// example:
(new IBlock('news'))->delete(42);

// or:
$iBlock = new IBlock('news');
$iBlock->add(['NAME' => 'SOME']);
$iBlock->delete();
```

### OTHER

#### IBlock::getObResult
Get object
```php
public IBlock::getObResult(): CIBlockResult|int
```
```php
// example:
$arResult = $iBlock->filter(['>=ID' => 42])->getElements();
$obResult = $iBlock->getObResult();
```

#### IBlock::includeMeta
Includes element's meta to page.
```php
public IBlock::includeMeta(string|int $elementId): void
```
```php
// example:
(new IBlock('news'))->includeMeta(42);
```

#### IBlock::getPropertySubQuery
Get subquery for property.
```php
public IBlock::getPropertySubQuery(string $propName, string $propValue): array
```
```php
// example:
$subquery = $iBlock->getPropertySubQuery('SOME_CODE', 42);
$iBlock->filter([$subquery])->getElement();
```

### STATIC

#### IBlock::getIdByCode
Get subquery for property.
```php
public static IBlock::getIdByCode(string $code = '', $siteId = SITE_ID): false|mixed
```
```php
// example:
$id = IBlock::getIdByCode('some_iblock_code');
```

#### IBlock::getResizeImageSrc
Get subquery for property.
```php
public static IBlock::getResizeImageSrc($pictureId, array $sizes): string
```
```php
// example:
$pathToPicture = IBlock::getResizeImageSrc(42, ['width' => 1280, 'height' => 720]);
```