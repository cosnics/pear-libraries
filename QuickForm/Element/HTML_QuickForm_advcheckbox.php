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
 * HTML class for an advanced checkbox type field
 * Basically this fixes a problem that HTML has had
 * where checkboxes can only pass a single value (the
 * value of the checkbox when checked).  A value for when
 * the checkbox is not checked cannot be passed, and
 * furthermore the checkbox variable doesn't even exist if
 * the checkbox was submitted unchecked.
 * It works by prepending a hidden field with the same name and
 * another "unchecked" value to the checbox. If the checkbox is
 * checked, PHP overwrites the value of the hidden field with
 * its value.
 *
 * @author       Jason Rust <jrust@php.net>
 */
class HTML_QuickForm_advcheckbox extends HTML_QuickForm_checkbox
{

    protected ?bool $_currentValue = null;

    /**
     * The values passed by the hidden elment
     */
    protected ?array $_values = null;

    /**
     * @param string $text               Checkbox display text
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     * @param mixed $values              (optional)Values to pass if checked or not checked
     */
    public function __construct(
        ?string $elementName = null, ?string $elementLabel = null, string $text = '', $attributes = null,
        ?array $values = null
    )
    {
        parent::__construct($elementName, $elementLabel, $text, $attributes);

        $this->setValues($values);
    }

    /**
     * This element has a value even if it is not checked, thus we override
     * checkbox's behaviour here
     */
    public function exportValue(array &$submitValues, bool $assoc = false)
    {
        $value = $this->_findValue($submitValues);

        if (null === $value)
        {
            $value = $this->getValue();
        }
        elseif (is_array($this->_values) && ($value != $this->_values[0]) && ($value != $this->_values[1]))
        {
            $value = null;
        }

        return $this->_prepareValue($value, $assoc);
    }

    /**
     * Unlike checkbox, this has to append a hidden input in both
     * checked and non-checked states
     */
    public function getFrozenHtml(): string
    {
        return ($this->getChecked() ? '<tt>[x]</tt>' : '<tt>[ ]</tt>') . $this->_getPersistantData();
    }

    public function getValue()
    {
        if (is_array($this->_values))
        {
            return $this->_values[$this->getChecked() ? 1 : 0];
        }
        else
        {
            return null;
        }
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event  Name of event
     * @param mixed $arg     event arguments
     * @param object $caller calling object
     */
    public function onQuickFormEvent(string $event, $arg, object $caller): bool
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
                if (null !== $value)
                {
                    $this->setValue($value);
                }
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

    public function setValue($value)
    {
        $this->setChecked(isset($this->_values[1]) && $value == $this->_values[1]);
        $this->_currentValue = $value;
    }

    /**
     * Sets the values used by the hidden element
     */
    public function setValues($values)
    {
        if (empty($values))
        {
            // give it default checkbox behavior
            $this->_values = ['', 1];
        }
        elseif (is_scalar($values))
        {
            // if it's string, then assume the value to
            // be passed is for when the element is checked
            $this->_values = ['', $values];
        }
        else
        {
            $this->_values = $values;
        }

        $this->updateAttributes(['value' => $this->_values[1]]);
        $this->setChecked($this->_currentValue == $this->_values[1]);
    }
    
    public function toHtml(): string
    {
        if ($this->_flagFrozen)
        {
            return parent::toHtml();
        }
        else
        {
            return '<input' . $this->_getAttrString([
                    'type' => 'hidden',
                    'name' => $this->getName(),
                    'value' => $this->_values[0]
                ]) . ' />' . parent::toHtml();
        }
    }

}

