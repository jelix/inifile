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
; @preferedFile $current-file-name
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
; @defaultPreferedFile $current-file-name
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

    function testPreferedFileWithSectionParamCanBeAnywhere() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile exemple.ini exemple
foo=bar
[exemple]
baz=qux
');
        $this->assertEquals(array('exemple' => 'exemple.ini'), $ini->getPreferedFiles());
    }

    function testPreferedFileWithSectionParamForNonExistentSection() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile secrets.ini db
[other]
foo=bar
');
        $this->assertEquals(array('db' => 'secrets.ini'), $ini->getPreferedFiles());
    }

    function testPreferedFileWithSectionParamSelf() {
        $ini = new testIniFileModifier('/some/path/myconfig.ini', '
; @preferedFile $current-file-name db
[other]
foo=bar
');
        $this->assertEquals(array('db' => 'myconfig.ini'), $ini->getPreferedFiles());
    }

    function testPreferedFileWithSectionParamDoesNotAttachToFollowingSection() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile secrets.ini db
[exemple]
foo=bar
');
        $this->assertEquals(array('db' => 'secrets.ini'), $ini->getPreferedFiles());
    }

    function testMixOfOneAndTwoParameterForms() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile shared.ini other
; @preferedFile exemple-specific.ini
[exemple]
foo=1
[other]
bar=2
');
        $this->assertEquals(
            array('exemple' => 'exemple-specific.ini', 'other' => 'shared.ini'),
            $ini->getPreferedFiles()
        );
    }

    function testRoundTripPreservesTwoParameterCommentVerbatim() {
        $content = '
; @preferedFile secrets.ini db
[exemple]
foo=bar
';
        $ini = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($content, $ini->generate());
    }

    function testPreferedFileWildcardMatchesPrefixedSections() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile shared.ini foo*
[foo]
a=1
[foobar]
b=2
');
        $this->assertEquals(
            array('foo' => 'shared.ini', 'foobar' => 'shared.ini', 'foo*' => 'shared.ini'),
            $ini->getPreferedFiles()
        );
    }

    function testPreferedFileWildcardDoesNotMatchUnrelatedSection() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile shared.ini foo*
[barfoo]
a=1
');
        $this->assertArrayNotHasKey('barfoo', $ini->getPreferedFiles());
    }

    function testPreferedFileLongestWildcardPrefixWins() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile general.ini foo*
; @preferedFile specific.ini foobar*
[foobarbaz]
a=1
');
        $this->assertEquals(
            array(
                'foobarbaz' => 'specific.ini',
                'foo*' => 'general.ini',
                'foobar*' => 'specific.ini',
            ),
            $ini->getPreferedFiles()
        );
    }

    function testPreferedFileWildcardPassesThroughWhenNoSectionMatchesInThisFile() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile shared.ini foo*
[other]
a=1
');
        $this->assertEquals(
            array('foo*' => 'shared.ini'),
            $ini->getPreferedFiles()
        );
    }

    function testPreferedFileExactSectionWinsOverWildcard() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferedFile shared.ini foo*
; @preferedFile literal.ini
[foo]
a=1
');
        $this->assertEquals(
            array('foo' => 'literal.ini', 'foo*' => 'shared.ini'),
            $ini->getPreferedFiles()
        );
    }
}
