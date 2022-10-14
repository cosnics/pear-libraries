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
 * An abstract base class for QuickForm renderers. The class implements a Visitor design pattern
 *
 * @author Alexey Borzov <borz_off@cs.msu.su>
 */
abstract class HTML_QuickForm_Renderer
{
    /**
     * Called when visiting a form, after processing all form elements
     */
    abstract public function finishForm(HTML_QuickForm $form);

    /**
     * Called when visiting a group, after processing all group elements
     */
    abstract public function finishGroup(HTML_QuickForm_group $group);

    /**
     * Called when visiting an element
     */
    abstract public function renderElement(HTML_QuickForm_element $element, bool $required, ?string $error = null);

    /**
     * Called when visiting a header element
     */
    abstract public function renderHeader(HTML_QuickForm_header $header);

    /**
     * Called when visiting a hidden element
     */
    abstract public function renderHidden(HTML_QuickForm_hidden $element);

    /**
     * Called when visiting a raw HTML/text pseudo-element
     * Seems that this should not be used when using a template-based renderer
     */
    abstract public function renderHtml(HTML_QuickForm_html $data);

    /**
     * Called when visiting a form, before processing any form elements
     */
    abstract public function startForm(HTML_QuickForm $form);

    /**
     * Called when visiting a group, before processing any group elements
     */
    abstract public function startGroup(HTML_QuickForm_group $group, bool $required, ?string $error = null);
}