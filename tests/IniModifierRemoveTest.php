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
        $parser = new testIniFileModifier('');
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
        $parser->testParse($content);
        $parser->removeValue('anumber', 0, null, false);
        $this->assertNull($parser->getValue('anumber'));

        $parser->removeValue('laurent','aSection', null, false);
        $this->assertNull($parser->getValue('laurent','aSection'));

        $parser->removeValue('foo','vla', 1, false);
        $this->assertNull($parser->getValue('foo','vla', 1));

        $parser->removeValue('assoc','othersection', null, false);
        $this->assertNull($parser->getValue('assoc','othersection'));

        $parser->removeValue('assoc',0, 'oth2', false);
        $this->assertNull($parser->getValue('assoc', 0, 'oth2'));

        $parser->removeValue('', 'aSection', null, false);
        $this->assertNull($parser->getValue('truc','aSection'));
        $this->assertEquals($parser->getSectionList(), array('othersection', 'vla'));

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

    function testRemoveWithComment() {
        $parser = new testIniFileModifier('');
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
        $parser->testParse($content);
        $parser->removeValue('anumber', 0, null, true);
        $parser->removeValue('laurent','aSection', null, true);
        $parser->removeValue('foo','vla', 1, true);
        $parser->removeValue('', 'othersection', null, true);
        $parser->removeValue('foo',0, null, true);

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





        $parser = new testIniFileModifier('');
        $content = '
string=uuuuu

; bla bla

; bli bli
;blo blo

string2=aaa
afloatnumber=5.098  

';
        $parser->testParse($content);
        $parser->removeValue('string2', 0, null, true);

        $result = '
string=uuuuu

; bla bla


afloatnumber=5.098

';
        $this->assertEquals($result, $parser->generate());

    }


}
