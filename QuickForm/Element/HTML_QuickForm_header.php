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
// | Author: Alexey Borzov <borz_off@cs.msu.su>                           |
// +----------------------------------------------------------------------+

/**
 * A pseudo-element used for adding headers to form
 *
 * @author Alexey Borzov <borz_off@cs.msu.su>
 */
class HTML_QuickForm_header extends HTML_QuickForm_static
{

    public function __construct(?string $elementName = null, ?string $text = null)
    {
        parent::__construct($elementName, null, $text);
        $this->_type = 'header';
    }
    
    public function accept(HTML_QuickForm_Renderer $renderer, bool $required = false, ?string $error = null)
    {
        $renderer->renderHeader($this);
    }

    public function getValue()
    {
        return null;
    }
}

