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
 */
class HTML_QuickForm_radio extends HTML_QuickForm_input
{

    protected ?string $_text = null;

    /**
     * @param ?string $text              Text to display near the radio
     * @param ?string $value             Input field value
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(
        ?string $elementName = null, ?string $elementLabel = null, ?string $text = null, ?string $value = null,
        $attributes = null
    )
    {
        parent::__construct($elementName, $elementLabel, $attributes);

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
    public function exportValue(array &$submitValues, bool $assoc = false)
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

    public function getChecked(): ?string
    {
        return $this->getAttribute('checked');
    }

    public function getFrozenHtml(): string
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

    public function getText(): ?string
    {
        return $this->_text;
    }

    public function setText(?string $text)
    {
        $this->_text = $text;
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
                    $value = $this->_findValue($caller->getSubmitValues());

                    if (null === $value)
                    {
                        $value = $this->_findValue($caller->getDefaultValues());
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

    public function toHtml(): string
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

