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
 * Class to dynamically create an HTML SELECT
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 * @version      1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_select extends HTML_QuickForm_element
{

    /**
     * Contains the select options
     *
     * @var       array
     * @since     1.0
     * @access    private
     */
    public $_options = [];

    /**
     * Default values of the SELECT
     *
     * @var       string
     * @since     1.0
     * @access    private
     */
    public $_values = null;

    /**
     * Class constructor
     *
     * @param string    Select name attribute
     * @param mixed     Label(s) for the select
     * @param mixed     Data to be used to populate options
     * @param mixed     Either a typical HTML attribute string or an associative array
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function __construct($elementName = null, $elementLabel = null, $options = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'select';
        if (isset($options))
        {
            $this->load($options);
        }
    }

    /**
     * Adds a new OPTION to the SELECT
     *
     * @param string $text              Display text for the OPTION
     * @param string $value             Value for the OPTION
     * @param mixed $attributes         Either a typical HTML attribute string
     *                                  or an associative array
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function addOption($text, $value, $attributes = null)
    {
        if (null === $attributes)
        {
            $attributes = ['value' => $value];
        }
        else
        {
            $attributes = $this->_parseAttributes($attributes);
            if (isset($attributes['selected']))
            {
                // the 'selected' attribute will be set in toHtml()
                $this->_removeAttr('selected', $attributes);
                if (is_null($this->_values))
                {
                    $this->_values = [$value];
                }
                elseif (!in_array($value, $this->_values))
                {
                    $this->_values[] = $value;
                }
            }
            $this->_updateAttrArray($attributes, ['value' => $value]);
        }
        $this->_options[] = ['text' => $text, 'attr' => $attributes];
    }

    /**
     * We check the options and return only the values that _could_ have been
     * selected. We also return a scalar value if select is not "multiple"
     */
    public function exportValue(array &$submitValues, bool $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (is_null($value))
        {
            $value = $this->getValue();
        }
        elseif (!is_array($value))
        {
            $value = [$value];
        }
        if (is_array($value) && !empty($this->_options))
        {
            $cleanValue = null;
            foreach ($value as $v)
            {
                for ($i = 0, $optCount = count($this->_options); $i < $optCount; $i ++)
                {
                    if ($v == $this->_options[$i]['attr']['value'])
                    {
                        $cleanValue[] = $v;
                        break;
                    }
                }
            }
        }
        else
        {
            $cleanValue = $value;
        }
        if (is_array($cleanValue) && !$this->getMultiple())
        {
            return $this->_prepareValue($cleanValue[0], $assoc);
        }
        else
        {
            return $this->_prepareValue($cleanValue, $assoc);
        }
    }

    /**
     * Returns the value of field without HTML tags
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function getFrozenHtml(): string
    {
        $value = [];
        if (is_array($this->_values))
        {
            foreach ($this->_values as $key => $val)
            {
                for ($i = 0, $optCount = count($this->_options); $i < $optCount; $i ++)
                {
                    if ((string) $val == (string) $this->_options[$i]['attr']['value'])
                    {
                        $value[$key] = $this->_options[$i]['text'];
                        break;
                    }
                }
            }
        }
        $html = empty($value) ? '&nbsp;' : join('<br />', $value);
        if ($this->_persistantFreeze)
        {
            $name = $this->getPrivateName();
            // Only use id attribute if doing single hidden input
            if (1 == count($value))
            {
                $id = $this->getAttribute('id');
                $idAttr = isset($id) ? ['id' => $id] : [];
            }
            else
            {
                $idAttr = [];
            }
            foreach ($value as $key => $item)
            {
                $html .= '<input' . $this->_getAttrString(
                        [
                            'type' => 'hidden',
                            'name' => $name,
                            'value' => $this->_values[$key]
                        ] + $idAttr
                    ) . ' />';
            }
        }

        return $html;
    }

    /**
     * Returns the select mutiple attribute
     *
     * @return    bool    true if multiple select, false otherwise
     * @since     1.2
     * @access    public
     */
    public function getMultiple()
    {
        return (bool) $this->getAttribute('multiple');
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
     * Returns the element name (possibly with brackets appended)
     *
     * @return    string
     * @since     1.0
     * @access    public
     */
    public function getPrivateName()
    {
        if ($this->getAttribute('multiple'))
        {
            return $this->getName() . '[]';
        }
        else
        {
            return $this->getName();
        }
    }

    /**
     * Returns an array of the selected values
     *
     * @return    array of selected values
     * @since     1.0
     * @access    public
     */
    public function getSelected()
    {
        return $this->_values;
    }

    /**
     * Returns the select field size
     *
     * @return    int
     * @since     1.0
     * @access    public
     */
    public function getSize()
    {
        return $this->getAttribute('size');
    }

    /**
     * Returns an array of the selected values
     *
     * @return    array of selected values
     * @since     1.0
     * @access    public
     */
    public function getValue()
    {
        return $this->_values;
    }

    /**
     * Loads options from different types of data sources
     * This method is a simulated overloaded method.  The arguments, other than the
     * first are optional and only mean something depending on the type of the first argument.
     * If the first argument is an array then all arguments are passed in order to loadArray.
     * If the first argument is a db_result then all arguments are passed in order to loadDbResult.
     * If the first argument is a string or a DB connection then all arguments are
     * passed in order to loadQuery.
     *
     * @param mixed $options Options source currently supports assoc array or DB_result
     * @param mixed $param1  (optional) See function detail
     * @param mixed $param2  (optional) See function detail
     * @param mixed $param3  (optional) See function detail
     * @param mixed $param4  (optional) See function detail
     *
     * @return    PEAR_Error on error or true
     * @throws    PEAR_Error
     * @since     1.1
     * @access    public
     */
    public function load(&$options, $param1 = null, $param2 = null, $param3 = null, $param4 = null)
    {
        switch (true)
        {
            case is_array($options):
                return $this->loadArray($options, $param1);
                break;
        }
    }

    /**
     * Loads the options from an associative array
     *
     * @param array $arr    Associative array of options
     * @param mixed $values (optional) Array or comma delimited string of selected values
     *
     * @return    PEAR_Error on error or true
     * @throws    PEAR_Error
     * @since     1.0
     * @access    public
     */
    public function loadArray($arr, $values = null)
    {
        if (!is_array($arr))
        {
            throw new Exception('Argument 1 of HTML_Select::loadArray is not a valid array');
        }
        if (isset($values))
        {
            $this->setSelected($values);
        }
        foreach ($arr as $key => $val)
        {
            // Warning: new API since release 2.3
            $this->addOption($val, $key);
        }

        return true;
    }

    public function onQuickFormEvent(string $event, $arg, object $caller): bool
    {
        if ('updateValue' == $event)
        {
            $value = $this->_findValue($caller->_constantValues);
            if (null === $value)
            {
                $value = $this->_findValue($caller->_submitValues);
                // Fix for bug #4465 & #5269
                // XXX: should we push this to element::onQuickFormEvent()?
                if (null === $value && (!$caller->isSubmitted() || !$this->getMultiple()))
                {
                    $value = $this->_findValue($caller->_defaultValues);
                }
            }
            if (null !== $value)
            {
                $this->setValue($value);
            }

            return true;
        }
        else
        {
            return parent::onQuickFormEvent($event, $arg, $caller);
        }
    }

    /**
     * Sets the select mutiple attribute
     *
     * @param bool $multiple Whether the select supports multi-selections
     *
     * @return    void
     * @since     1.2
     * @access    public
     */
    public function setMultiple($multiple)
    {
        if ($multiple)
        {
            $this->updateAttributes(['multiple' => 'multiple']);
        }
        else
        {
            $this->removeAttribute('multiple');
        }
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
     * Sets the default values of the select box
     *
     * @param mixed $values Array or comma delimited string of selected values
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setSelected($values)
    {
        if (is_string($values) && $this->getMultiple())
        {
            $values = preg_split('/[ ]?,[ ]?/', $values);
        }
        if (is_array($values))
        {
            $this->_values = array_values($values);
        }
        else
        {
            $this->_values = [$values];
        }
    }

    /**
     * Sets the select field size, only applies to 'multiple' selects
     *
     * @param int $size Size of select  field
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setSize($size)
    {
        $this->updateAttributes(['size' => $size]);
    }

    /**
     * Sets the value of the form element
     *
     * @param mixed $values Array or comma delimited string of selected values
     *
     * @return    void
     * @since     1.0
     * @access    public
     */
    public function setValue($value)
    {
        $this->setSelected($value);
    }

    /**
     * Returns the SELECT in HTML
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
            $tabs = $this->_getTabs();
            $strHtml = '';

            if ($this->getComment() != '')
            {
                $strHtml .= $tabs . '<!-- ' . $this->getComment() . " //-->\n";
            }

            if (!$this->getMultiple())
            {
                $attrString = $this->_getAttrString($this->_attributes);
            }
            else
            {
                $myName = $this->getName();
                $this->setName($myName . '[]');
                $attrString = $this->_getAttrString($this->_attributes);
                $this->setName($myName);
            }
            $strHtml .= $tabs . '<select' . $attrString . ">\n";

            foreach ($this->_options as $option)
            {
                if (is_array($this->_values) && in_array((string) $option['attr']['value'], $this->_values))
                {
                    $this->_updateAttrArray($option['attr'], ['selected' => 'selected']);
                }
                $strHtml .= $tabs . "\t<option" . $this->_getAttrString($option['attr']) . '>' . $option['text'] .
                    "</option>\n";
            }

            return $strHtml . $tabs . '</select>';
        }
    }

}

