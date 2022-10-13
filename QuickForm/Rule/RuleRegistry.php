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
// |          Alexey Borzov <borz_off@cs.msu.su>                          |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+

class HTML_QuickForm_RuleRegistry
{
    protected array $_rules = [];

    /**
     * Returns a singleton of HTML_QuickForm_RuleRegistry. Usually, only one RuleRegistry object is needed, this is the
     * reason why it is recommended to use this method to get the validation object.
     */
    public static function singleton(): HTML_QuickForm_RuleRegistry
    {
        static $obj;

        if (!isset($obj))
        {
            $obj = new HTML_QuickForm_RuleRegistry();
        }

        return $obj;
    }

    /**
     * Registers a new validation rule
     * In order to use a custom rule in your form, you need to register it
     * first. For regular expressions, one can directly use the 'regex' type
     * rule in addRule(), this is faster than registering the rule.
     * Functions and methods can be registered. Use the 'function' type.
     * When registering a method, specify the class name as second parameter.
     * You can also register an HTML_QuickForm_Rule subclass with its own
     * validate() method.
     *
     * @param string $ruleName                   Name of validation rule
     * @param string $type                       Either: 'regex', 'function' or null
     * @param string|\HTML_QuickForm_Rule $data1 Name of function, regular expression or
     *                                           HTML_QuickForm_Rule object class name
     * @param ?string $data2                     Object parent of above function or HTML_QuickForm_Rule file path
     *
     * @access    public
     * @return    void
     */
    public function registerRule(string $ruleName, string $type, $data1, ?string $data2 = null)
    {
        $type = strtolower($type);

        if ($type == 'regex')
        {
            // Regular expression
            $rule = $this->getRule('regex');
            $rule->addData($ruleName, $data1);
            $GLOBALS['_HTML_QuickForm_registered_rules'][$ruleName] =
                $GLOBALS['_HTML_QuickForm_registered_rules']['regex'];
        }
        elseif ($type == 'function' || $type == 'callback')
        {
            // Callback function
            $rule = $this->getRule('callback');
            $rule->addData($ruleName, $data1, $data2, 'function' == $type);
            $GLOBALS['_HTML_QuickForm_registered_rules'][$ruleName] =
                $GLOBALS['_HTML_QuickForm_registered_rules']['callback'];
        }
        elseif (is_object($data1))
        {
            // An instance of HTML_QuickForm_Rule
            $this->_rules[strtolower(get_class($data1))] = $data1;
            $GLOBALS['_HTML_QuickForm_registered_rules'][$ruleName] = strtolower(get_class($data1));
        }
        else
        {
            // Rule class name
            $GLOBALS['_HTML_QuickForm_registered_rules'][$ruleName] = strtolower($data1);
        }
    }

    public function getRule(string $ruleName): HTML_QuickForm_Rule
    {
        $class = $GLOBALS['_HTML_QuickForm_registered_rules'][$ruleName];

        if (!isset($this->_rules[$class]))
        {
            $this->_rules[$class] = new $class();
        }
        $this->_rules[$class]->setName($ruleName);

        return $this->_rules[$class];
    }

    /**
     * Performs validation on the given values
     *
     * @param string $ruleName              Name of the rule to be used
     * @param mixed $values                 Can be a scalar or an array of values
     *                                      to be validated
     * @param mixed $options                Options used by the rule
     * @param mixed $multiple               Whether to validate an array of values altogether
     *
     * @return bool|int true if no error found, int of valid values (when an array of values is given) or false if
     *                     error
     */
    public function validate(string $ruleName, $values, $options = null, $multiple = false)
    {
        $rule = $this->getRule($ruleName);

        if (is_array($values) && !$multiple)
        {
            $result = 0;
            foreach ($values as $value)
            {
                if ($rule->validate($value, $options) === true)
                {
                    $result ++;
                }
            }

            return ($result == 0) ? false : $result;
        }
        else
        {
            return $rule->validate($values, $options);
        }
    }

    /**
     * Returns the validation test in javascript code
     *
     * @param mixed $element      Element(s) the rule applies to
     * @param string $elementName Element name, in case $element is not array
     * @param array $ruleData     Rule data
     */
    public function getValidationScript($element, string $elementName, array $ruleData): string
    {
        $reset = (isset($ruleData['reset'])) ? $ruleData['reset'] : false;
        $rule = $this->getRule($ruleData['type']);
        if (!is_array($element))
        {
            [$jsValue, $jsReset] = $this->_getJsValue($element, $elementName, $reset);
        }
        else
        {
            $jsValue = "  value = new Array();\n";
            $jsReset = '';
            for ($i = 0; $i < count($element); $i ++)
            {
                [$tmp_value, $tmp_reset] = $this->_getJsValue($element[$i], $element[$i]->getName(), $reset, $i);
                $jsValue .= "\n" . $tmp_value;
                $jsReset .= $tmp_reset;
            }
        }
        $jsField = $ruleData['group'] ?? $elementName;
        [$jsPrefix, $jsCheck] = $rule->getValidationScript($ruleData['format']);
        if (!isset($ruleData['howmany']))
        {
            $js = $jsValue . "\n" . $jsPrefix . "  if (" . str_replace('{jsVar}', 'value', $jsCheck) .
                " && !errFlag['{$jsField}']) {\n" . "    errFlag['{$jsField}'] = true;\n" .
                "    _qfMsg = _qfMsg + '\\n - {$ruleData['message']}';\n" . $jsReset . "  }\n";
        }
        else
        {
            $js = $jsValue . "\n" . $jsPrefix . "  var res = 0;\n" . "  for (var i = 0; i < value.length; i++) {\n" .
                "    if (!(" . str_replace('{jsVar}', 'value[i]', $jsCheck) . ")) {\n" . "      res++;\n" . "    }\n" .
                "  }\n" . "  if (res < {$ruleData['howmany']} && !errFlag['{$jsField}']) {\n" .
                "    errFlag['{$jsField}'] = true;\n" . "    _qfMsg = _qfMsg + '\\n - {$ruleData['message']}';\n" .
                $jsReset . "  }\n";
        }

        return $js;
    }

    /**
     * Returns JavaScript to get and to reset the element's value
     *
     * @access private
     *
     * @param HTML_QuickForm_element $element element being processed
     * @param string $elementName             element's name
     * @param bool $reset                     whether to generate JavaScript to reset the value
     * @param ?int $index                     value's index in the array (only used for multielement rules)
     *
     * @return array     first item is value javascript, second is reset
     */
    public function _getJsValue(
        HTML_QuickForm_element $element, string $elementName, bool $reset = false, ?int $index = null
    ): array
    {
        $jsIndex = isset($index) ? '[' . $index . ']' : '';
        $tmp_reset = $reset ? "    var field = frm.elements['$elementName'];\n" : '';
        if (is_a($element, 'html_quickform_group'))
        {
            $value = "  _qfGroups['{$elementName}'] = {";
            $elements =& $element->getElements();
            for ($i = 0, $count = count($elements); $i < $count; $i ++)
            {
                $append = (($elements[$i]->getType() == 'select' || $element->getType() == 'autocomplete') &&
                    $elements[$i]->getMultiple()) ? '[]' : '';
                $value .= "'" . $element->getElementName($i) . $append . "': true" . ($i < $count - 1 ? ', ' : '');
            }
            $value .= "};\n" . "  value{$jsIndex} = new Array();\n" . "  var valueIdx = 0;\n" .
                "  for (var i = 0; i < frm.elements.length; i++) {\n" . "    var _element = frm.elements[i];\n" .
                "    if (_element.name in _qfGroups['{$elementName}']) {\n" . "      switch (_element.type) {\n" .
                "        case 'checkbox':\n" . "        case 'radio':\n" . "          if (_element.checked) {\n" .
                "            value{$jsIndex}[valueIdx++] = _element.value;\n" . "          }\n" . "          break;\n" .
                "        case 'select-one':\n" . "          if (-1 != _element.selectedIndex) {\n" .
                "            value{$jsIndex}[valueIdx++] = _element.options[_element.selectedIndex].value;\n" .
                "          }\n" . "          break;\n" . "        case 'select-multiple':\n" .
                "          var tmpVal = new Array();\n" . "          var tmpIdx = 0;\n" .
                "          for (var j = 0; j < _element.options.length; j++) {\n" .
                "            if (_element.options[j].selected) {\n" .
                "              tmpVal[tmpIdx++] = _element.options[j].value;\n" . "            }\n" . "          }\n" .
                "          if (tmpIdx > 0) {\n" . "            value{$jsIndex}[valueIdx++] = tmpVal;\n" .
                "          }\n" . "          break;\n" . "        default:\n" .
                "          value{$jsIndex}[valueIdx++] = _element.value;\n" . "      }\n" . "    }\n" . "  }\n";
            if ($reset)
            {
                $tmp_reset = "    for (var i = 0; i < frm.elements.length; i++) {\n" .
                    "      var _element = frm.elements[i];\n" .
                    "      if (_element.name in _qfGroups['{$elementName}']) {\n" .
                    "        switch (_element.type) {\n" . "          case 'checkbox':\n" .
                    "          case 'radio':\n" . "            _element.checked = _element.defaultChecked;\n" .
                    "            break;\n" . "          case 'select-one':\n" . "          case 'select-multiple':\n" .
                    "            for (var j = 0; j < _element.options.length; j++) {\n" .
                    "              _element.options[j].selected = _element.options[j].defaultSelected;\n" .
                    "            }\n" . "            break;\n" . "          default:\n" .
                    "            _element.value = _element.defaultValue;\n" . "        }\n" . "      }\n" . "    }\n";
            }
        }
        elseif ($element->getType() == 'select' || $element->getType() == 'autocomplete')
        {
            if ($element->getMultiple())
            {
                $elementName .= '[]';
                $value = "  value{$jsIndex} = new Array();\n" . "  var valueIdx = 0;\n" .
                    "  for (var i = 0; i < frm.elements['{$elementName}'].options.length; i++) {\n" .
                    "    if (frm.elements['{$elementName}'].options[i].selected) {\n" .
                    "      value{$jsIndex}[valueIdx++] = frm.elements['{$elementName}'].options[i].value;\n" .
                    "    }\n" . "  }\n";
            }
            else
            {
                $value =
                    "  value{$jsIndex} = frm.elements['{$elementName}'].selectedIndex == -1? '': frm.elements['{$elementName}'].options[frm.elements['{$elementName}'].selectedIndex].value;\n";
            }
            if ($reset)
            {
                $tmp_reset .= "    for (var i = 0; i < field.options.length; i++) {\n" .
                    "      field.options[i].selected = field.options[i].defaultSelected;\n" . "    }\n";
            }
        }
        elseif ($element->getType() == 'checkbox')
        {
            if (is_a($element, 'html_quickform_advcheckbox'))
            {
                $value =
                    "  value{$jsIndex} = frm.elements['$elementName'][1].checked? frm.elements['$elementName'][1].value: frm.elements['$elementName'][0].value;\n";
                $tmp_reset .= $reset ? "    field[1].checked = field[1].defaultChecked;\n" : '';
            }
            else
            {
                $value = "  value{$jsIndex} = frm.elements['$elementName'].checked? '1': '';\n";
                $tmp_reset .= $reset ? "    field.checked = field.defaultChecked;\n" : '';
            }
        }
        elseif ($element->getType() == 'radio')
        {
            $value = "  value{$jsIndex} = '';\n" . // Fix for bug #5644
                "  var els = 'length' in frm.elements['$elementName']? frm.elements['$elementName']: [ frm.elements['$elementName'] ];\n" .
                "  for (var i = 0; i < els.length; i++) {\n" . "    if (els[i].checked) {\n" .
                "      value{$jsIndex} = els[i].value;\n" . "    }\n" . "  }";
            if ($reset)
            {
                $tmp_reset .= "    for (var i = 0; i < field.length; i++) {\n" .
                    "      field[i].checked = field[i].defaultChecked;\n" . "    }";
            }
        }
        else
        {
            $value = "  value{$jsIndex} = frm.elements['$elementName'].value;";
            $tmp_reset .= ($reset) ? "    field.value = field.defaultValue;\n" : '';
        }

        return [$value, $tmp_reset];
    }
}