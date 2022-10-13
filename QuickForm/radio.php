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
 * HTML class for a radio type element
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.1
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_radio extends HTML_QuickForm_input
{

    /**
     * Radio display text
     *
     * @var       string
     * @since     1.1
     * @access    private
     */
    public $_text = '';

    /**
     * Class constructor
     *
     * @param string    Input field name attribute
     * @param mixed     Label(s) for a field
     * @param string    Text to display near the radio
     * @param string    Input field value
     * @param mixed     Either a typical HTML attribute string or an associative array
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function __construct(
        $elementName = null, $elementLabel = null, $text = null, $value = null, $attributes = null
    )
    {
        // TODO MDL-52313 Replace with the call to parent::__construct().
        HTML_QuickForm_element::__construct($elementName, $elementLabel, $attributes);
        if (isset($value))
        {
            $this->setValue($value);
        }
        $this->_persistantFreeze = true;
        $this->setType('radio');
        $this->_text = $text;
    }

    /**
     * Returns the value attribute if the radio is checked, null if it is not
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value)
        {
            $value = $this->getChecked() ? $this->getValue() : null;
        }
        elseif ($value != $this->getValue())
        {
            $value = null;
        }

        return $this->_prepareValue($value, $assoc);
    }

    /**
     * Returns whether radio button is checked
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function getChecked()
    {
        return $this->getAttribute('checked');
    }

    /**
     * Returns the value of field without HTML tags
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function getFrozenHtml()
    {
        if ($this->getChecked())
        {
            return '<tt>(x)</tt>' . $this->_getPersistantData();
        }
        else
        {
            return '<tt>( )</tt>';
        }
    }

    /**
     * Returns the radio text
     *
     * @return    string
     * @since     1.1
     * @access    public
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * Sets the radio text
     *
     * @param string $text Text to display near the radio button
     *
     * @return    void
     * @since     1.1
     * @access    public
     */
    public function setText($text)
    {
        $this->_text = $text;
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
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event)
        {
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value)
                {
                    $value = $this->_findValue($caller->_submitValues);
                    if (null === $value)
                    {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }
                if ($value == $this->getValue())
                {
                    $this->setChecked(true);
                }
                else
                {
                    $this->setChecked(false);
                }
                break;
            case 'setGroupValue':
                if ($arg == $this->getValue())
                {
                    $this->setChecked(true);
                }
                else
                {
                    $this->setChecked(false);
                }
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

    /**
     * Sets whether radio button is checked
     *
     * @param bool $checked Whether the field is checked or not
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setChecked($checked)
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
     * Returns the radio element in HTML
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function toHtml()
    {
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

}

