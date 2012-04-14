<?php
/**
 * Unit tests for HTML_QuickForm2 package
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2006-2012, Alexey Borzov <avb@php.net>,
 *                          Bertrand Mansion <golgote@mamasam.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   HTML
 * @package    HTML_QuickForm2
 * @author     Alexey Borzov <avb@php.net>
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/HTML_QuickForm2
 */

/** Sets up includes */
require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';

/**
 * Unit test for HTML_QuickForm2_Container_Repeat class
 */
class HTML_QuickForm2_Container_RepeatTest extends PHPUnit_Framework_TestCase
{
    public function testCannotAddRepeatToRepeat()
    {
        $repeatOne = new HTML_QuickForm2_Container_Repeat();
        $repeatTwo = new HTML_QuickForm2_Container_Repeat();

        try {
            $repeatOne->setPrototype($repeatTwo);
            $this->fail('Expected HTML_QuickForm2_Exception was not thrown');
        } catch (HTML_QuickForm2_Exception $e) {}

        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeatOne->setPrototype($fieldset);

        try {
            $fieldset->appendChild($repeatTwo);
            $this->fail('Expected HTML_QuickForm2_Exception was not thrown');
        } catch (HTML_QuickForm2_Exception $e) {}
    }

    public function testPrototypeRequiredForDOMAndOutput()
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $text   = new HTML_QuickForm2_Element_InputText('aTextBox');

        try {
            $repeat->appendChild($text);
            $this->fail('Expected HTML_QuickForm2_NotFoundException not found');
        } catch (HTML_QuickForm2_NotFoundException $e) {}

        try {
            $repeat->insertBefore($text);
            $this->fail('Expected HTML_QuickForm2_NotFoundException not found');
        } catch (HTML_QuickForm2_NotFoundException $e) {}

        try {
            $repeat->render(HTML_QuickForm2_Renderer::factory('default'));
            $this->fail('Expected HTML_QuickForm2_NotFoundException not found');
        } catch (HTML_QuickForm2_NotFoundException $e) {}
    }

    public function testElementsAreAddedToPrototype()
    {
        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat   = new HTML_QuickForm2_Container_Repeat(
            null, null, array('prototype' => $fieldset)
        );
        $textOne  = new HTML_QuickForm2_Element_InputText('firstText');
        $textTwo  = new HTML_QuickForm2_Element_InputText('secondText');

        $repeat->appendChild($textOne);
        $this->assertSame($textOne->getContainer(), $fieldset);

        $repeat->insertBefore($textTwo, $textOne);
        $this->assertSame($textTwo->getContainer(), $fieldset);

        $repeat->removeChild($textOne);
        $this->assertNull($textOne->getContainer());
    }

    public function testSetIndexesExplicitly()
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $this->assertEquals(array(), $repeat->getIndexes());

        $repeat->setIndexes(array('foo', 'bar', 'baz', 'qu\'ux', 'baz', 25));
        $this->assertEquals(array('foo', 'bar', 'baz', 25), $repeat->getIndexes());
    }

    public function testSetIndexFieldExplicitly()
    {
        $form = new HTML_QuickForm2('testIndexField');
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'blah' => array(
                'blergh'    => 'a',
                'blurgh'    => 'b',
                'ba-a-a-ah' => 'c',
                42          => 'd'
            ),
            'argh' => array(
                'a'    => 'e',
                'b\'c' => 'f',
                'd'    => 'g'
            )
        )));

        $repeat = new HTML_QuickForm2_Container_Repeat();
        $repeat->setIndexField('blah');
        $repeat->setIndexes(array('foo', 'bar'));
        $form->appendChild($repeat);
        $this->assertEquals(array('blergh', 'blurgh', 42), $repeat->getIndexes());

        $repeat->setIndexField('argh');
        $this->assertEquals(array('a', 'd'), $repeat->getIndexes());
    }

    public function testGuessIndexField()
    {
        $form = new HTML_QuickForm2('guessIndexField');
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'blah'   => array('foo' => 1),
            'bzz'    => array('bar' => array('a', 'b')),
            'aaargh' => array('foo' => ''),
            'blergh' => array('foo' => '', 'bar' => 'bar value')
        )));

        $repeat = new HTML_QuickForm2_Container_Repeat();
        $form->appendChild($repeat);

        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat->setPrototype($fieldset);
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addCheckbox('blah');
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addSelect('bzz', array('multiple'));
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addText('aaargh', array('disabled'));
        $this->assertEquals(array(), $repeat->getIndexes());

        $fieldset->addText('blergh');
        $this->assertEquals(array('foo', 'bar'), $repeat->getIndexes());
    }

    public function testGetValue()
    {
        $values = array(
            'foo' => array('a' => 'a value', 'b' => 'b value', 'c' => 'c value'),
            'bar' => array(
                'baz' => array('a' => 'aa', 'b' => 'bb', 'c' => 'cc')
            )
        );

        $form   = new HTML_QuickForm2('repeatValue');
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($values));
        $form->appendChild($repeat);

        $fieldset = new HTML_QuickForm2_Container_Fieldset();
        $repeat->setPrototype($fieldset);

        $fieldset->addText('foo');
        $fieldset->addText('bar[baz]');

        $this->assertEquals($values, $repeat->getValue());

        $repeat->setIndexes(array('a', 'c'));
        unset($values['foo']['b'], $values['bar']['baz']['b']);
        $this->assertEquals($values, $repeat->getValue());
    }

    public function testFrozenRepeatShouldNotContainJavascript()
    {
        $repeat = new HTML_QuickForm2_Container_Repeat();
        $repeat->setPrototype(new HTML_QuickForm2_Container_Fieldset());
        $repeat->toggleFrozen(true);

        $this->assertNotContains('<script', $repeat->__toString());
    }
}
?>