Some classes to read and modify ini files by preserving comments, empty lines.

# installation

You can install it from Composer. In your project:

```
composer require "jelix/inifile"
```

# Usage

The ```\Jelix\IniFile\IniModifier``` class allow to read an ini file, to modify its
content, and save it by preserving its comments and empty lines.

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

// merging two section: merge sectionSource into sectionTarget and sectionSource is removed
$ini->mergeSection('sectionSource', 'sectionTarget');

```

See the class to learn about other methods and options.

The ```\Jelix\IniFile\MultiIniModifier``` allows to load two ini file at the same time,
where the first one "overrides" values of the second one.


The ```\Jelix\IniFile\Util``` contains simple methods to read, write and merge ini files.
These are just wrappers around ```parse_ini_file()```.
