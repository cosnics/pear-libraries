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
 * @version      1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_textarea extends HTML_QuickForm_element
{

    /**
     * Field value
     *
     * @var       string
     * @since     1.0
     * @access    private
     */
    public $_value = null;

    /**
     * Class constructor
     *
     * @param string    Input field name attribute
     * @param mixed     Label(s) for a field
     * @param mixed     Either a typical HTML attribute string or an associative array
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'textarea';
    }

    /**
     * Returns the value of field without HTML tags (in this case, value is changed to a mask)
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
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

    /**
     * Returns the element name
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    /**
     * Returns the value of the form element
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Sets value for textarea element
     *
     * @param string $value Value for textarea element
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Sets width in cols for textarea element
     *
     * @param string $cols Width expressed in cols
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setCols($cols)
    {
        $this->updateAttributes(['cols' => $cols]);
    }

    /**
     * Sets the input field name
     *
     * @param string $name Input field name attribute
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setName($name)
    {
        $this->updateAttributes(['name' => $name]);
    }

    /**
     * Sets height in rows for textarea element
     *
     * @param string $rows Height expressed in rows
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setRows($rows)
    {
        $this->updateAttributes(['rows' => $rows]);
    }

    /**
     * Sets wrap type for textarea element
     *
     * @param string $wrap Wrap type
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setWrap($wrap)
    {
        $this->updateAttributes(['wrap' => $wrap]);
    }

    /**
     * Returns the textarea element in HTML
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
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

