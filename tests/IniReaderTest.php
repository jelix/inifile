<?php
/**
* @author      Laurent Jouanneau
* @copyright   2026 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(__DIR__.'/lib.php');

class IniReaderTest extends \PHPUnit\Framework\TestCase {

    function testPlainPreferedFile() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile exemple.ini
[exemple]
foo=bar
');
        $this->assertEquals(array('exemple' => 'exemple.ini'), $ini->getPreferedFiles());
    }

    function testPreferedFileSelf() {
        $ini = new testIniFileModifier('/some/path/myconfig.ini', '
; @preferedFile self
[exemple]
foo=bar
');
        $this->assertEquals(array('exemple' => 'myconfig.ini'), $ini->getPreferedFiles());
    }

    function testDefaultPreferedFileAppliesToAllSections() {
        $ini = new testIniFileModifier('foo.ini', '
; @defaultPreferedFile default.ini
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(
            array('exemple' => 'default.ini', 'other' => 'default.ini'),
            $ini->getPreferedFiles()
        );
    }

    function testPerSectionOverridesDefault() {
        $ini = new testIniFileModifier('foo.ini', '
; @defaultPreferedFile default.ini
; @preferedFile override.ini
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(
            array('exemple' => 'override.ini', 'other' => 'default.ini'),
            $ini->getPreferedFiles()
        );
    }

    function testDefaultPreferedFileSelf() {
        $ini = new testIniFileModifier('/some/path/myconfig.ini', '
; @defaultPreferedFile self
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(
            array('exemple' => 'myconfig.ini', 'other' => 'myconfig.ini'),
            $ini->getPreferedFiles()
        );
    }

    function testSectionWithNeitherIsOmitted() {
        $ini = new testIniFileModifier('foo.ini', '
[exemple]
foo=bar
');
        $this->assertArrayNotHasKey('exemple', $ini->getPreferedFiles());
    }

    function testMultipleSectionsMixed() {
        $ini = new testIniFileModifier('foo.ini', '
; @defaultPreferedFile default.ini
; @preferedFile override.ini
[withOwn]
foo=bar
[withDefault]
baz=qux
[none]
hop=1
');
        $this->assertEquals(
            array(
                'withOwn' => 'override.ini',
                'withDefault' => 'default.ini',
                'none' => 'default.ini',
            ),
            $ini->getPreferedFiles()
        );
    }

    function testOrdinaryCommentAboveSectionIgnored() {
        $ini = new testIniFileModifier('foo.ini', '
; just a note
[exemple]
foo=bar
');
        $this->assertEquals(array(), $ini->getPreferedFiles());
    }

    function testNoAttributesAtAll() {
        $ini = new testIniFileModifier('foo.ini', '
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(array(), $ini->getPreferedFiles());
    }

    function testRoundTripPreservesCommentVerbatim() {
        $content = '
; @defaultPreferedFile default.ini
; @preferedFile exemple.ini
[exemple]
foo=bar
';
        $ini = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($content, $ini->generate());
    }
}
