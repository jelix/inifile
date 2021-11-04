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

class IniModifierGetTest extends \PHPUnit\Framework\TestCase {

    function testGetValue() {
        $parser = new testIniFileModifier('foo.ini', '
  ; a comment
  
foo=bar
anumber=98
string= "uuuuu"
string2= "aaa
bbb"
string3= "aaa
  multiline
bbb"
afloatnumber=   5.098  

[aSection]
laurent=toto
trucon = on
machintrue = true
bidule1 = 1
chouetyes = yes
trucoff = off
machinfalse = false
bidule0 = 0
chouetno = no
bizarre=none

[othersection]
truc=machin2

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc

[the_section]
truck=on
foo[key1]=aaa
; key comment
foo[key2]=true
foo[key4]=off
foo[key3]=ccc
');

        $this->assertEquals($parser->getValue('foo'), 'bar' );
        $this->assertEquals($parser->getValue('anumber'), 98 );
        $this->assertEquals($parser->getValue('string'), 'uuuuu' );
        $this->assertEquals($parser->getValue('string2'), 'aaa
bbb');
        $this->assertEquals($parser->getValue('string3'), 'aaa
  multiline
bbb');
        $this->assertEquals($parser->getValue('afloatnumber'), 5.098 );
        $this->assertEquals($parser->getValue('truc','aSection'), null );
        $this->assertEquals($parser->getValue('laurent','aSection'), 'toto' );
        $this->assertEquals($parser->getValue('trucon','aSection'), true );
        $this->assertEquals($parser->getValue('machintrue','aSection'), true );
        $this->assertEquals($parser->getValue('bidule1','aSection'), 1 );
        $this->assertEquals($parser->getValue('chouetyes','aSection'), true );
        $this->assertEquals($parser->getValue('trucoff','aSection'), false );
        $this->assertEquals($parser->getValue('machinfalse','aSection'), false );
        $this->assertEquals($parser->getValue('bidule0','aSection'), 0 );
        $this->assertEquals($parser->getValue('chouetno','aSection'), false );
        $this->assertEquals($parser->getValue('bizarre','aSection'), false );
        $this->assertEquals($parser->getValue('foo','vla',2), 'ccc' );
        $this->assertEquals($parser->getValue('foo','vla'), array('aaa', 'bbb', 'ccc'));
        $this->assertEquals($parser->getValue('foo','the_section'), array('key1'=>'aaa', 'key2'=>true, 'key4'=>false, 'key3'=>'ccc'));
    }


    public function testGetValues() {
        $content = '
; a comment <?php die()
  
foo=bar

; section comment
[aSection]
trucon = on
machintrue = true
bidule1 = 1
chouetyes = yes
trucoff = off
machinfalse = false
bidule0 = 0
chouetno = no
bizarre=none

; super section
[the_section]
truc=machin
bidule=1
truck=on
foo[]=aaa
; key comment
foo[]=true
foo[]=off
foo[]=ccc


';

        $ini = new testIniFileModifier('foo.ini', $content);

        $values = $ini->getValues('the_section');
        $expected = array('truc'=>'machin', 'bidule'=>1, 'truck'=>true, 'foo'=>array('aaa', true, false, 'ccc'));
        $this->assertEquals($expected, $values);

        $values = $ini->getValues(0);
        $expected = array('foo'=>'bar');
        $this->assertEquals($expected, $values);

        $values = $ini->getValues('aSection');
        $expected = array(
            'trucon' => true,
            'machintrue' => true,
            'bidule1' => 1,
            'chouetyes' => true,
            'trucoff' => false,
            'machinfalse' => false,
            'bidule0' => 0,
            'chouetno' => false,
            'bizarre'=>false,
        );
        $this->assertEquals($expected, $values);


    }

    public function testGetValuesAssocArray() {
        $content = '
; a comment <?php die()
  
foo=bar

; section comment
[aSection]
truc=true

; super section
[the_section]
truc=machin
bidule=1
truck=on
foo[key1]=aaa
; key comment
foo[key2]=true
foo[key4]=off
foo[key3]=ccc
';

        $ini = new testIniFileModifier('foo.ini', $content);

        $values = $ini->getValues('the_section');
        $expected = array('truc'=>'machin', 'bidule'=>1, 'truck'=>true, 'foo'=>array('key1'=>'aaa', 'key2'=>true, 'key4'=>false, 'key3'=>'ccc'));
        $this->assertEquals($expected, $values);

        $values = $ini->getValues(0);
        $expected = array('foo'=>'bar');
        $this->assertEquals($expected, $values);
    }

}
