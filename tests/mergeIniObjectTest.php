<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2016 Laurent Jouanneau
 * @link        http://www.jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

use \Jelix\IniFile\Util;

class mergeIniObjectTest extends \PHPUnit\Framework\TestCase
{
    public function testNoSection()
    {
        $base = (object) parse_ini_string('
foo=bar
vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
vehicule=bus
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "vehicule"=>"bus",
            "food" => "bread"
        ), $result);
    }

    public function testProtectedField()
    {
        $base = (object) parse_ini_string('
foo=bar
_vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
_vehicule=bus
plane=airbus
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new, Util::NOT_MERGE_PROTECTED_DIRECTIVE);
        $this->assertEquals(array(
            "foo"=>"bar",
            "_vehicule"=>"car",
            "food" => "bread",
            "plane"=>"airbus"
        ), $result);
    }


    public function testSection()
    {
        $base = (object) parse_ini_string('
foo=bar
[something]
vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
[something]
vehicule=bus
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "something"=> array(
                "vehicule"=>"bus",
                "food" => "bread")
        ), $result);
    }

    public function testSectionWithIgnoredSection()
    {
        $base = (object) parse_ini_string('
foo=bar
untouchedfoo=baz
[something]
vehicule=car
food=bread
[untouched]
chou=fleur
        ', true);
        $new = (object) parse_ini_string('
untouchedfoo=hello
earth=notflat
[something]
vehicule=bus

[untouched]
chou=vert
some=thing

[newsection]
newparam=param
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new, 0, array('untouchedfoo', 'untouched'));
        $this->assertEquals(array(
            "foo"=>"bar",
            "untouchedfoo"=>"baz",
            "earth"=>"notflat",
            "something"=> array(
                "vehicule"=>"bus",
                "food" => "bread"),
            "untouched"=> array(
                "chou" => "fleur"
            ),
            "newsection" => array(
                "newparam"=>"param"
            )
        ), $result);
    }

    public function testArrayValuesOutsideSections()
    {
        $base = (object) parse_ini_string('
foo=bar
arr[]=aaa
arr[]=bbb
arr[]=ccc

[something]
vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
arr[]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "arr"=> array('eee'),
            "something"=> array(
                "vehicule"=>"car",
                "food" => "bread")

        ), $result);
    }


    public function testArrayValuesAndStringOutsideSections()
    {
        $base = (object) parse_ini_string('
foo=bar
arr=bbb,ccc

[something]
vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
arr[]=aaa
arr[]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "arr"=> array('aaa','eee'),
            "something"=> array(
                "vehicule"=>"car",
                "food" => "bread")

        ), $result);
    }

    public function testStringAndArrayValuesOutsideSections()
    {
        $base = (object) parse_ini_string('
foo=bar
arr[]=aaa
arr[]=eee

[something]
vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
arr=bbb,ccc
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "arr"=> 'bbb,ccc',
            "something"=> array(
                "vehicule"=>"car",
                "food" => "bread")

        ), $result);
    }

    public function testArrayValuesOutsideSectionsWithFlag()
    {
        $base = (object) parse_ini_string('
foo=bar
arr[]=aaa
arr[]=bbb
arr[]=ccc

[something]
vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
arr[]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new, Util::NORMAL_MERGE_ARRAY_VALUES_WITH_INTEGER_KEYS);
        $this->assertEquals(array(
            "foo"=>"bar",
            "arr"=> array('aaa', 'bbb', 'ccc', 'eee'),
            "something"=> array(
                "vehicule"=>"car",
                "food" => "bread")

        ), $result);
    }

    public function testAssocArrayValuesOutsideSections()
    {
        $base = (object) parse_ini_string('
foo=bar
arr[k1]=aaa
arr[k2]=bbb
arr[]=ccc

[something]
1=fofofo
vehicule=car
food=bread
        ', true);
        $new = (object) parse_ini_string('
arr[k2]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "arr"=> array('k1'=>'aaa','k2'=>'eee'),
            "something"=> array(
                "1"=> "fofofo",
                "vehicule"=>"car",
                "food" => "bread")

        ), $result);
    }


    public function testArrayValuesIntoSections()
    {
        $base = (object) parse_ini_string('
foo=bar

[something]
vehicule=car
arr[]=aaa
arr[]=bbb
arr[]=ccc
food=bread
        ', true);
        $new = (object) parse_ini_string('
[something]
arr[]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "something"=> array(
                "vehicule"=>"car",
                "arr"=> array('eee'),
                "food" => "bread")

        ), $result);
    }

    public function testArrayValuesWithStringIntoSections()
    {
        $base = (object) parse_ini_string('
foo=bar

[something]
vehicule=car
arr=bbb,ccc
food=bread
        ', true);
        $new = (object) parse_ini_string('
[something]
arr[]=aaa
arr[]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "something"=> array(
                "vehicule"=>"car",
                "arr"=> array('aaa','eee'),
                "food" => "bread")

        ), $result);
    }

    public function testStringWithArrayValuesIntoSections()
    {
        $base = (object) parse_ini_string('
foo=bar

[something]
vehicule=car
arr[]=aaa
arr[]=eee
food=bread
        ', true);
        $new = (object) parse_ini_string('
[something]
arr=bbb,ccc
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "something"=> array(
                "vehicule"=>"car",
                "arr"=> 'bbb,ccc',
                "food" => "bread")

        ), $result);
    }

    public function testArrayValuesIntoSectionsWithFlag()
    {
        $base = (object) parse_ini_string('
foo=bar

[something]
vehicule=car
arr[]=aaa
arr[]=bbb
arr[]=ccc
food=bread
        ', true);
        $new = (object) parse_ini_string('
[something]
arr[]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new, Util::NORMAL_MERGE_ARRAY_VALUES_WITH_INTEGER_KEYS);
        $this->assertEquals(array(
            "foo"=>"bar",
            "something"=> array(
                "vehicule"=>"car",
                "arr"=> array('aaa', 'bbb', 'ccc', 'eee'),
                "food" => "bread")

        ), $result);
    }

    public function testAssocArrayValuesIntoSections()
    {
        $base = (object) parse_ini_string('
foo=bar

[something]
vehicule=car
arr[k1]=aaa
arr[k2]=bbb
arr[]=ccc
food=bread
        ', true);
        $new = (object) parse_ini_string('
[something]
arr[k2]=eee
        ', true);

        $result = (array) Util::mergeIniObjectContents($base, $new);
        $this->assertEquals(array(
            "foo"=>"bar",
            "something"=> array(
                "vehicule"=>"car",
                "arr"=> array('k1'=>'aaa','k2'=>'eee'),
                "food" => "bread")
        ), $result);
    }
}