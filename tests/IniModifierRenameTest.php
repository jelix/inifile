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

class IniModifierRenameTest extends PHPUnit_Framework_TestCase {

    public function testRenameSection() {
        $ini = new testIniFileModifier('');
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
[thesection]
truc=machin2
bidule = 1
[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


';
        $ini->testParse($content);
        $ini->renameValue('string', 'vuvuzela');
        $ini->renameSection('aSection', 'beautiful');
        $result = '
  ; a comment <?php die()
  
foo=bar
anumber=98
vuvuzela=uuuuu
string2="aaa
bbb"
afloatnumber=5.098

; section comment
[beautiful]
truc=true

; a comment

laurent=toto
isvalid=on

; super section
[thesection]
truc=machin2
bidule=1
[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


';
        $this->assertEquals($result, $ini->generate());

        $ini->renameSection('0', 'zipo');
        $result = '[zipo]

  ; a comment <?php die()
  
foo=bar
anumber=98
vuvuzela=uuuuu
string2="aaa
bbb"
afloatnumber=5.098

; section comment
[beautiful]
truc=true

; a comment

laurent=toto
isvalid=on

; super section
[thesection]
truc=machin2
bidule=1
[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


';
        $this->assertEquals($result, $ini->generate());

        $ini->renameValue('truc', 'system', 'thesection');

        $result = '[zipo]

  ; a comment <?php die()
  
foo=bar
anumber=98
vuvuzela=uuuuu
string2="aaa
bbb"
afloatnumber=5.098

; section comment
[beautiful]
truc=true

; a comment

laurent=toto
isvalid=on

; super section
[thesection]
system=machin2
bidule=1
[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


';
        $this->assertEquals($result, $ini->generate());

        $ini->renameValue('foo', 'superfoo', 'vla');

        $result = '[zipo]

  ; a comment <?php die()
  
foo=bar
anumber=98
vuvuzela=uuuuu
string2="aaa
bbb"
afloatnumber=5.098

; section comment
[beautiful]
truc=true

; a comment

laurent=toto
isvalid=on

; super section
[thesection]
system=machin2
bidule=1
[vla]
superfoo[]=aaa
; key comment
superfoo[]=bbb
superfoo[]=ccc


';
        $this->assertEquals($result, $ini->generate());
    }


}
