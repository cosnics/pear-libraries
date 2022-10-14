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
 * HTML class for a textarea type field
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 */
class HTML_QuickForm_textarea extends HTML_QuickForm_element
{

    protected ?string $_value = null;

    /**
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(?string $elementName = null, ?string $elementLabel = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'textarea';
    }

    public function getFrozenHtml(): string
    {
        $value = htmlspecialchars($this->getValue());
        if ($this->getAttribute('wrap') == 'off')
        {
            $html = $this->_getTabs() . '<pre>' . $value . "</pre>\n";
        }
        else
        {
            $html = nl2br($value) . "\n";
        }

        return $html . $this->_getPersistantData();
    }

    public function getName(): ?string
    {
        return $this->getAttribute('name');
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setValue($value)
    {
        $this->_value = $value;
    }

    public function setCols(?int $cols)
    {
        $this->updateAttributes(['cols' => $cols]);
    }

    public function setName(string $name)
    {
        $this->updateAttributes(['name' => $name]);
    }

    public function setRows(?int $rows)
    {
        $this->updateAttributes(['rows' => $rows]);
    }

    public function setWrap(?string $wrap)
    {
        $this->updateAttributes(['wrap' => $wrap]);
    }

    public function toHtml(): string
    {
        if ($this->_flagFrozen)
        {
            return $this->getFrozenHtml();
        }
        else
        {
            return $this->_getTabs() . '<textarea' . $this->_getAttrString($this->_attributes) . '>' .
                // because we wrap the form later we don't want the text indented
                preg_replace("/(\r\n|\n|\r)/", '&#010;', htmlspecialchars($this->_value)) . '</textarea>';
        }
    }

}

