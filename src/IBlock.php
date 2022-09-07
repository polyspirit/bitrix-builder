<?php

/*
 * (c) polyspirit <poly@polycreative.ru>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
*/

namespace polyspirit\Bitrix\Builder;

use \Bitrix\Iblock\IblockTable;
use \Bitrix\Iblock\InheritedProperty\ElementValues;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Class IBlock
 * @package polyspirit\Bitrix\Builder
 * @version 1.2.4
 */
class IBlock
{
    protected string $mainClass = \CIBlockElement::class;

    protected int $iblockId;
    protected int $lastElementId;
    protected $obResult;

    protected $filter = [];
    protected $fields = ['*'];
    protected $sort = ['sort' => 'ASC', 'date_active_from' => 'DESC', 'created_date' => 'DESC'];
    protected $navs = false;
    protected $sizes = [
        'width' => 640,
        'height' => 640
    ];

    /**
     * @param int|string $iblockId id or code
     */
    public function __construct($iblockId, string $siteId = SITE_ID)
    {
        if (is_int($iblockId) || ctype_digit($iblockId)) {
            $this->iblockId = intval($iblockId);
        } else {
            $this->iblockId = self::getIdByCode($iblockId, $siteId);
        }
    }

    // BUILDER METHODS
    public function filter(array $filter = []): IBlock
    {
        $this->filter = array_merge($this->filter, $filter);

        return $this;
    }

    public function fields(array $fields = ['*']): IBlock
    {        
        $this->fields = $fields;

        if (!in_array('*', $fields)) {
            $this->fields = array_merge($this->fields, ['ID', 'IBLOCK_ID']);
        }
        
        return $this;
    }

    public function sort(array $sort = []): IBlock
    {
        $this->sort = array_merge($this->sort, $sort);
        
        return $this;
    }

    public function sortReset(): IBlock
    {
        $this->sort = [];
        
        return $this;
    }

    public function navs(array $navs = []): IBlock
    {
        $this->navs = $navs;
        
        return $this;
    }

    public function sizes(array $sizes = ['width' => 640, 'height' => 640]): IBlock
    {
        $this->sizes = array_merge($this->sizes, $sizes);
        
        return $this;
    }

    public function active(): IBlock
    {
        $activeFilter = [
            'ACTIVE' => 'Y',
            'GLOBAL_ACTIVE' => 'Y',
        ];

        $this->filter = array_merge($this->filter, $activeFilter);
        
        return $this;
    }

    public function params(array $params): IBlock
    {
        foreach ($params as $param => $value) {
            $this->$param($value);
        }

        return $this;
    }

    // ELEMENTS
    public function getElement(?\Closure $closure = null): array
    {
        $elem = [];

        $this->filter['IBLOCK_ID'] = $this->iblockId;
        $this->navs = ['nTopCount' => 1];

        $this->obResult = $this->getElementListResult();

        if ($obItem = $this->obResult->GetNextElement()) {        
            $elem = $this->getElementInfo($obItem, $closure);
        }

        $this->lastElementId = $elem['ID'];

        return $elem;
    }

    public function getElements(?\Closure $closure = null): array
    {
        $elements = [];

        $this->filter['IBLOCK_ID'] = $this->iblockId;

        $this->obResult = $this->getElementListResult();

        while ($obItem = $this->obResult->GetNextElement()) {        
            $elements[] = $this->getElementInfo($obItem, $closure);
        }

        $this->lastElementId = $elements[count($elements) - 1]['ID'];

        return $elements;
    }

    protected function getElementListResult()
    {
        return $this->mainClass::GetList(
            $this->sort,
            $this->filter,
            false,
            $this->navs,
            $this->fields
        );
    }

    /**
     * @param array|\_CIBElement $obItem
     * @param null|\Closure $closure
     * 
     * @return array
     */
    protected function getElementInfo($obItem, ?\Closure $closure): array
    {
        $arItem = $obItem->GetFields();
        $arItem['PROPS'] = $obItem->GetProperties();

        $pictureId = $arItem['DETAIL_PICTURE'] ?? $arItem['PREVIEW_PICTURE'];
    
        $arItem['PICTURE_SRC'] = self::getResizeImageSrc($pictureId, $this->sizes);

        if (isset($closure)) {
            $closure($arItem);
        }
    
        return $arItem;
    }


    // ADD & MODIFY
    protected function addOrUpdate(array $fields, array $props = [], $id = null)
    {
        if (empty($fields['IBLOCK_ID'])) {
            $fields['IBLOCK_ID'] = $this->iblockId;
        }

        $el = new $this->mainClass;

        $method = '';
        if (is_null($id) || empty($id)) {
            $method = 'addition';
            $fields['PROPERTY_VALUES'] = $props;
            $result = $el->Add($fields);
        } else {
            $method = 'update';
            $result = $el->Update($id, $fields);
            $this->mainClass::SetPropertyValuesEx($id, false, $props);
        }

        if (!$result) {
            throw new \Exception('Element ' . $method . ' error: ' . $el->LAST_ERROR, 400);
        }

        return $result;
    }

    public function add(array $fields, array $props = []): int
    {
        $id = $this->addOrUpdate($fields, $props);
        $this->lastElementId = (int)$id;

        return $this->lastElementId;
    }

    public function update($id, array $fields, array $props = []): bool
    {
        $this->lastElementId = (int)$id;

        return $this->addOrUpdate($fields, $props, $id);
    }

    public function delete($id = null): bool
    {
        if (is_null($id) || empty($id)) {
            $id = $this->lastElementId;
        }
        $this->lastElementId = $id;

        return $this->mainClass::Delete($id);
    }


    // OTHER
    /**
     * @return \CIBlockResult|int
     */
    public function getObResult()
    {
        return $this->obResult;
    }

    public function getPropertySubQuery(string $propName, string $propValue): array
    {
        return [
            'ID' => $this->mainClass::SubQuery(
                'ID', [
                    'IBLOCK_ID' => $this->iblockId, 
                    'PROPERTY_' . $propName => $propValue
                ]
            )
        ];
    }

    public function includeMeta($elementId = null): void
    {
        global $APPLICATION;
        $metaValues = $this->getMetaValues($elementId);

        if (!empty($metaValues['ELEMENT_PAGE_TITLE'])) {
            $APPLICATION->SetPageProperty('h1', $metaValues['ELEMENT_PAGE_TITLE']);
        }

        if (!empty($metaValues['ELEMENT_META_TITLE'])) {
            $APPLICATION->SetTitle($metaValues['ELEMENT_META_TITLE']);
        }

        if (!empty($metaValues['ELEMENT_META_DESCRIPTION'])) {
            $APPLICATION->SetPageProperty('description', $metaValues['ELEMENT_META_DESCRIPTION']);
        }

        if (!empty($metaValues['ELEMENT_META_KEYWORDS'])) {
            $APPLICATION->SetPageProperty('keywords', $metaValues['ELEMENT_META_KEYWORDS']);
        }
    }

    public function getMetaValues($elementId = null): array
    {
        $meta = new ElementValues($this->iblockId, $elementId ?? $this->lastElementId); 

        return $meta->getValues();
    }

    public function getListIdByXmlId(string $xmlId): string
    {
        $typeLocationOb = \CIBlockPropertyEnum::GetList(
            ['sort' => 'ASC'],
            ['IBLOCK_ID ' => $this->iblockId, 'XML_ID' => $xmlId]
        );

        $typeLocation = $typeLocationOb->GetNext();

        return $typeLocation['ID'];
    }


    // STATIC
    /**
     * @param string $code
     * @param false|mixed|string $siteID
     * @return false|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIdByCode(string $code = '', $siteId = SITE_ID)
    {
        $dbResult = IblockTable::getList(
            [
                'filter' => [
                    'CODE' => $code,
                    'LID' => $siteId
                ]
            ]
        );
        if ($next = $dbResult->fetch()) {
            return $next['ID'];
        }

        return false;
    }

    public static function getResizeImageSrc($pictureId, array $sizes): string
    {
        return $arItem['PICTURE_SRC'] = \CFile::ResizeImageGet(
            $pictureId,
            ['width' => $sizes['width'], 'height' => $sizes['height']]
        )['src'] ?? '';
    }
}