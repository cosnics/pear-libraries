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
 * Required elements validation
 */
class HTML_QuickForm_Rule_Required extends HTML_QuickForm_Rule
{
    public function getValidationScript($options = null): array
    {
        return ['', "{jsVar} == ''"];
    }

    /**
     * Checks if an element is empty
     */
    public function validate($value, $options = null): bool
    {
        if ((string) $value == '')
        {
            return false;
        }

        return true;
    }

}