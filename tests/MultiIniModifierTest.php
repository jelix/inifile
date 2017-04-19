<?php
/**
* @author      Laurent Jouanneau
* @copyright   2016 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/
use \Jelix\IniFile\IniModifier as IniModifier;
use \Jelix\IniFile\MultiIniModifier as MultiIniModifier;

require_once(__DIR__.'/lib.php');

class MultiIniModifierTest extends PHPUnit_Framework_TestCase {

    function testGetValues() {
        $master = new testIniFileModifier();
        $overrider = new testIniFileModifier();
        $master->testParse(
'
[section]
foo=bar
arr[]=hello
arr[]=world
car=mercedes
'
        );

        $overrider->testParse(
'
he=ho
[section]
foo=baz
arr[]=beautiful
'
        );

        $multi = new testMultiIniFileModifier($master, $overrider);
        $values = $multi->getValues();
        $this->assertEquals(array('he'=>'ho'), $values);

        $values = $multi->getValues('section');
        $this->assertEquals(array(
                            'foo'=>'baz',
                            'arr'=>array('hello', 'world', 'beautiful'),
                            'car'=>'mercedes'), $values);
    }
    function testGetValue() {
        $one = new testIniFileModifier();
        $two = new testIniFileModifier();
        $one->testParse(
            '
deep=value
otherdeep=value
[section]
foo=bar
arr[]=hello
arr[]=world
car=mercedes
'
        );

        $two->testParse(
            '
z=b
he=ho
otherdeep= newvalue
[section]
foo=baz
arr[]=beautiful
'
        );


        $multi = new testMultiIniFileModifier($one, $two);
        $this->assertEquals('ho', $multi->getValue('he'));
        $this->assertEquals('b', $multi->getValue('z'));
        $this->assertEquals('value', $multi->getValue('deep'));
        $this->assertEquals('newvalue', $multi->getValue('otherdeep'));

        $this->assertEquals('baz', $multi->getValue('foo', 'section'));
        $this->assertEquals(array('beautiful'), $multi->getValue('arr', 'section'));
        $this->assertEquals('mercedes', $multi->getValue('car', 'section'));
    }
}