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
 */
class HTML_QuickForm_checkbox extends HTML_QuickForm_input
{

    protected string $_text = '';

    /**
     * @param string $text              Checkbox display text
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(
        ?string $elementName = null, ?string $elementLabel = null, string $text = '', $attributes = null
    )
    {
        parent::__construct($elementName, $elementLabel, $attributes);

        $this->_persistantFreeze = true;
        $this->_text = $text;
        $this->setType('checkbox');
        $this->updateAttributes(['value' => 1]);
    }

    /**
     * Return true if the checkbox is checked, null if it is not checked (getValue() returns false)
     */
    public function exportValue(array &$submitValues, bool $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value)
        {
            $value = $this->getChecked() ? true : null;
        }

        return $this->_prepareValue($value, $assoc);
    }

    public function getChecked(): bool
    {
        return (bool) $this->getAttribute('checked');
    }

    public function getFrozenHtml(): string
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

    public function getText(): string
    {
        return $this->_text;
    }

    public function setText(string $text)
    {
        $this->_text = $text;
    }

    public function getValue()
    {
        return $this->getChecked();
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event           Name of event
     * @param mixed $arg              event arguments
     * @param ?HTML_QuickForm $caller calling object
     */
    public function onQuickFormEvent(string $event, $arg, ?HTML_QuickForm $caller = null): bool
    {
        switch ($event)
        {
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findValue($caller->getConstantValues());

                if (null === $value)
                {
                    // if no boxes were checked, then there is no value in the array
                    // yet we don't want to display default value in this case
                    if ($caller->isSubmitted())
                    {
                        $value = $this->_findValue($caller->getSubmitValues());
                    }
                    else
                    {
                        $value = $this->_findValue($caller->getDefaultValues());
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

    public function setChecked(bool $checked)
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

    public function setValue($value)
    {
        $this->setChecked($value);
    }

    public function toHtml(): string
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

        return parent::toHtml() . $label;
    }

}

