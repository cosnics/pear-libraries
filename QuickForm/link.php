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
 * @version      1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_link extends HTML_QuickForm_static
{

    /**
     * Link display text
     *
     * @var       string
     * @since     1.0
     * @access    private
     */
    public $_text = '';

    /**
     * Class constructor
     *
     * @param string $elementLabel          (optional)Link label
     * @param string $href                  (optional)Link href
     * @param string $text                  (optional)Link display text
     * @param mixed $attributes             (optional)Either a typical HTML attribute string
     *                                      or an associative array
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    public function __construct(
        $elementName = null, $elementLabel = null, $href = null, $text = null, $attributes = null
    )
    {
        // TODO MDL-52313 Replace with the call to parent::__construct().
        HTML_QuickForm_element::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = false;
        $this->_type = 'link';
        $this->setHref($href);
        $this->_text = $text;
    }

    /**
     * Sets the input field name
     *
     * @param string $name Input field name attribute
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    public function setName($name)
    {
        $this->updateAttributes(['name' => $name]);
    }

    /**
     * Returns the element name
     *
     * @return    string
     * @throws
     * @since     1.0
     * @access    public
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Sets value for textarea element
     *
     * @param string $value Value for password element
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    public function setValue($value)
    {
        return;
    }

    /**
     * Returns the value of the form element
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    public function getValue()
    {
        return;
    }

    /**
     * Sets the links href
     *
     * @param string $href
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    public function setHref($href)
    {
        $this->updateAttributes(['href' => $href]);
    }

    /**
     * Returns the textarea element in HTML
     *
     * @return    string
     * @throws
     * @since     1.0
     * @access    public
     */
    public function toHtml()
    {
        $tabs = $this->_getTabs();
        $html = "$tabs<a" . $this->_getAttrString($this->_attributes) . '>';
        $html .= $this->_text;
        $html .= '</a>';

        return $html;
    }

    /**
     * Returns the value of field without HTML tags (in this case, value is changed to a mask)
     *
     * @return    string
     * @throws
     * @since     1.0
     * @access    public
     */
    public function getFrozenHtml()
    {
        return;
    }

}

