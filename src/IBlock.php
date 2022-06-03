<?php

/*
 * (c) polyspirit <poly@polycreative.ru>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
*/

namespace polyspirit\Bitrix\Builder;

use \Bitrix\Iblock\IblockTable;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Class IBlock
 * @package polyspirit\Bitrix\Builder
 * @version 1.0
 */
class IBlock
{
    protected int $iblockId;
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
    public function __construct($iblockId)
    {
        if (is_int($iblockId) || ctype_digit($iblockId)) {
            $this->iblockId = intval($iblockId);
        } else {
            $this->iblockId = self::getIdByCode($iblockId);
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
    public function getElement($closure = null): array
    {
        $elem = [];

        $this->filter['IBLOCK_ID'] = $this->iblockId;
        $this->navs = ['nTopCount' => 1];

        $this->obResult = $this->getElementListResult();

        if ($obItem = $this->obResult->GetNextElement()) {        
            $elem = $this->getElementInfo($obItem, $closure);
        }

        return $elem;
    }

    public function getElements($closure = null): array
    {
        $elements = [];

        $this->filter['IBLOCK_ID'] = $this->iblockId;

        $this->obResult = $this->getElementListResult();

        while ($obItem = $this->obResult->GetNextElement()) {        
            $elements[] = $this->getElementInfo($obItem, $closure);
        }

        return $elements;
    }

    protected function getElementListResult()
    {
        return \CIBlockElement::GetList(
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
    protected function getElementInfo($obItem, $closure = null): array
    {
        $arItem = $obItem->GetFields();
        $arItem['PROPS'] = $obItem->GetProperties();

        $pictureId = $arItem['DETAIL_PICTURE'] ?? $arItem['PREVIEW_PICTURE'];
    
        $arItem['PICTURE_SRC'] = Tools::getResizeImageSrc($pictureId, $this->sizes);

        if (isset($closure)) {
            $closure($arItem);
        }
    
        return $arItem;
    }


    // OTHER
    public function getObResult()
    {
        return $this->obResult;
    }

    public function getPropertySubQuery(string $propName, string $propValue): array
    {
        return [
            'ID' => \CIBlockElement::SubQuery(
                'ID', [
                    'IBLOCK_ID' => $this->iblockId, 
                    'PROPERTY_' . $propName => $propValue
                ]
            )
        ];
    }

    public function includeMeta($elementId)
    {
        global $APPLICATION;
        $meta = new \Bitrix\Iblock\InheritedProperty\ElementValues($this->iblockId, $elementId); 
        $metaValues = $meta->getValues();

        if (!empty($metaValues['ELEMENT_META_DESCRIPTION'])) {
            $APPLICATION->SetPageProperty('description', $metaValues['ELEMENT_META_DESCRIPTION']);
        }

        if (!empty($metaValues['ELEMENT_META_KEYWORDS'])) {
            $APPLICATION->SetPageProperty('keywords', $metaValues['ELEMENT_META_KEYWORDS']);
        }
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