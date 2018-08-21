<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2008-2015 Laurent Jouanneau
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
use \Jelix\IniFile\IniModifier as IniModifier;
use \Jelix\IniFile\MultiIniModifier as MultiIniModifier;

require_once(__DIR__.'/lib.php');

class IniModifierRemoveTest extends PHPUnit_Framework_TestCase {

    function testRemove() {

        $content = '
  ; a comment
  
foo=bar
;bla bla
anumber=98
string= "uuuuu"
string2= "aaa
bbb"
assoc[oth]=buser
assoc[oth2]=buser2
afloatnumber=   5.098  

[aSection]
truc= true
laurent=toto
isvalid = on

[othersection]
truc=machin2

assoc[key1]=car
assoc[otherkey]=bus

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc


';
        $parser = new testIniFileModifier('foo.ini', $content);
        $parser->removeValue('anumber', 0, null, false);
        $this->assertNull($parser->getValue('anumber'));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $parser->removeValue('laurent','aSection', null, false);
        $this->assertNull($parser->getValue('laurent','aSection'));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $parser->removeValue('foo','vla', 1, false);
        $this->assertNull($parser->getValue('foo','vla', 1));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $parser->removeValue('assoc','othersection', null, false);
        $this->assertNull($parser->getValue('assoc','othersection'));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $parser->removeValue('assoc',0, 'oth2', false);
        $this->assertNull($parser->getValue('assoc', 0, 'oth2'));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $parser->removeSection('aSection', false);
        $this->assertNull($parser->getValue('truc','aSection'));
        $this->assertEquals($parser->getSectionList(), array('othersection', 'vla'));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $result = '
  ; a comment
  
foo=bar
;bla bla
string=uuuuu
string2="aaa
bbb"
assoc[oth]=buser
afloatnumber=5.098

[othersection]
truc=machin2


[vla]
foo[]=aaa
foo[]=ccc


';
        $this->assertEquals($result, $parser->generate());
    }


    function testRemoveUnknownOption() {

        $content = '
  ; a comment
  
foo=bar
;bla bla
anumber=98
string= "uuuuu"
string2= "aaa
bbb"
assoc[oth]=buser
assoc[oth2]=buser2
afloatnumber=   5.098  

[aSection]
truc= true
laurent=toto
isvalid = on

[othersection]
truc=machin2

assoc[key1]=car
assoc[otherkey]=bus

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc


';
        $parser = new testIniFileModifier('foo.ini', $content);
        $parser->removeValue('zzzzz', 0, null, false);
        $this->assertNull($parser->getValue('zzzzz'));
        $this->assertFalse($parser->isModified());

        $parser->removeValue('laurentzzz','aSection', null, false);
        $this->assertNull($parser->getValue('laurentzzz','aSection'));
        $this->assertFalse($parser->isModified());

        $parser->removeValue('foo','vla', 5, false);
        $this->assertNull($parser->getValue('foo','vla', 5));
        $this->assertFalse($parser->isModified());

        $parser->removeValue('assoczzz','othersection', null, false);
        $this->assertNull($parser->getValue('assoczzz','othersection'));
        $this->assertFalse($parser->isModified());

        $parser->removeValue('assoc',0, 'oth3', false);
        $this->assertNull($parser->getValue('assoc', 0, 'oth3'));
        $this->assertFalse($parser->isModified());

        $parser->removeSection('aSectionzzz', false);
        $this->assertNull($parser->getValue('truc','aSectionzzz'));
        $this->assertFalse($parser->isModified());
    }


    function testRemoveArray() {
        $content = '
string= "uuuuu"
assoc[o]=buser
string2= "aaabbb"
assoc[oth]=buser
assoc[oth2]=buser2
afloatnumber=   5.098  

[othersection]
truc=machin2

assoc[key1]=car
assoc[otherkey]=bus

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc


';
        $parser = new testIniFileModifier('foo.ini', $content);
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_VALUE, 'string', 'uuuuu'),
                array(IniModifier::TK_ARR_VALUE, 'assoc', 'buser', 'o'),
                array(IniModifier::TK_VALUE, 'string2', 'aaabbb'),
                array(IniModifier::TK_ARR_VALUE, 'assoc', 'buser', 'oth'),
                array(IniModifier::TK_ARR_VALUE, 'assoc', 'buser2', 'oth2'),
                array(IniModifier::TK_VALUE, 'afloatnumber', 5.098),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'assoc', 'car', 'key1'),
                array(IniModifier::TK_ARR_VALUE, 'assoc', 'bus', 'otherkey'),
                array(IniModifier::TK_WS, ""),
            ),
            'vla' => array(
                array(IniModifier::TK_SECTION, "[vla]"),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'aaa', 0),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'bbb', 1),
                array(IniModifier::TK_ARR_VALUE, 'foo', 'ccc', 2),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

        $parser->removeValue('assoc', 0, null, false);
        $this->assertNull($parser->getValue('assoc'));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $parser->removeValue('foo','vla', null, false);
        $this->assertNull($parser->getValue('foo','vla', 1));
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_VALUE, 'string', 'uuuuu'),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_VALUE, 'string2', 'aaabbb'),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_VALUE, 'afloatnumber', 5.098),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'assoc', 'car', 'key1'),
                array(IniModifier::TK_ARR_VALUE, 'assoc', 'bus', 'otherkey'),
                array(IniModifier::TK_WS, ""),
            ),
            'vla' => array(
                array(IniModifier::TK_SECTION, "[vla]"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );
        $this->assertEquals($expected, $parser->getContent());

    }


    function testRemoveWithComment() {
        $content = '
  ; a comment <?php die()
  
foo=bar
anumber=98
string= "uuuuu"
string2= "aaa
bbb"
afloatnumber=   5.098  

; section comment
[aSection]
truc= true

; a comment

laurent=toto
isvalid = on

; super section
[othersection]
truc=machin2

[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


';
        $parser = new testIniFileModifier('foo.ini', $content);
        $parser->removeValue('anumber', 0, null, true);
        $parser->removeValue('laurent','aSection', null, true);
        $parser->removeValue('foo','vla', 1, true);
        $parser->removeValue('', 'othersection', null, true);
        $parser->removeValue('foo',0, null, true);
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        $result = '
  ; a comment <?php die()
  
string=uuuuu
string2="aaa
bbb"
afloatnumber=5.098

; section comment
[aSection]
truc=true


isvalid=on

[vla]
foo[]=aaa
foo[]=ccc


';
        $this->assertEquals($result, $parser->generate());


        $content = '
string=uuuuu

; bla bla

; bli bli
;blo blo

string2=aaa
afloatnumber=5.098  

';
        $parser = new testIniFileModifier('foo.ini', $content);
        $parser->removeValue('string2', 0, null, true);

        $result = '
string=uuuuu

; bla bla


afloatnumber=5.098

';
        $this->assertEquals($result, $parser->generate());

    }


}
