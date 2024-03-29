<?php
/**
* @author      Laurent Jouanneau
* @copyright   2008-2024 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/
use \Jelix\IniFile\IniModifier as IniModifier;
use \Jelix\IniFile\MultiIniModifier as MultiIniModifier;

require_once(__DIR__.'/lib.php');

class IniModifierTest extends \PHPUnit\Framework\TestCase {

    public function testParseEmptyFile()
    {
        $content = '';
        $expected = array(
            0 => array(
            ),
        );
        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertTrue($parser->isEmpty());
        $this->assertEquals($expected, $parser->getContent());
    }

    public function testParseFile()
    {
        $content = 'foo=bar';
        $expected = array(
            0 => array(
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
            ),
        );
        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());
    }

    public function testParseFileComment()
    {
        $content = '
  ; a comment
  
foo=bar
# other comment
';
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_WS, "# other comment"),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());

        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_COMMENT, "# other comment"),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content, \Jelix\IniFile\IniReaderInterface::FORMAT_COMMENT_HASH);
        $this->assertEquals($expected, $parser->getContent());
    }

    public function testParseFileSection()
    {
        $content = '
  ; a comment
  
foo=bar

[aSection]
truc=machin
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
            )
        );

        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());
    }

    public function testParseFileSectionCoolName()
    {
        $content = '
  ; a comment
  
foo=bar
àç09=etot

[aSection]
truc=machin

[qsk:!893840012+4°05£%£$^*%!:,=){}]
truc=machin2

[some[other]brackets]
truc=machin3
';
        $expected = array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_COMMENT, "  ; a comment"),
                array(IniModifier::TK_WS, "  "),
                array(IniModifier::TK_VALUE, 'foo', 'bar'),
                array(IniModifier::TK_VALUE, 'àç09', 'etot'),
                array(IniModifier::TK_WS, ""),
            ),
            'aSection' => array(
                array(IniModifier::TK_SECTION, "[aSection]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin'),
                array(IniModifier::TK_WS, ""),
            ),
            'qsk:!893840012+4°05£%£$^*%!:,=){}' => array(
                array(IniModifier::TK_SECTION, "[qsk:!893840012+4°05£%£$^*%!:,=){}]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin2'),
                array(IniModifier::TK_WS, ""),
            ),
            // parse_ini_file stops at first ], so we does to.
            'some[other' => array(
                array(IniModifier::TK_SECTION, "[some[other]"),
                array(IniModifier::TK_VALUE, 'truc', 'machin3'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());

    }

    /**
     * test when there is a ';' into the name (mimic parse_ini_file)
     */
    public function testParseFileBadSectionName()
    {
        $content = '
  ; a comment
  
foo=bar
[qsk;qsd]
truc=machin2

';
        $this->expectException('\Jelix\IniFile\IniSyntaxException');
        $parser = new testIniFileModifier('foo.ini', $content);
    }

    public function testParseFileArray()
    {
        $content ='
foo[]=bar
example=1
foo[]=machine
';
        $expected=array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo','bar',0),
                 array(IniModifier::TK_VALUE, 'example','1'),
                array(IniModifier::TK_ARR_VALUE, 'foo','machine',1),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());
    }

    public function testParseFileAssocArray()
    {
        $content ='
foo[key1]=bar
example=1
foo[key2]=machine
';
        $expected=array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo','bar','key1'),
                array(IniModifier::TK_VALUE, 'example','1'),
                array(IniModifier::TK_ARR_VALUE, 'foo','machine','key2'),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());
    }
    public function testParseFileMixedArray()
    {
        $content ='
foo[key1]=bar
example=1
foo[]=machine
';
        $expected=array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo','bar','key1'),
                array(IniModifier::TK_VALUE, 'example','1'),
                array(IniModifier::TK_ARR_VALUE, 'foo','machine',1),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());

        $content ='
foo[]=bar
example=1
foo[key1]=machine
foo[]=hello
';
        $expected=array(
            0 => array(
                array(IniModifier::TK_WS, ""),
                array(IniModifier::TK_ARR_VALUE, 'foo','bar',0),
                array(IniModifier::TK_VALUE, 'example','1'),
                array(IniModifier::TK_ARR_VALUE, 'foo','machine','key1'),
                array(IniModifier::TK_ARR_VALUE, 'foo','hello',2),
                array(IniModifier::TK_WS, ""),
            ),
        );

        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($expected, $parser->getContent());

    }

    function testSave() {
        $content = '
  ; a comment
  
foo=bar
job= foo.b-a_r
messageLogFormat = "%date%\t[%code%]\t%msg%\t%file%\t%line%\n"
anumber=98
afloatnumber=   5.098  
[aSection]
truc= true
laurent=toto@machin.com
isvalid = on

[othersection]
truc=machin2

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc

[sudoku]
case[key1]=aaa
case[ab]=bbb
case[key3]=ccc

';
        $result = '
  ; a comment
  
foo=bar
job=foo.b-a_r
messageLogFormat="%date%\t[%code%]\t%msg%\t%file%\t%line%\n"
anumber=98
afloatnumber=5.098
[aSection]
truc=true
laurent="toto@machin.com"
isvalid=on

[othersection]
truc=machin2

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc

[sudoku]
case[key1]=aaa
case[ab]=bbb
case[key3]=ccc

';
        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($result, $parser->generate() );

        file_put_contents(TEMP_PATH.'test_IniModifier.html_cli.php', $content);
        $parser = new testIniFileModifier(TEMP_PATH.'test_IniModifier.html_cli.php');
        $this->assertEquals($result, $parser->generate() );

        $content = str_replace("\n", "\r", $content);
        file_put_contents(TEMP_PATH.'test_IniModifier.html_cli.php', $content);
        $parser = new testIniFileModifier(TEMP_PATH.'test_IniModifier.html_cli.php');
        $this->assertEquals($result, $parser->generate() );

        $content = str_replace("\r", "\r\n", $content);
        file_put_contents(TEMP_PATH.'test_IniModifier.html_cli.php', $content);
        $parser = new testIniFileModifier(TEMP_PATH.'test_IniModifier.html_cli.php');
        $this->assertEquals($result, $parser->generate());
    }

    function testSaveWithoutQuote() {
        $content = '
  ; a comment
  
foo=bar
job= foo.b-a_r
messageLogFormat = "%date%\t[%code%]\t%msg%\t%file%\t%line%\n"
anumber=98
afloatnumber=   5.098  
[aSection]
truc= true
laurent=toto@machin.com
isvalid = on

[othersection]
truc=machin2

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc

[sudoku]
case[key1]=aaa
case[ab]=bbb
case[key3]=ccc

';
        $result = '
  ; a comment
  
foo=bar
job=foo.b-a_r
messageLogFormat=%date%\t[%code%]\t%msg%\t%file%\t%line%\n
anumber=98
afloatnumber=5.098
[aSection]
truc=true
laurent=toto@machin.com
isvalid=on

[othersection]
truc=machin2

[vla]
foo[]=aaa
foo[]=bbb
foo[]=ccc

[sudoku]
case[key1]=aaa
case[ab]=bbb
case[key3]=ccc

';
        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($result, $parser->generate(\Jelix\IniFile\IniModifierInterface::FORMAT_NO_QUOTES) );

        file_put_contents(TEMP_PATH.'test_IniModifier.html_cli.php', $content);
        $parser = new testIniFileModifier(TEMP_PATH.'test_IniModifier.html_cli.php');
        $this->assertEquals($result, $parser->generate(\Jelix\IniFile\IniModifierInterface::FORMAT_NO_QUOTES) );

    }

    function testSaveSpaceAroundEqual() {
        $content = '
  ; a comment
  
foo=bar
job= foo.b-a_r
messageLogFormat = "%date%\t[%code%]\t%msg%\t%file%\t%line%\n"
anumber=98
afloatnumber = 5.098  
[aSection]
truc= true

[vla]
foo[]=aaa
foo[]=bbb

[sudoku]
case[key1]=aaa

';
        $result = '
  ; a comment
  
foo = bar
job = foo.b-a_r
messageLogFormat = "%date%\t[%code%]\t%msg%\t%file%\t%line%\n"
anumber = 98
afloatnumber = 5.098
[aSection]
truc = true

[vla]
foo[] = aaa
foo[] = bbb

[sudoku]
case[key1] = aaa

';
        $parser = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($result, $parser->generate(\Jelix\IniFile\IniModifierInterface::FORMAT_SPACE_AROUND_EQUAL) );


    }
}
