<?php

/*
 * (c) polyspirit <poly@polycreative.ru>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
*/

namespace polyspirit\Bitrix\Builder;

use CIBlockResult;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Class ISection
 * @package polyspirit\Bitrix\Builder
 * @version 1.0
 */
class ISection extends IBlock
{

    protected function getElementListResult(): CIBlockResult
    {
        return \CIBlockSection::GetList(
            $this->sort,
            $this->filter,
            false,
            $this->fields,
            $this->navs
        );
    }

    public function getDefaultSubsections(string $rootSectionCode): array
    {
        $this->sort = ['SORT' => 'ASC'];
        $this->filter = ['ACTIVE' => 'Y', 'SECTION_ID' => $this->getSectionIdByCode($rootSectionCode)];
        $this->fields(['NAME', 'CODE', 'DESCRIPTION', 'PICTURE']);
        $this->sizes = ['width' => 657, 'height' => 354];
        $this->obResult = $this->getElementListResult();

        return $this->getElements();
    }

    protected function getElementInfo($obItem, $closure = null): array
    {
        $arItem = $obItem->GetFields();
        $arItem['PICTURE_SRC'] = parent::getResizeImageSrc($arItem['PICTURE'], $this->sizes);

        if (isset($closure)) {
            $closure($arItem);
        }
    
        return $arItem;
    }

    public function getSectionIdByCode(string $code, $siteId = SITE_ID)
    {
        $res = \CIBlockSection::GetList(
            [], 
            ['IBLOCK_ID' => $this->iblockId, 'CODE' => $code, 'SITE_ID' => $siteId]
        );
        $section = $res->Fetch();
        
        return $section["ID"];
    }


    // OTHER
    public function includeMeta($elementId)
    {
        global $APPLICATION;
        $meta = new \Bitrix\Iblock\InheritedProperty\SectionValues($this->iblockId, $elementId); 
        $metaValues = $meta->getValues();

        if (!empty($metaValues['ELEMENT_META_DESCRIPTION'])) {
            $APPLICATION->SetPageProperty('description', $metaValues['ELEMENT_META_DESCRIPTION']);
        }

        if (!empty($metaValues['ELEMENT_META_KEYWORDS'])) {
            $APPLICATION->SetPageProperty('keywords', $metaValues['ELEMENT_META_KEYWORDS']);
        }
    }

}