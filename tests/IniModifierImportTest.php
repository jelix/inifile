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

class IniModifierImportTest extends \PHPUnit\Framework\TestCase {

    function testImport() {

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

assoc[bb]=1978

willbearray=yes

willbestring[aa]=val1
willbestring[bb]=val2

; super section
[othersection]
truc=machin2

[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc

';
        $ini = new testIniFileModifier('foo.ini', $content);

        $content2 = '

; my comment
toto = truc
;bla
anumber=100

; section comment
[aSection]

newlaurent=hello
; a new comment
isvalid = on
truc= false

willbearray[]=ok
willbearray[]=no

supercar=ferrari

assoc[aa]=0

willbestring=a string

[newsection]
truc=machin2
arr[b]=f

foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc

arr[a]=f

';
        $ini2 = new testIniFileModifier('foo.ini', $content2);

        $ini->import($ini2);


        $result = '
  ; a comment <?php die()
  
foo=bar
anumber=100
string=uuuuu
string2="aaa
bbb"
afloatnumber=5.098


; my comment
toto=truc

; section comment
[aSection]
truc=false

; a comment

laurent=toto
isvalid=on

assoc[bb]=1978

willbearray[]=ok

willbestring="a string"

newlaurent=hello
willbearray[]=no

supercar=ferrari

assoc[aa]=0

; super section
[othersection]
truc=machin2

[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


[newsection]
truc=machin2
arr[b]=f

foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc

arr[a]=f

';
        $this->assertEquals($result, $ini->generate());

    }



    function testImportRename() {
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
[blob_thesection]
truc=machin2
bidule = 1
[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc

';
        $ini = new testIniFileModifier('foo.ini', $content);

        $content2 = '

; my comment
toto = truc
;bla
anumber=100

; section comment
[mySection]

newlaurent=hello
; a new comment
isvalid = on
truc= false

supercar=ferrari

[thesection]
truc=machin3
truck=on

';
        $ini2 = new testIniFileModifier('foo.ini', $content2);

        $ini->import($ini2, 'blob');


        $result = '
  ; a comment <?php die()
  
foo=bar
anumber=98
string=uuuuu
string2="aaa
bbb"
afloatnumber=5.098

; section comment
[aSection]
truc=true

; a comment

laurent=toto
isvalid=on

; super section
[blob_thesection]
truc=machin3
bidule=1
truck=on
[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


[blob]


; my comment
toto=truc
;bla
anumber=100

; section comment
[blob_mySection]

newlaurent=hello
; a new comment
isvalid=on
truc=false

supercar=ferrari
';
        $this->assertEquals($result, $ini->generate());



        $ini = new testIniFileModifier('foo.ini', $content);
        $ini2 = new testIniFileModifier('foo.ini', $content2);

        $ini->import($ini2, 'blob', ':');
        $result = '
  ; a comment <?php die()
  
foo=bar
anumber=98
string=uuuuu
string2="aaa
bbb"
afloatnumber=5.098

; section comment
[aSection]
truc=true

; a comment

laurent=toto
isvalid=on

; super section
[blob_thesection]
truc=machin2
bidule=1
[vla]
foo[]=aaa
; key comment
foo[]=bbb
foo[]=ccc


[blob]


; my comment
toto=truc
;bla
anumber=100

; section comment
[blob:mySection]

newlaurent=hello
; a new comment
isvalid=on
truc=false

supercar=ferrari

[blob:thesection]
truc=machin3
truck=on

';
        $this->assertEquals($result, $ini->generate());

    }

}
