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
// | Authors: Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+

/**
 * Validates values using regular expressions
 */
class HTML_QuickForm_Rule_Regex extends HTML_QuickForm_Rule
{
    /**
     * Array of regular expressions
     * Array is in the format:
     * $_data['rulename'] = 'pattern';
     *
     * @var string[]
     */
    protected array $_data = [
        'lettersonly' => '/^[a-zA-Z]+$/',
        'alphanumeric' => '/^[a-zA-Z0-9]+$/',
        'numeric' => '/(^-?\d\d*\.\d*$)|(^-?\d\d*$)|(^-?\.\d\d*$)/',
        'nopunctuation' => '/^[^().\/\*\^\?#!@$%+=,\"\'><~\[\]{}]+$/',
        'nonzero' => '/^-?[1-9][0-9]*/'
    ];

    /**
     * Adds new regular expressions to the list
     */
    public function addData(string $name, string $pattern)
    {
        $this->_data[$name] = $pattern;
    }

    public function getValidationScript($options = null): array
    {
        $regex = $this->_data[$this->name] ?? $options;

        return ['  var regex = ' . $regex . ";\n", "{jsVar} != '' && !regex.test({jsVar})"];
    }

    /**
     * Validates a value using a regular expression
     *
     * @param string $value Value to be checked
     * @param string $regex Regular expression
     */
    public function validate($value, $regex = null): bool
    {
        if (isset($this->_data[$this->name]))
        {
            if (!preg_match($this->_data[$this->name], $value))
            {
                return false;
            }
        }
        elseif (!preg_match($regex, $value))
        {
            return false;
        }

        return true;
    }

}