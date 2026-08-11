<?php
/**
* @author      Laurent Jouanneau
* @copyright   2026 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(__DIR__.'/lib.php');

use Jelix\IniFile\IniException;
use \Jelix\IniFile\IniModifierReadOnly;

class SmartIniModifierArrayTest extends \PHPUnit\Framework\TestCase {

    function testSetValueRule1PicksFileHavingSectionAndParam() {
        $one = new testIniFileModifier('foo.ini', '
[s]
bar=1
');
        $two = new testIniFileModifier('foo.ini', '
[s]
foo=2
');
        $three = new testIniFileModifier('foo.ini', '
[s]
baz=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));
        $this->assertSame($two, $multi->resolveTarget('foo', 's'));

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $two->getValue('foo', 's'));
        $this->assertNull($one->getValue('foo', 's'));
        $this->assertNull($three->getValue('foo', 's'));
        $this->assertTrue($two->isModified());
        $this->assertFalse($one->isModified());
        $this->assertFalse($three->isModified());
    }

    function testSetValueRule1PicksClosestToEndWhenSeveralFilesHaveTheParam() {
        $one = new testIniFileModifier('foo.ini', '
[s]
bar=1
');
        $two = new testIniFileModifier('foo.ini', '
[s]
foo=2
');
        $three = new testIniFileModifier('foo.ini', '
[s]
foo=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));
        $this->assertSame($three, $multi->resolveTarget('foo', 's'));

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $three->getValue('foo', 's'));
        $this->assertEquals(2, $two->getValue('foo', 's'));
    }

    function testSetValueRule2PicksFileHavingTheSectionWhenOnlyOneHasIt() {
        $one = new testIniFileModifier('foo.ini', '
other=1
');
        $two = new testIniFileModifier('foo.ini', '
[s]
someother=1
');
        $three = new testIniFileModifier('foo.ini', '
other=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));
        $this->assertSame($two, $multi->resolveTarget('foo', 's'));

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $two->getValue('foo', 's'));
        $this->assertFalse($three->isSection('s'));
    }

    function testSetValueRule2PicksClosestToEndWhenSeveralFilesHaveTheSection() {
        $one = new testIniFileModifier('foo.ini', '
[s]
bar=1
');
        $two = new testIniFileModifier('foo.ini', '
[s]
baz=2
');
        $three = new testIniFileModifier('foo.ini', '
[s]
qux=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));
        $this->assertSame($three, $multi->resolveTarget('foo', 's'));
    }

    function testSetValueRule3PicksClosestToEndWritableFileForABrandNewSection() {
        $one = new testIniFileModifier('foo.ini', '
other=1
');
        $two = new testIniFileModifier('foo.ini', '
other=2
');
        $three = new testIniFileModifier('foo.ini', '
other=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));
        $this->assertSame($three, $multi->resolveTarget('baz', 'newsection'));

        $multi->setValue('baz', 'v', 'newsection');
        $this->assertTrue($three->isSection('newsection'));
        $this->assertFalse($one->isSection('newsection'));
        $this->assertFalse($two->isSection('newsection'));
    }

    function testSetValueSkipsReadOnlyLastFileEvenIfItWouldMatchRule1() {
        $one = new testIniFileModifier('foo.ini', '
[s]
bar=1
');
        $two = new testIniFileModifier('foo.ini', '
[s]
someother=1
');
        $three = new IniModifierReadOnly(new testIniFileModifier('foo.ini', '
[s]
foo=readonly-value
'));
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));

        $target = $multi->resolveTarget('foo', 's');
        $this->assertSame($two, $target);
        $this->assertNotSame($three, $target);

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $two->getValue('foo', 's'));
        $this->assertEquals('readonly-value', $three->getValue('foo', 's'));
    }

    function testSetValueSkipsReadOnlyMiddleFileEvenIfItWouldMatchRule1() {
        $one = new testIniFileModifier('foo.ini', '
[s]
someother=1
');
        $two = new IniModifierReadOnly(new testIniFileModifier('foo.ini', '
[s]
foo=readonly-value
'));
        $three = new testIniFileModifier('foo.ini', '
other=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));

        $target = $multi->resolveTarget('foo', 's');
        $this->assertSame($one, $target);
        $this->assertNotSame($two, $target);

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $one->getValue('foo', 's'));
        $this->assertEquals('readonly-value', $two->getValue('foo', 's'));
        $this->assertFalse($three->isSection('s'));
    }

    function testSetValueOnAllReadOnlyStackTriggersWarningAndWritesNothing() {
        $one = new IniModifierReadOnly(new testIniFileModifier('foo.ini', '
[s]
foo=1
'));
        $two = new IniModifierReadOnly(new testIniFileModifier('foo.ini', '
[s]
foo=2
'));
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $this->assertNull($multi->resolveTarget('foo', 's'));

        $this->expectException(IniException::class);

        $multi->setValue('foo', 'X', 's');
    }

    function testSetValuesRoutesEachParameterIndependently() {
        $one = new testIniFileModifier('foo.ini', '
other=1
');
        $two = new testIniFileModifier('foo.ini', '
[sec]
a=orig
');
        $three = new testIniFileModifier('foo.ini', '
[sec]
other=val
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));

        $multi->setValues(array('a' => '1', 'b' => '2'), 'sec');

        $this->assertEquals('1', $two->getValue('a', 'sec'));
        $this->assertEquals('2', $three->getValue('b', 'sec'));
        $this->assertNull($one->getValue('a', 'sec'));
        $this->assertNull($one->getValue('b', 'sec'));
    }

    function testSetValueWithASingleWritableModifierAlwaysTargetsIt() {
        $one = new testIniFileModifier('foo.ini', '
[s]
foo=1
');
        $multi = new testSmartIniFileModifierArray(array($one));

        $this->assertSame($one, $multi->resolveTarget('foo', 's'));
        $this->assertSame($one, $multi->resolveTarget('newparam', 'newsection'));

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $one->getValue('foo', 's'));
    }

    function testInheritedGetValueStillMergesByPriority() {
        $one = new testIniFileModifier('foo.ini', '
[s]
foo=bar
');
        $two = new testIniFileModifier('foo.ini', '
[s]
foo=baz
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $this->assertEquals('baz', $multi->getValue('foo', 's'));
    }

    function testInheritedRemoveValueStillAppliesToAllModifiers() {
        $one = new testIniFileModifier('foo.ini', '
[s]
foo=bar
');
        $two = new testIniFileModifier('foo.ini', '
[s]
foo=baz
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->removeValue('foo', 's');
        $this->assertNull($one->getValue('foo', 's'));
        $this->assertNull($two->getValue('foo', 's'));
    }

    function testSetPreferedFilePicksMappedWritableModifierOverOtherRules() {
        $one = new testIniFileModifier('one.ini', '
[s]
foo=1
');
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $three = new testIniFileModifier('three.ini', '
[s]
foo=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));

        // without the mapping, rule 1 would pick $three (closest to end)
        $this->assertSame($three, $multi->resolveTarget('foo', 's'));

        $multi->setPreferedFile(array('s'), 'one.ini');
        $this->assertSame($one, $multi->resolveTarget('foo', 's'));

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $one->getValue('foo', 's'));
        $this->assertEquals('2', $two->getValue('foo', 's'));
        $this->assertEquals('3', $three->getValue('foo', 's'));
    }

    function testSetPreferedFileFallsThroughWhenMappedModifierIsReadOnly() {
        $one = new IniModifierReadOnly(new testIniFileModifier('one.ini', '
[s]
foo=readonly
'));
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $multi->setPreferedFile(array('s'), 'one.ini');
        $this->assertSame($two, $multi->resolveTarget('foo', 's'));

        $multi->setValue('foo', 'X', 's');
        $this->assertEquals('X', $two->getValue('foo', 's'));
        $this->assertEquals('readonly', $one->getValue('foo', 's'));
    }

    function testSetPreferedFileCreatesModifierWhenFileIsAbsentFromTheStack() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $two = new testIniFileModifier('two.ini', '
other=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $multi->setPreferedFile(array('s'), TEMP_PATH.'newfile.ini');
        $this->assertCount(2, $multi);

        $target = $multi->resolveTarget('foo', 's');
        $this->assertNotSame($one, $target);
        $this->assertNotSame($two, $target);
        $this->assertEquals(TEMP_PATH.'newfile.ini', $target->getFileName());

        $multi->setValue('foo', 'X', 's');
        $this->assertCount(3, $multi);
        $this->assertSame($target, $multi[TEMP_PATH.'newfile.ini']);
        $this->assertEquals('X', $target->getValue('foo', 's'));

        // the newly created modifier sits right before the previous last element
        $keys = array();
        foreach ($multi as $k => $mod) {
            $keys[] = $k;
        }
        $this->assertEquals(array(0, TEMP_PATH.'newfile.ini', 1), $keys);
    }

    function testSetPreferedFileResolvesBareFilenameAgainstConstructorDirectory() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $multi = new testSmartIniFileModifierArray(array($one), TEMP_PATH);

        $multi->setPreferedFile(array('s'), 'newfile.ini');
        $target = $multi->resolveTarget('foo', 's');
        $this->assertEquals(rtrim(TEMP_PATH, '/').'/newfile.ini', $target->getFileName());
    }

    function testSetPreferedFileKeepsBareFilenameWhenNoDirectoryGiven() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $multi = new testSmartIniFileModifierArray(array($one));

        $multi->setPreferedFile(array('s'), 'newfile.ini');
        $target = $multi->resolveTarget('foo', 's');
        $this->assertEquals('newfile.ini', $target->getFileName());
    }

    function testSetPreferedFileWithMultipleSectionsMappedToSameFile() {
        $one = new testIniFileModifier('one.ini', '
[s1]
foo=1
[s2]
bar=1
');
        $two = new testIniFileModifier('two.ini', '
[s1]
foo=2
[s2]
bar=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $multi->setPreferedFile(array('s1', 's2'), 'one.ini');
        $this->assertSame($one, $multi->resolveTarget('foo', 's1'));
        $this->assertSame($one, $multi->resolveTarget('bar', 's2'));
    }

    function testUnmappedSectionStillFollowsTheExistingRules() {
        $one = new testIniFileModifier('one.ini', '
[s]
foo=1
');
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $multi->setPreferedFile(array('other'), 'one.ini');
        $this->assertSame($two, $multi->resolveTarget('foo', 's'));
    }

    function testConstructorAutoPopulatesFromModifiersPreferedFiles() {
        $one = new testIniFileModifier('one.ini', '
; @preferedFile two.ini
[s]
foo=1
');
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $this->assertSame($two, $multi->resolveTarget('foo', 's'));
    }

    function testConstructorLaterModifierPreferenceOverridesEarlierOne() {
        $one = new testIniFileModifier('one.ini', '
; @preferedFile one.ini
[s]
foo=1
');
        $two = new testIniFileModifier('two.ini', '
; @preferedFile three.ini
[s]
foo=2
');
        $three = new testIniFileModifier('three.ini', '
[s]
foo=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));

        $this->assertSame($three, $multi->resolveTarget('foo', 's'));
    }

    function testConstructorResolvesRelativePreferedFileAgainstDirectory() {
        $one = new testIniFileModifier('one.ini', '
; @preferedFile newfile.ini
[s]
foo=1
');
        $multi = new testSmartIniFileModifierArray(array($one), TEMP_PATH);

        $target = $multi->resolveTarget('foo', 's');
        $this->assertEquals(rtrim(TEMP_PATH, '/').'/newfile.ini', $target->getFileName());
    }

    function testConstructorDoesNotFailWithModifiersLackingGetPreferedFiles() {
        $one = new IniModifierReadOnly(new testIniFileModifier('one.ini', '
[s]
foo=1
'));
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $this->assertSame($two, $multi->resolveTarget('foo', 's'));
    }

    function testDispatchMovesSectionToItsPreferedFile() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('s'), 'one.ini');

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals('2', $one->getValue('foo', 's'));
        $this->assertFalse($two->isSection('s'));
    }

    function testDispatchIsANoopWhenSectionAlreadyInPreferedFile() {
        $one = new testIniFileModifier('one.ini', '
[s]
foo=1
');
        $two = new testIniFileModifier('two.ini', '
other=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('s'), 'one.ini');

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals('1', $one->getValue('foo', 's'));
        $this->assertFalse($two->isSection('s'));
    }

    function testDispatchMergesSectionFromSeveralOtherFiles() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $three = new testIniFileModifier('three.ini', '
[s]
bar=3
');
        $multi = new testSmartIniFileModifierArray(array($one, $two, $three));
        $multi->setPreferedFile(array('s'), 'one.ini');

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals('2', $one->getValue('foo', 's'));
        $this->assertEquals('3', $one->getValue('bar', 's'));
        $this->assertFalse($two->isSection('s'));
        $this->assertFalse($three->isSection('s'));
    }

    function testDispatchCreatesThePreferedFileWhenAbsentFromTheStack() {
        $one = new testIniFileModifier('one.ini', '
[s]
foo=1
');
        $two = new testIniFileModifier('two.ini', '
other=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('s'), TEMP_PATH.'newfile.ini');
        $this->assertCount(2, $multi);

        $multi->dispatchSectionToPreferedFiles();

        $this->assertCount(3, $multi);
        $target = $multi[TEMP_PATH.'newfile.ini'];
        $this->assertEquals('1', $target->getValue('foo', 's'));
        $this->assertFalse($one->isSection('s'));

        $keys = array();
        foreach ($multi as $k => $mod) {
            $keys[] = $k;
        }
        $this->assertEquals(array(0, TEMP_PATH.'newfile.ini', 1), $keys);
    }

    function testDispatchSkipsWhenPreferedFileTargetIsReadOnly() {
        $one = new IniModifierReadOnly(new testIniFileModifier('one.ini', '
other=1
'));
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('s'), 'one.ini');

        $multi->dispatchSectionToPreferedFiles();

        $this->assertTrue($two->isSection('s'));
        $this->assertEquals('2', $two->getValue('foo', 's'));
    }

    function testDispatchPreservesArrayValues() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $two = new testIniFileModifier('two.ini', '
[s]
foo[]=aaa
foo[]=bbb
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('s'), 'one.ini');

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals(array('aaa', 'bbb'), $one->getValue('foo', 's'));
        $this->assertFalse($two->isSection('s'));
    }

    function testDispatchLeavesUnmappedSectionsUntouched() {
        $one = new testIniFileModifier('one.ini', '
[a]
x=1
');
        $two = new testIniFileModifier('two.ini', '
[b]
y=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $multi->dispatchSectionToPreferedFiles();

        $this->assertTrue($one->isSection('a'));
        $this->assertTrue($two->isSection('b'));
    }

    function testDispatchSkipsReadOnlySourceFiles() {
        $one = new IniModifierReadOnly(new testIniFileModifier('one.ini', '
[s]
foo=1
'));
        $two = new testIniFileModifier('two.ini', '
other=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('s'), 'two.ini');

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals('1', $one->getValue('foo', 's'));
        $this->assertFalse($two->isSection('s'));
    }

    function testSetPreferedFileWildcardRoutesMatchingSection() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $two = new testIniFileModifier('two.ini', '
[foobar]
a=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('foo*'), 'one.ini');

        $this->assertSame($one, $multi->resolveTarget('a', 'foobar'));
    }

    function testSetPreferedFileWildcardDoesNotMatchUnrelatedSection() {
        $one = new testIniFileModifier('one.ini', '
[s]
foo=1
');
        $two = new testIniFileModifier('two.ini', '
[s]
foo=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('foo*'), 'one.ini');

        $this->assertSame($two, $multi->resolveTarget('foo', 's'));
    }

    function testSetPreferedFileLongestWildcardPrefixWins() {
        $one = new testIniFileModifier('general.ini', '
other=1
');
        $two = new testIniFileModifier('specific.ini', '
other=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('foo*'), 'general.ini');
        $multi->setPreferedFile(array('foobar*'), 'specific.ini');

        $this->assertSame($two, $multi->resolveTarget('a', 'foobarbaz'));
    }

    function testSetPreferedFileLongestWildcardPrefixWinsRegardlessOfOrder() {
        $one = new testIniFileModifier('general.ini', '
other=1
');
        $two = new testIniFileModifier('specific.ini', '
other=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('foobar*'), 'specific.ini');
        $multi->setPreferedFile(array('foo*'), 'general.ini');

        $this->assertSame($two, $multi->resolveTarget('a', 'foobarbaz'));
    }

    function testDispatchMovesWildcardMatchingSectionFromAnotherFile() {
        $one = new testIniFileModifier('one.ini', '
other=1
');
        $two = new testIniFileModifier('two.ini', '
[foobar]
a=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('foo*'), 'one.ini');

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals('2', $one->getValue('a', 'foobar'));
        $this->assertFalse($two->isSection('foobar'));
    }

    function testDispatchCreatesFileForWildcardMatchWhenAbsentFromStack() {
        $one = new testIniFileModifier('one.ini', '
[foobar]
a=1
');
        $two = new testIniFileModifier('two.ini', '
other=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('foo*'), TEMP_PATH.'wildcard-target.ini');
        $this->assertCount(2, $multi);

        $multi->dispatchSectionToPreferedFiles();

        $this->assertCount(3, $multi);
        $target = $multi[TEMP_PATH.'wildcard-target.ini'];
        $this->assertEquals('1', $target->getValue('a', 'foobar'));
        $this->assertFalse($one->isSection('foobar'));
    }

    function testDispatchDoesNotCreateFileForWildcardWithNoMatchingSection() {
        $one = new testIniFileModifier('one.ini', '
[other]
a=1
');
        $two = new testIniFileModifier('two.ini', '
b=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));
        $multi->setPreferedFile(array('foo*'), TEMP_PATH.'never-created.ini');
        $this->assertCount(2, $multi);

        $multi->dispatchSectionToPreferedFiles();

        $this->assertCount(2, $multi);
    }

    function testDispatchExactKeyWinsOverWildcardAvoidingDoubleDispatch() {
        $two = new testIniFileModifier('two.ini', '
[foobar]
a=1
');
        $exact = new testIniFileModifier('exact.ini', '
other=1
');
        $multi = new testSmartIniFileModifierArray(array($two, $exact));
        $multi->setPreferedFile(array('foobar'), 'exact.ini');
        $multi->setPreferedFile(array('foo*'), 'wildcard.ini');
        $this->assertCount(2, $multi);

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals('1', $exact->getValue('a', 'foobar'));
        $this->assertFalse($two->isSection('foobar'));
        $this->assertCount(2, $multi);
    }

    function testDispatchExactKeyWinsOverWildcardRegardlessOfOrder() {
        $two = new testIniFileModifier('two.ini', '
[foobar]
a=1
');
        $exact = new testIniFileModifier('exact.ini', '
other=1
');
        $multi = new testSmartIniFileModifierArray(array($two, $exact));
        $multi->setPreferedFile(array('foo*'), 'wildcard.ini');
        $multi->setPreferedFile(array('foobar'), 'exact.ini');
        $this->assertCount(2, $multi);

        $multi->dispatchSectionToPreferedFiles();

        $this->assertEquals('1', $exact->getValue('a', 'foobar'));
        $this->assertFalse($two->isSection('foobar'));
        $this->assertCount(2, $multi);
    }

    function testConstructorAutoPopulatesWildcardPreferedFileFromModifier() {
        $one = new testIniFileModifier('one.ini', '
; @preferedFile two.ini foo*
other=1
');
        $two = new testIniFileModifier('two.ini', '
[foobar]
a=2
');
        $multi = new testSmartIniFileModifierArray(array($one, $two));

        $this->assertSame($two, $multi->resolveTarget('a', 'foobar'));
    }
}
