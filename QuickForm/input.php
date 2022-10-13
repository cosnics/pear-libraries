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
 * Base class for input form elements
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.0
 * @since        PHP4.04pl1
 * @access       public
 * @abstract
 */
abstract class HTML_QuickForm_input extends HTML_QuickForm_element
{

    /**
     * Class constructor
     *
     * @param string     Input field name attribute
     * @param mixed      Label(s) for the input field
     * @param mixed      Either a typical HTML attribute string or an associative array
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
    }

    /**
     * We don't need values from button-type elements (except submit) and files
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $type = $this->getType();
        if ('reset' == $type || 'image' == $type || 'button' == $type || 'file' == $type)
        {
            return null;
        }
        else
        {
            return parent::exportValue($submitValues, $assoc);
        }
    }

    /**
     * Returns the element name
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function getName()
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
        return $this->getAttribute('value');
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event  Name of event
     * @param mixed $arg     event arguments
     * @param object $caller calling object
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        // do not use submit values for button-type elements
        $type = $this->getType();
        if (('updateValue' != $event) ||
            ('submit' != $type && 'reset' != $type && 'image' != $type && 'button' != $type))
        {
            parent::onQuickFormEvent($event, $arg, $caller);
        }
        else
        {
            $value = $this->_findValue($caller->_constantValues);
            if (null === $value)
            {
                $value = $this->_findValue($caller->_defaultValues);
            }
            if (null !== $value)
            {
                $this->setValue($value);
            }
        }

        return true;
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
     * Sets the element type
     *
     * @param string $type Element type
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setType($type)
    {
        $this->_type = $type;
        $this->updateAttributes(['type' => $type]);
    }

    /**
     * Sets the value of the form element
     *
     * @param string $value Default value of the form element
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setValue($value)
    {
        $this->updateAttributes(['value' => $value]);
    }

    /**
     * Returns the input field in HTML
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function toHtml()
    {
        if ($this->_flagFrozen)
        {
            return $this->getFrozenHtml();
        }
        else
        {
            return $this->_getTabs() . '<input' . $this->_getAttrString($this->_attributes) . ' />';
        }
    }

} // end class HTML_QuickForm_element

