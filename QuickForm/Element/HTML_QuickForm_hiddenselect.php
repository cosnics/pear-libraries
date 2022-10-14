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
 * This class takes the same arguments as a select element, but instead
 * of creating a select ring it creates hidden elements for all values
 * already selected with setDefault or setConstant.  This is useful if
 * you have a select ring that you don't want visible, but you need all
 * selected values to be passed.
 *
 * @author       Isaac Shepard <ishepard@bsiweb.com>
 */
class HTML_QuickForm_hiddenselect extends HTML_QuickForm_select
{

    /**
     * @param ?array $options            Data to be used to populate options
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(
        ?string $elementName = null, ?string $elementLabel = null, ?array $options = null, $attributes = null
    )
    {
        parent::__construct($elementName, $elementLabel, $options, $attributes);

        $this->_type = 'hiddenselect';
    }

    /**
     * This is essentially a hidden element and should be rendered as one
     */
    public function accept(HTML_QuickForm_Renderer $renderer, bool $required = false, ?string $error = null)
    {
        $renderer->renderHidden($this);
    }

    public function toHtml(): string
    {
        $tabs = $this->_getTabs();
        $name = $this->getPrivateName();
        $strHtml = '';

        foreach ($this->_values as $val)
        {
            for ($i = 0, $optCount = count($this->_options); $i < $optCount; $i ++)
            {
                if ($val == $this->_options[$i]['attr']['value'])
                {
                    $strHtml .= $tabs . '<input' . $this->_getAttrString([
                            'type' => 'hidden',
                            'name' => $name,
                            'value' => $val
                        ]) . " />\n";
                }
            }
        }

        return $strHtml;
    }

}

