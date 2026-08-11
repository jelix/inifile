# Usage

The ```\Jelix\IniFile\IniModifier``` class allows reading an ini file, modifying its
content, and saving it by preserving its comments and empty lines.

Don't use this class to just read content. Use instead ```\Jelix\IniFile\Util``` or
```parse_ini_file()``` for this purpose, it's more efficient and performant.


```php
$ini = new \Jelix\IniFile\IniModifier('myfile.ini');

// setting a parameter.  (section_name is optional)
$ini->setValue('parameter_name', 'value', 'section_name');

// retrieve a parameter value. (section_name is optional)
$val = $ini->getValue('parameter_name', 'section_name');

// remove a parameter
$ini->removeValue('parameter_name', 'section_name');


// save into file

$ini->save();
$ini->saveAs('otherfile.ini');

// importing an ini file into an other
$ini2 = new \Jelix\IniFile\IniModifier('myfile2.ini');
$ini->import($ini2);
$ini->save();

// merging two sections: merge sectionSource into sectionTarget and then 
// sectionSource is removed
$ini->mergeSection('sectionSource', 'sectionTarget');

```

It supports also array values (indexed or associative) like :

```ini
foo[]=bar
foo[]=baz
assoc[key1]=car
assoc[otherkey]=bus
```

Then in PHP:

```php
$ini = new \Jelix\IniFile\IniModifier('myfile.ini');

$val = $ini->getValue('foo'); // array('bar', 'baz');
$val = $ini->getValue('assoc'); // array('key1'=>'car', 'otherkey'=>'bus');

$ini->setValue('foo', 'other value', 0, '');
$val = $ini->getValue('foo'); // array('bar', 'baz', 'other value');

$ini->setValue('foo', 'five', 0, 5);
$val = $ini->getValue('foo'); // array('bar', 'baz', 'other value', 5 => 'five');


$ini->setValue('assoc', 'other value', 0, 'ov');
$val = $ini->getValue('assoc'); // array('key1'=>'car', 'otherkey'=>'bus', 'ov'=>'other value');
```

After saving, the ini content is:

```ini
foo[]=bar
foo[]=baz
assoc[key1]=car
assoc[otherkey]=bus

foo[]="other value"
foo[]=five
assoc[ov]="other value"
```

Note: the result can be parsed by `parse_ini_file()`.

See the class to learn about other methods and options.

# Other classes

The `\Jelix\IniFile\MultiIniModifier` class allows loading two ini files at the same time,
where the second one "overrides" values of the first one.

The `\Jelix\IniFile\IniModifierArray` class allows loading several files at the
same time and to manage their values as if files were merged. Modified values are stored in the latest file of the stack.
The `IniModifier` objects can be accessed by their index in the array like `$ini['my.ini']`.

The `\Jelix\IniFile\IniReader` class is a class that reads an ini file, as IniModifier does. So it can be used
into composite classes like `MultiIniModifier`, `SmartIniModifierArray` etc.

The `\Jelix\IniFile\IniModifierReadOnly` class is a class that decorates an `IniModifier` object and makes it read-only.

The `\Jelix\IniFile\Util` contains simple methods to read, write and merge ini files.
These are just wrappers around `parse_ini_file()`.


## SmartIniModifierArray

The `\Jelix\IniFile\SmartIniModifierArray` class is like `IniModifierArray`, but when setting a parameter value, it may be set
into one of the files, instead of the latest file of the stack. Some rules determine the right file to store a parameter:

- the preferred modifiable file indicated by the method `setPreferredFile()`
- the preferred modifiable file indicated by one of the ini attribute `@preferredFile` or `@defaultPreferredFile`
- the modifiable file closest to the end of the list that already has the section and the parameter
- the modifiable file closest to the end of the list that has the section
- the modifiable file closest to the end of the list


`@preferredFile` or `@defaultPreferredFile` are comment's attributes.
Syntax is:

```ini
; @defaultPreferredFile <filename>
; @preferredFile <filename> [<section>]

```

`<filename>` indicates the file where parameters of a section are stored. `<section>` is the section name. it is optional
when `@preferredFile` is just before a section.

`@defaultPreferredFile` indicates the default preferred file for all sections declared in the ini file.

You can set `$current-file-name` as filename to indicate the current file basename.

Example into a mainconfig.ini file:

```ini

;indicates the preferred file for all sections used in this file
;@defaultPreferredFile bar.ini

; indicates the preferred file for the section "mySection"
; @preferredFile other.ini mySection

; indicates the preferred file for all sections having names starting with "auth"
; @preferredFile authentication.ini auth*

; indicates the preferred file for the section "foo"
; @preferredFile bla.ini
[foo]
param=value
```

The constructor of `SmartIniModifierArray` takes an array of `IniModifier` objects and the path to a directory where to store ini files.
If an ini file indicated by `@preferredFile` or `@defaultPreferredFile` does not exist into the array, it is created into this directory.

```php

use Jelix\IniFile\IniModifierReadOnly;
$ini = new SmartIniModifierArray(
    array(
        new IniReader(__DIR__.'/mainconfig.ini'), // this file is not modifiable
        new IniModifierReadOnly(new IniModifier(__DIR__.'/config.d/foo.ini')), // this file is not modifiable either
        new IniModifier(__DIR__.'/config.d/bar.ini'),
        new IniModifier(__DIR__.'/config.d/other.ini'),
    ),
    __DIR__.'/config.d/'
);

// acl2 and acldriver sections are stored into acl.ini
$ini->setPreferredFile(['acl2', 'acldriver'], 'acl.ini');

// the value will be stored into authentication.ini which will be created into config.d/
$ini->setValue('driver', 'db', 'auth'); 
```

Values of sections that have no preferred files are stored into the last file of the stack if it is modifiable. 
