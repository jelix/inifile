<?php
/**
* @author      Laurent Jouanneau
* @copyright   2026 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(__DIR__.'/lib.php');

use \Jelix\IniFile\IniModifierReadOnly;

class IniModifierArray2Test extends \PHPUnit\Framework\TestCase {

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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));
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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));
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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));
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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));
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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));
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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));

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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));

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
        $multi = new testIniFileModifierArray2(array($one, $two));

        $this->assertNull($multi->resolveTarget('foo', 's'));

        $caught = null;
        set_error_handler(function ($errno, $msg) use (&$caught) {
            $caught = array($errno, $msg);
            return true;
        });
        $multi->setValue('foo', 'X', 's');
        restore_error_handler();

        $this->assertNotNull($caught);
        $this->assertEquals(E_USER_WARNING, $caught[0]);
        $this->assertEquals('1', $one->getValue('foo', 's'));
        $this->assertEquals('2', $two->getValue('foo', 's'));
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
        $multi = new testIniFileModifierArray2(array($one, $two, $three));

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
        $multi = new testIniFileModifierArray2(array($one));

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
        $multi = new testIniFileModifierArray2(array($one, $two));
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
        $multi = new testIniFileModifierArray2(array($one, $two));
        $multi->removeValue('foo', 's');
        $this->assertNull($one->getValue('foo', 's'));
        $this->assertNull($two->getValue('foo', 's'));
    }
}
