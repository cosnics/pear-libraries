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
 * HTML class for a checkbox type field
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.1
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_checkbox extends HTML_QuickForm_input
{

    /**
     * Checkbox display text
     *
     * @var       string
     * @since     1.1
     * @access    private
     */
    var $_text = '';

    /**
     * Class constructor
     *
     * @param string $elementName           (optional)Input field name attribute
     * @param string $elementLabel          (optional)Input field value
     * @param string $text                  (optional)Checkbox display text
     * @param mixed $attributes             (optional)Either a typical HTML attribute string
     *                                      or an associative array
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName = null, $elementLabel = null, $text = '', $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_text = $text;
        $this->setType('checkbox');
        $this->updateAttributes(['value' => 1]);
    }

    /**
     * Sets whether a checkbox is checked
     *
     * @param bool $checked Whether the field is checked or not
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    function setChecked($checked)
    {
        if (!$checked)
        {
            $this->removeAttribute('checked');
        }
        else
        {
            $this->updateAttributes(['checked' => 'checked']);
        }
    }

    /**
     * Returns whether a checkbox is checked
     *
     * @return    bool
     * @since     1.0
     * @access    public
     */
    function getChecked()
    {
        return (bool) $this->getAttribute('checked');
    }

    /**
     * Returns the checkbox element in HTML
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    function toHtml()
    {
        $this->_generateId(); // Seems to be necessary when this is used in a group.
        if (0 == strlen($this->_text))
        {
            $label = '';
        }
        elseif ($this->_flagFrozen)
        {
            $label = $this->_text;
        }
        else
        {
            $label = '<label for="' . $this->getAttribute('id') . '">' . $this->_text . '</label>';
        }

        return HTML_QuickForm_input::toHtml() . $label;
    }

    /**
     * Returns the value of field without HTML tags
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    function getFrozenHtml()
    {
        if ($this->getChecked())
        {
            return '<tt>[x]</tt>' . $this->_getPersistantData();
        }
        else
        {
            return '<tt>[ ]</tt>';
        }
    }

    /**
     * Sets the checkbox text
     *
     * @param string $text
     *
     * @return    void
     * @since     1.1
     * @access    public
     */
    function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * Returns the checkbox text
     *
     * @return    string
     * @since     1.1
     * @access    public
     */
    function getText()
    {
        return $this->_text;
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
    function setValue($value)
    {
        return $this->setChecked($value);
    }

    /**
     * Returns the value of the form element
     *
     * @return    bool
     * @since     1.0
     * @access    public
     */
    function getValue()
    {
        return $this->getChecked();
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event  Name of event
     * @param mixed $arg     event arguments
     * @param object $caller calling object
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event)
        {
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value)
                {
                    // if no boxes were checked, then there is no value in the array
                    // yet we don't want to display default value in this case
                    if ($caller->isSubmitted())
                    {
                        $value = $this->_findValue($caller->_submitValues);
                    }
                    else
                    {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }
                if (null !== $value)
                {
                    $this->setChecked($value);
                }
                break;
            case 'setGroupValue':
                $this->setChecked($arg);
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

    /**
     * Return true if the checkbox is checked, null if it is not checked (getValue() returns false)
     */
    function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value)
        {
            $value = $this->getChecked() ? true : null;
        }

        return $this->_prepareValue($value, $assoc);
    }

}

