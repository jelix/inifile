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

class IniModifierSetCommentsTest extends PHPUnit_Framework_TestCase {

    protected function prepareParserSetComments()
    {
        $content = '
; global foo comment  
  
foo=bar

[aSection]
truc=machin
; flag comment
flag=on
noflag=off

[othersection]
truc=machin2

';
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "; global foo comment  "),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_COMMENT, "; flag comment"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);

        $this->assertEquals($expected, $parser->getContent());
        $this->assertFalse($parser->isModified());
        return $parser;
    }

    function testSetComments()
    {
        $parser = $this->prepareParserSetComments();

        // set single comment - global
        $parser->setComments('foo', "; new global foo comment");

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_COMMENT, "; new global foo comment"),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_COMMENT, "; flag comment"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
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
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        // set single comment - in section
        $parser->setComments('truc', "truc comment", 'aSection');

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_COMMENT, "; new global foo comment"),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_COMMENT, ";truc comment"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_COMMENT, "; flag comment"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
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
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        // set multiline comment
        $parser->setComments('truc', ["othersection truc line 1" , "othersection truc line 2"], 'othersection');

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_COMMENT, "; new global foo comment"),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_COMMENT, ";truc comment"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_COMMENT, "; flag comment"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
            'othersection' => array(
                array(IniModifier::TK_SECTION, "[othersection]"),
                array(IniModifier::TK_COMMENT, ";othersection truc line 1"),
                array(IniModifier::TK_COMMENT, ";othersection truc line 2"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $this->assertEquals($expected, $parser->getContent());
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();
    }

    protected function prepareParserSetCommentsWithKey()
    {
        $content = '
bedo[a]="bedo a"
bedo[b]="bedo b"

[aSection]
truc[a]="machin a"
truc[b]="machin b"
; flag comment
flag=on
noflag=off
';
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo a', 'a'),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo b', 'b'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_ARR_VALUE, 'truc', 'machin a', 'a'),
                array(IniModifier::TK_ARR_VALUE, 'truc', 'machin b', 'b'),
                array(IniModifier::TK_COMMENT, "; flag comment"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);

        $this->assertEquals($expected, $parser->getContent());
        $this->assertFalse($parser->isModified());
        return $parser;
    }

    function testSetCommentsWithKey()
    {
        $parser = $this->prepareParserSetCommentsWithKey();

        // set single comment - global
        $parser->setComments('bedo', "; bedo a comment", 0, 'a');

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "; bedo a comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo a', 'a'),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo b', 'b'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_ARR_VALUE, 'truc', 'machin a', 'a'),
                array(IniModifier::TK_ARR_VALUE, 'truc', 'machin b', 'b'),
                array(IniModifier::TK_COMMENT, "; flag comment"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $this->assertEquals($expected, $parser->getContent());
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();
    }
}
