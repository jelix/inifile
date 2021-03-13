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

class IniModifierRemoveCommentsTest extends PHPUnit_Framework_TestCase {

    protected function prepareParserRemoveComments()
    {
        $content = '
; global foo comment  
  
foo=bar

[aSection]
; truc comment
truc=machin
; flag comment 1
; flag comment 2
flag=on
; bedo a comment
bedo[a] = "bedo a"
; bedo b comment
bedo[b] = "bedo b"
noflag=off
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
                array(IniModifier::TK_COMMENT, "; truc comment"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_COMMENT, "; flag comment 1"),
                array(IniModifier::TK_COMMENT, "; flag comment 2"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_COMMENT, "; bedo a comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo a', 'a'),
                array(IniModifier::TK_COMMENT, "; bedo b comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo b', 'b'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);

        $this->assertEquals($expected, $parser->getContent());
        $this->assertFalse($parser->isModified());
        return $parser;
    }

    function testRemoveComments()
    {
        $parser = $this->prepareParserRemoveComments();

        // remove single comment - global
        $parser->removeComments('foo');

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_COMMENT, "; truc comment"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_COMMENT, "; flag comment 1"),
                array(IniModifier::TK_COMMENT, "; flag comment 2"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_COMMENT, "; bedo a comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo a', 'a'),
                array(IniModifier::TK_COMMENT, "; bedo b comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo b', 'b'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $this->assertEquals($expected, $parser->getContent());
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        // remove single comment
        $parser->removeComments('truc', 'aSection');

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_COMMENT, "; flag comment 1"),
                array(IniModifier::TK_COMMENT, "; flag comment 2"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_COMMENT, "; bedo a comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo a', 'a'),
                array(IniModifier::TK_COMMENT, "; bedo b comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo b', 'b'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $this->assertEquals($expected, $parser->getContent());
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        // remove multiline comment
        $parser->removeComments('flag', 'aSection');

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_COMMENT, "; bedo a comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo a', 'a'),
                array(IniModifier::TK_COMMENT, "; bedo b comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo b', 'b'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $this->assertEquals($expected, $parser->getContent());
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();

        // remove single comment with key
        $parser->removeComments('bedo', 'aSection', 'b');

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_VALUE, 'flag', 'on'),
                array(IniModifier::TK_COMMENT, "; bedo a comment"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo a', 'a'),
                array(IniModifier::TK_WS, "--"),
                array(IniModifier::TK_ARR_VALUE, 'bedo', 'bedo b', 'b'),
                array(IniModifier::TK_VALUE, 'noflag', 'off'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $this->assertEquals($expected, $parser->getContent());
        $this->assertTrue($parser->isModified());
        $parser->clearModifierFlag();
    }
}
