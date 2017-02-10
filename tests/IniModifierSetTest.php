<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2008-2016 Laurent Jouanneau
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
use \Jelix\IniFile\IniModifier as IniModifier;
use \Jelix\IniFile\MultiIniModifier as MultiIniModifier;

require_once(__DIR__ . '/lib.php');

class IniModifierSetTest extends PHPUnit_Framework_TestCase
{

    protected function prepareParserSetValue()
    {
        $parser = new testIniFileModifier('');
        $content = '
  ; a comment
  
foo=bar

[aSection]
truc=machin

[othersection]
truc=machin2

';
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser->testParse($content);
        $this->assertEquals($expected, $parser->getContent());
        return $parser;
    }

    function testSetValue()
    {
        $parser = $this->prepareParserSetValue();
        // set Simple value
        $parser->setValue('foo', 'hello');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'hello'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        // set value in a section
        $parser->setValue('truc', 'bidule', 'aSection');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'hello'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'bidule'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        // set value in an other section
        $parser->setValue('truc', 'bidule2', 'othersection');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'hello'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'bidule'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'bidule2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        $parser->setValue('name', 'toto', 'othersection');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'hello'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'bidule'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'bidule2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_VALUE, 'name', 'toto'),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());
    }

    function testSetArrayValue()
    {
        $parser = $this->prepareParserSetValue();
        // append an array value
        $parser->setValue('name', 'toto', 'othersection', '');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'name', 'toto', 0),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        // append an array value at the 0 key
        $parser->setValue('theme', 'blue', 'aSection', 0);
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'theme', 'blue', 0),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'name', 'toto', 0),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());
    }

    function testSetArrayValue2()
    {
        $parser = new testIniFileModifier('');
        $content = '
foo[]=bar
example=1
foo[]=machine
';
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bar', 0),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'machine', 1),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser->testParse($content);
        $this->assertEquals($expected, $parser->getContent());

        $parser->setValue('foo', 'bla', 0, '');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bar', 0),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'machine', 1),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bla', 2),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        $parser->setValue('theme', 'blue', 'aSection', '0');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bar', 0),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'machine', 1),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bla', 2),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_ARR_VALUE, 'theme', 'blue', 0),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        $parser->setValue('foo', 'button');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_VALUE, 'foo', 'button'),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_WS, '--'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, '--'),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_ARR_VALUE, 'theme', 'blue', 0),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());
    }


    function testSetArrayValueWithArray()
    {
        $parser = $this->prepareParserSetValue();
        // append an array value
        $parser->setValue('name', 'toto', 'othersection', '');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'name', 'toto', 0),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        // now set a new value which is an array
        $parser->setValue('name', array('black', 'brown', 'yellow'), 'othersection');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'name', 'black', 0),
                array(IniModifier::TK_ARR_VALUE, 'name', 'brown', 1),
                array(IniModifier::TK_ARR_VALUE, 'name', 'yellow', 2),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        // now change it by a smaller array
        $parser->setValue('name', array('red', 'white'), 'othersection');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'name', 'red', 0),
                array(IniModifier::TK_ARR_VALUE, 'name', 'white', 1),
                array(IniModifier::TK_WS, '--'),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());
    }

    function testModifyArrayValueWithArray()
    {
        $parser = new testIniFileModifier('');
        $content = '
  ; a comment
  
foo=bar

[aSection]
truc=machin
mylist[]=hello
ddd=eee
mylist[]=bar
fff=ggg

[othersection]
truc=machin2

';
        $parser->testParse($content);

        // now set a new value which is an array
        $parser->setValue('mylist', array('black', 'brown', 'yellow'), 'aSection');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_ARR_VALUE, 'mylist', 'black', 0),
                array(IniModifier::TK_VALUE, 'ddd', 'eee'),
                array(IniModifier::TK_ARR_VALUE, 'mylist', 'brown', 1),
                array(IniModifier::TK_VALUE, 'fff', 'ggg'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'mylist', 'yellow', 2),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        // now change it by a smaller array
        $parser->setValue('mylist', array('red'), 'aSection');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_ARR_VALUE, 'mylist', 'red', 0),
                array(IniModifier::TK_VALUE, 'ddd', 'eee'),
                array(IniModifier::TK_WS, '--'),
                array(IniModifier::TK_VALUE, 'fff', 'ggg'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, '--')
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

    }

    function testSetAssocArrayValue()
    {
        $parser = new testIniFileModifier('');
        $content = '
foo[]=bar
example=1
foo[key1]=machine
foo[]=vla
';
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bar', 0),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'machine', 'key1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'vla', 2),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser->testParse($content);
        $this->assertEquals($expected, $parser->getContent());

        $parser->setValue('foo', 'bla', 0, 'champ');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bar', 0),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'machine', 'key1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'vla', 2),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bla', 'champ'),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        $parser->setValue('foo', 'modif', 0, 'key1');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bar', 0),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'modif', 'key1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'vla', 2),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bla', 'champ'),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        $parser->setValue('foo', 'modif2', 0, 'champ');
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bar', 0),
                array(IniModifier::TK_VALUE, 'example', '1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'modif', 'key1'),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'vla', 2),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'modif2', 'champ'),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());
    }


    public function testSetValues() {
        $content = '
; a comment <?php die()
  
foo=bar

; section comment
[aSection]
truc=true

; super section
[the_section]
foo= z
';
        $ini = new testIniFileModifier('');
        $ini->testParse($content);
        $ini->setValues(
            array(
                'truc'=>'machin',
                'bidule'=>1,
                'truck'=>true,
                'foo'=>array('aaa', 'bbb', 'machin'=>'ccc')
            ),
            'the_section');
        $expected = '
; a comment <?php die()
  
foo=bar

; section comment
[aSection]
truc=true

; super section
[the_section]

truc=machin
bidule=1
truck=1
foo[]=aaa
foo[]=bbb
foo[machin]=ccc
';
        $this->assertEquals($expected, $ini->generate());
    }



    public function testSetExample() {
        $content = 'foo[]=bar
foo[]=baz
assoc[key1]=car
assoc[otherkey]=bus
';
        $ini = new testIniFileModifier('');
        $ini->testParse($content);
        $ini->setValue('foo', 'other value', 0, '');
        $ini->setValue('foo', 'five', 0, 5);
        $ini->setValue('assoc', 'other value', 0, 'ov');
        $expected = 'foo[]=bar
foo[]=baz
assoc[key1]=car
assoc[otherkey]=bus

foo[]="other value"
foo[]=five
assoc[ov]="other value"
';
        $this->assertEquals($expected, $ini->generate());
    }

}
