<?php

// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexey Borzov <avb@php.net>                                 |
// +----------------------------------------------------------------------+

/**
 * Class for HTML 4.0 <button> element
 *
 * @author  Alexey Borzov <avb@php.net>
 */
class HTML_QuickForm_xbutton extends HTML_QuickForm_element
{
    protected ?string $_content;

    /**
     * @param ?string $elementContent    Button content (HTML to add between <button></button> tags)
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(?string $elementName = null, ?string $elementContent = null, $attributes = null)
    {
        parent::__construct($elementName, null, $attributes);
        $this->setContent($elementContent);
        $this->setPersistantFreeze();
        $this->_type = 'xbutton';
    }

    /**
     * Returns a 'safe' element's value
     * The value is only returned if the button's type is "submit" and if this
     * particlular button was clicked
     */
    public function exportValue(array &$submitValues, bool $assoc = false)
    {
        if ('submit' == $this->getAttribute('type'))
        {
            return $this->_prepareValue($this->_findValue($submitValues), $assoc);
        }
        else
        {
            return null;
        }
    }

    public function freeze()
    {
        return false;
    }

    public function getFrozenHtml(): string
    {
        return $this->toHtml();
    }

    public function getName(): ?string
    {
        return $this->getAttribute('name');
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event          Name of event
     * @param mixed $arg             event arguments
     * @param ?HTML_QuickForm $caller calling object
     */
    public function onQuickFormEvent(string $event, $arg, ?HTML_QuickForm $caller = null): bool
    {
        if ('updateValue' != $event)
        {
            return parent::onQuickFormEvent($event, $arg, $caller);
        }
        else
        {
            $value = $this->_findValue($caller->getConstantValues());

            if (null === $value)
            {
                $value = $this->_findValue($caller->getDefaultValues());
            }

            if (null !== $value)
            {
                $this->setValue($value);
            }
        }

        return true;
    }

    public function setContent(?string $content)
    {
        $this->_content = $content;
    }

    public function setName(string $name)
    {
        $this->updateAttributes([
            'name' => $name
        ]);
    }

    public function setValue($value)
    {
        $this->updateAttributes([
            'value' => $value
        ]);
    }

    public function toHtml(): string
    {
        return '<button' . $this->getAttributes(true) . '>' . $this->_content . '</button>';
    }
}


