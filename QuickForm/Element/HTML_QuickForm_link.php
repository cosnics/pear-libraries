<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+

/**
 * HTML class for a link type field
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 */
class HTML_QuickForm_link extends HTML_QuickForm_static
{
    /**
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(
        ?string $elementName = null, ?string $elementLabel = null, ?string $href = null, ?string $text = null,
        $attributes = null
    )
    {
        parent::__construct($elementName, $elementLabel, $text, $attributes);

        $this->_persistantFreeze = false;
        $this->_type = 'link';
        $this->setHref($href);
    }

    /**
     * Returns the value of field without HTML tags (in this case, value is changed to a mask)
     */
    public function getFrozenHtml(): string
    {
        return '';
    }

    public function setHref(?string $href = null)
    {
        $this->updateAttributes(['href' => $href]);
    }

    public function setValue($value)
    {
    }

    public function toHtml(): string
    {
        $tabs = $this->_getTabs();

        $html = $tabs . '<a' . $this->_getAttrString($this->_attributes) . '>';
        $html .= $this->_text;
        $html .= '</a>';

        return $html;
    }

}

