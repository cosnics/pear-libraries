<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
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
// | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+

/**
 * HTML class for static data
 *
 * @author       Wojciech Gdela <eltehaem@poczta.onet.pl>
 */
class HTML_QuickForm_static extends HTML_QuickForm_element
{

    protected ?string $_text = null;

    /**
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(
        ?string $elementName = null, ?string $elementLabel = null, ?string $text = null, $attributes = null
    )
    {
        parent::__construct($elementName, $elementLabel, $attributes);

        $this->_persistantFreeze = false;
        $this->_type = 'static';
        $this->_text = $text;
    }

    /**
     * We override this here because we don't want any values from static elements
     */
    public function exportValue(array &$submitValues, bool $assoc = false)
    {
        return null;
    }

    /**
     * Returns the value of field without HTML tags
     */
    public function getFrozenHtml(): string
    {
        return $this->toHtml();
    }

    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    public function getValue()
    {
        return null;
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param mixed $arg event arguments
     * @param object $caller calling object
     */
    public function onQuickFormEvent(string $event, $arg, object $caller): bool
    {
        switch ($event)
        {
            case 'updateValue':
                // do NOT use submitted values for static elements
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value)
                {
                    $value = $this->_findValue($caller->_defaultValues);
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

    public function setName(string $name)
    {
        $this->updateAttributes(['name' => $name]);
    }

    public function setText(?string $text = null)
    {
        $this->_text = $text;
    }

    /**
     * Sets the text (uses the standard setValue call to emulate a form element.
     */
    public function setValue($text)
    {
        $this->setText($text);
    }

    public function toHtml(): string
    {
        return $this->_getTabs() . $this->_text;
    }
}

