<?php

/**
 * @author     Laurent Jouanneau
 * @copyright  2026 Laurent Jouanneau
 *
 * @link       http://jelix.org
 * @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

namespace Jelix\IniFile;

/**
 * Like IniModifierArray, but setValue()/setValues() write into whichever modifiable ini file
 * of the stack is the most relevant for the given parameter, instead of always the last one:
 * - the modifiable file closest to the end of the list that already has the section and the parameter
 * - else the modifiable file closest to the end of the list that has the section
 * - else the modifiable file closest to the end of the list
 *
 * Unlike IniModifierArray::setValue(), this always finds a writable target as long as any
 * modifier in the stack is writable, even if the literal last element of the list is read-only.
 */
class IniModifierArray2 extends IniModifierArray
{
    /**
     * map of section name => filename, populated by setPreferedFile().
     * @var array
     */
    protected $preferedFileBySection = array();

    /**
     * @var string|null directory used to resolve bare filenames given to setPreferedFile()
     */
    protected $preferedFilesDirectory;

    /**
     * @param \Jelix\IniFile\IniReaderInterface[]|string[] $modifiers the list of ini file names or ini reader/modifier objects
     * @param string|null $preferedFilesDirectory directory into which bare filenames given to
     *                                             setPreferedFile() (without any directory part)
     *                                             should be resolved
     */
    public function __construct(array $modifiers, $preferedFilesDirectory = null)
    {
        parent::__construct($modifiers);
        $this->preferedFilesDirectory = $preferedFilesDirectory;
    }

    /**
     * Indicate into which ini file the given sections should be stored, when their value
     * is modified with setValue()/setValues(), in priority over the other resolution rules.
     *
     * @param string[] $sections list of section names
     * @param string $filename the ini file. If it is a bare filename (no directory part),
     *                          it is resolved into the directory given to the constructor.
     */
    public function setPreferedFile($sections, $filename)
    {
        if ($this->preferedFilesDirectory !== null && basename($filename) === $filename) {
            $filename = rtrim($this->preferedFilesDirectory, '/').'/'.$filename;
        }
        foreach ($sections as $section) {
            $this->preferedFileBySection[$section] = $filename;
        }
    }

    /**
     * Modify an option in the most relevant ini file of the stack. If the option doesn't exist,
     * it is created into the modifiable file closest to the end of the list.
     *
     * @param string $name    the name of the option to modify
     * @param string $value   the new value
     * @param string $section the section where to set the item. 0 is the global section
     * @param string $key     for option which is an item of array, the key in the array
     */
    public function setValue($name, $value, $section = 0, $key = null)
    {
        $target = $this->resolveTargetModifier($name, $section);
        if ($target === null) {
            throw new IniException('None of the ini contents is alterable');
        }
        $target->setValue($name, $value, $section, $key);
    }

    /**
     * Modify several options. Each option may end up in a different ini file of the stack,
     * depending on where it is the most relevant, so options are routed one by one.
     *
     * @param array  $values  associated array with key=>value
     * @param string $section the section where to set the item. 0 is the global section
     */
    public function setValues($values, $section = 0)
    {
        foreach ($values as $name => $value) {
            $this->setValue($name, $value, $section);
        }
    }

    /**
     * @param string $name
     * @param string $section
     * @return \Jelix\IniFile\IniModifierInterface|null
     */
    protected function resolveTargetModifier($name, $section)
    {
        if (!$section) {
            $section = 0;
        }

        if (isset($this->preferedFileBySection[$section])) {
            $filename = $this->preferedFileBySection[$section];
            $mod = $this->findModifierByFileName($filename);
            if ($mod === null) {
                $mod = new IniModifier($filename);
                $this->insertModifierBeforeLast($filename, $mod);

                return $mod;
            }
            if ($mod instanceof IniModifierInterface) {
                return $mod;
            }
            // else: the file matching the section exists but is read-only, fall through
        }

        $fallback = null;         // closest-to-end modifiable modifier
        $sectionOnlyMatch = null; // closest-to-end modifiable modifier having the section

        foreach ($this->reversedModifiers as $mod) {
            if (!($mod instanceof IniModifierInterface)) {
                continue;
            }
            if ($fallback === null) {
                $fallback = $mod;
            }
            if ($mod->getValue($name, $section) !== null) {
                return $mod;
            }
            if ($sectionOnlyMatch === null && $mod->isSection($section)) {
                $sectionOnlyMatch = $mod;
            }
        }

        return $sectionOnlyMatch !== null ? $sectionOnlyMatch : $fallback;
    }

    /**
     * @param string $filename
     * @return \Jelix\IniFile\IniReaderInterface|null
     */
    protected function findModifierByFileName($filename)
    {
        foreach ($this->modifiers as $mod) {
            if ($mod->getFileName() === $filename) {
                return $mod;
            }
        }

        return null;
    }

    /**
     * insert the given modifier into $this->modifiers, keyed by its filename, right before
     * the current last (highest priority) modifier, so it keeps ultimate priority.
     *
     * @param string $filename
     */
    protected function insertModifierBeforeLast($filename, IniModifierInterface $mod)
    {
        // $this->lastModifierKey cannot be used here: array_reverse() reindexes purely
        // numeric keys, so it does not reliably point to the true key in $this->modifiers.
        $keys = array_keys($this->modifiers);
        $lastKey = end($keys);
        $newModifiers = array();
        foreach ($this->modifiers as $k => $m) {
            if ($k === $lastKey) {
                $newModifiers[$filename] = $mod;
            }
            $newModifiers[$k] = $m;
        }
        $this->modifiers = $newModifiers;
        $this->setReversedArray();
    }
}
