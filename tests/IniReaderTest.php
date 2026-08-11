<?php
/**
* @author      Laurent Jouanneau
* @copyright   2026 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(__DIR__.'/lib.php');

class IniReaderTest extends \PHPUnit\Framework\TestCase {

    function testPlainPreferredFile() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile exemple.ini
[exemple]
foo=bar
');
        $this->assertEquals(array('exemple' => 'exemple.ini'), $ini->getPreferredFiles());
    }

    function testPreferredFileSelf() {
        $ini = new testIniFileModifier('/some/path/myconfig.ini', '
; @preferredFile $current-file-name
[exemple]
foo=bar
');
        $this->assertEquals(array('exemple' => 'myconfig.ini'), $ini->getPreferredFiles());
    }

    function testDefaultPreferredFileAppliesToAllSections() {
        $ini = new testIniFileModifier('foo.ini', '
; @defaultPreferredFile default.ini
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(
            array('exemple' => 'default.ini', 'other' => 'default.ini'),
            $ini->getPreferredFiles()
        );
    }

    function testPerSectionOverridesDefault() {
        $ini = new testIniFileModifier('foo.ini', '
; @defaultPreferredFile default.ini
; @preferredFile override.ini
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(
            array('exemple' => 'override.ini', 'other' => 'default.ini'),
            $ini->getPreferredFiles()
        );
    }

    function testDefaultPreferredFileSelf() {
        $ini = new testIniFileModifier('/some/path/myconfig.ini', '
; @defaultPreferredFile $current-file-name
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(
            array('exemple' => 'myconfig.ini', 'other' => 'myconfig.ini'),
            $ini->getPreferredFiles()
        );
    }

    function testSectionWithNeitherIsOmitted() {
        $ini = new testIniFileModifier('foo.ini', '
[exemple]
foo=bar
');
        $this->assertArrayNotHasKey('exemple', $ini->getPreferredFiles());
    }

    function testMultipleSectionsMixed() {
        $ini = new testIniFileModifier('foo.ini', '
; @defaultPreferredFile default.ini
; @preferredFile override.ini
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
            $ini->getPreferredFiles()
        );
    }

    function testOrdinaryCommentAboveSectionIgnored() {
        $ini = new testIniFileModifier('foo.ini', '
; just a note
[exemple]
foo=bar
');
        $this->assertEquals(array(), $ini->getPreferredFiles());
    }

    function testNoAttributesAtAll() {
        $ini = new testIniFileModifier('foo.ini', '
[exemple]
foo=bar
[other]
baz=qux
');
        $this->assertEquals(array(), $ini->getPreferredFiles());
    }

    function testRoundTripPreservesCommentVerbatim() {
        $content = '
; @defaultPreferredFile default.ini
; @preferredFile exemple.ini
[exemple]
foo=bar
';
        $ini = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($content, $ini->generate());
    }

    function testPreferredFileWithSectionParamCanBeAnywhere() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile exemple.ini exemple
foo=bar
[exemple]
baz=qux
');
        $this->assertEquals(array('exemple' => 'exemple.ini'), $ini->getPreferredFiles());
    }

    function testPreferredFileWithSectionParamForNonExistentSection() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile secrets.ini db
[other]
foo=bar
');
        $this->assertEquals(array('db' => 'secrets.ini'), $ini->getPreferredFiles());
    }

    function testPreferredFileWithSectionParamSelf() {
        $ini = new testIniFileModifier('/some/path/myconfig.ini', '
; @preferredFile $current-file-name db
[other]
foo=bar
');
        $this->assertEquals(array('db' => 'myconfig.ini'), $ini->getPreferredFiles());
    }

    function testPreferredFileWithSectionParamDoesNotAttachToFollowingSection() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile secrets.ini db
[exemple]
foo=bar
');
        $this->assertEquals(array('db' => 'secrets.ini'), $ini->getPreferredFiles());
    }

    function testMixOfOneAndTwoParameterForms() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile shared.ini other
; @preferredFile exemple-specific.ini
[exemple]
foo=1
[other]
bar=2
');
        $this->assertEquals(
            array('exemple' => 'exemple-specific.ini', 'other' => 'shared.ini'),
            $ini->getPreferredFiles()
        );
    }

    function testRoundTripPreservesTwoParameterCommentVerbatim() {
        $content = '
; @preferredFile secrets.ini db
[exemple]
foo=bar
';
        $ini = new testIniFileModifier('foo.ini', $content);
        $this->assertEquals($content, $ini->generate());
    }

    function testPreferredFileWildcardMatchesPrefixedSections() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile shared.ini foo*
[foo]
a=1
[foobar]
b=2
');
        $this->assertEquals(
            array('foo' => 'shared.ini', 'foobar' => 'shared.ini', 'foo*' => 'shared.ini'),
            $ini->getPreferredFiles()
        );
    }

    function testPreferredFileWildcardDoesNotMatchUnrelatedSection() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile shared.ini foo*
[barfoo]
a=1
');
        $this->assertArrayNotHasKey('barfoo', $ini->getPreferredFiles());
    }

    function testPreferredFileLongestWildcardPrefixWins() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile general.ini foo*
; @preferredFile specific.ini foobar*
[foobarbaz]
a=1
');
        $this->assertEquals(
            array(
                'foobarbaz' => 'specific.ini',
                'foo*' => 'general.ini',
                'foobar*' => 'specific.ini',
            ),
            $ini->getPreferredFiles()
        );
    }

    function testPreferredFileWildcardPassesThroughWhenNoSectionMatchesInThisFile() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile shared.ini foo*
[other]
a=1
');
        $this->assertEquals(
            array('foo*' => 'shared.ini'),
            $ini->getPreferredFiles()
        );
    }

    function testPreferredFileExactSectionWinsOverWildcard() {
        $ini = new testIniFileModifier('foo.ini', '
; @preferredFile shared.ini foo*
; @preferredFile literal.ini
[foo]
a=1
');
        $this->assertEquals(
            array('foo' => 'literal.ini', 'foo*' => 'shared.ini'),
            $ini->getPreferredFiles()
        );
    }
}
