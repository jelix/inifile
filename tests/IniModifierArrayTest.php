<?php
/**
* @author      Laurent Jouanneau
* @copyright   2017 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(__DIR__.'/lib.php');

class IniModifierArrayTest extends PHPUnit_Framework_TestCase {

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

        $multi = new testIniFileModifierArray(array($master, $overrider));
        $values = $multi->getValues();
        $this->assertEquals(array('he'=>'ho'), $values);

        $values = $multi->getValues('section');
        $this->assertEquals(array(
                            'foo'=>'baz',
                            'arr'=>array('hello', 'world', 'beautiful'),
                            'car'=>'mercedes'), $values);
    }

    protected function getModifiersArray() {
        $one = new testIniFileModifier();
        $two = new testIniFileModifier();
        $three = new testIniFileModifier();
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

        $three->testParse(
            '
            
he=klo
[section]
arr[]=zoli
happy=new year
'
        );


        return new testIniFileModifierArray(array($one, 'two'=>$two, $three));
    }

    function testGetValue() {


        $multi = $this->getModifiersArray();
        $this->assertEquals('klo', $multi->getValue('he'));
        $this->assertEquals('b', $multi->getValue('z'));
        $this->assertEquals('value', $multi->getValue('deep'));
        $this->assertEquals('newvalue', $multi->getValue('otherdeep'));

        $this->assertEquals('baz', $multi->getValue('foo', 'section'));
        $this->assertEquals(array('zoli'), $multi->getValue('arr', 'section'));
        $this->assertEquals('mercedes', $multi->getValue('car', 'section'));
        $this->assertEquals('new year', $multi->getValue('happy', 'section'));
    }


    function testIterator() {
        $multi = $this->getModifiersArray();
        $result = array();
        $keys = array();
        foreach($multi as $k=>$mod) {
            $result[] = $mod;
            $keys[] = $k;

        }
        $this->assertEquals('newvalue', $result[1]->getValue('otherdeep'));
        $this->assertEquals('value', $result[0]->getValue('otherdeep'));
        $this->assertEquals('klo', $result[2]->getValue('he'));
        $this->assertEquals(array(0, 'two', 1), $keys);
    }

    function testArrayAccess() {
        $multi = $this->getModifiersArray();
        $two = $multi['two'];
        $this->assertEquals('newvalue', $two->getValue('otherdeep'));
        $one = $multi[0];
        $this->assertEquals('value', $one->getValue('otherdeep'));
        $three = $multi[1];
        $this->assertEquals('klo', $three->getValue('he'));
    }
}