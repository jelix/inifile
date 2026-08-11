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
 * - the preferred modifiable file for the section, if any (see method `setPreferredFile()` or ini attribute `@preferredFile`)
 * - else the modifiable file closest to the end of the list that already has the section and the parameter
 * - else the modifiable file closest to the end of the list that has the section
 * - else the modifiable file closest to the end of the list
 *
 * Unlike IniModifierArray::setValue(), this always finds a writable target as long as any
 * modifier in the stack is writable, even if the literal last element of the list is read-only.
 */
class SmartIniModifierArray extends IniModifierArray
{
    /**
     * map of section name => filename, populated by setPreferredFile().
     * @var array
     */
    protected $preferredFileBySection = array();

    /**
     * @var string|null directory used to resolve filenames given to setPreferredFile()
     */
    protected $preferredFilesDirectory;

    /**
     * @param \Jelix\IniFile\IniReaderInterface[]|string[] $modifiers the list of ini file names or ini reader/modifier objects
     * @param string|null $preferredFilesDirectory directory into which filenames with only relative path
     *                                            given to setPreferredFile() should be resolved
     */
    public function __construct(array $modifiers, $preferredFilesDirectory = null)
    {
        parent::__construct($modifiers);
        $this->preferredFilesDirectory = $preferredFilesDirectory;

        foreach ($this->modifiers as $mod) {
            if (!method_exists($mod, 'getPreferredFiles')) {
                continue;
            }
            foreach ($mod->getPreferredFiles() as $section => $filename) {
                $this->preferredFileBySection[$section] = $this->resolvePreferredFilename($filename);
            }
        }
    }

    /**
     * Indicate into which ini file the given sections should be stored, when their value
     * is modified with setValue()/setValues(), in priority over the other resolution rules.
     *
     * A section name ending with '*' acts as a prefix wildcard: it matches any section whose
     * name starts with the part before the '*' (e.g. 'foo*' matches 'foo', 'foobar', 'foo_baz'...).
     * An exact section name always wins over a wildcard; among several matching wildcards, the
     * one with the longest prefix wins.
     *
     * @param string[] $sections list of section names
     * @param string $filename the ini file. If it is a relative path filename,
     *                          it is resolved into the directory given to the constructor.
     */
    public function setPreferredFile($sections, $filename)
    {
        $filename = $this->resolvePreferredFilename($filename);
        foreach ($sections as $section) {
            $this->preferredFileBySection[$section] = $filename;
        }
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function resolvePreferredFilename($filename)
    {
        if ($this->preferredFilesDirectory !== null && $filename[0] != '/') {
            return rtrim($this->preferredFilesDirectory, '/').'/'.$filename;
        }

        return $filename;
    }

    /**
     * Resolve the preferred filename declared for the given section, honoring prefix
     * wildcards: a key ending with '*' matches any section whose name starts with
     * the part before the '*'. An exact key always wins over a wildcard; among
     * matching wildcards, the one with the longest prefix wins.
     *
     * @param string $section
     * @return string|null
     */
    protected function resolvePreferredFileForSection($section)
    {
        if (isset($this->preferredFileBySection[$section])) {
            return $this->preferredFileBySection[$section];
        }

        $bestPrefixLength = -1;
        $bestFilename = null;
        foreach ($this->preferredFileBySection as $pattern => $filename) {
            if (substr($pattern, -1) !== '*') {
                continue;
            }
            $prefix = substr($pattern, 0, -1);
            if (strpos($section, $prefix) === 0 && strlen($prefix) > $bestPrefixLength) {
                $bestPrefixLength = strlen($prefix);
                $bestFilename = $filename;
            }
        }

        return $bestFilename;
    }

    /**
     * Move sections having a preferred file into that file, when they currently live in
     * another modifiable ini file of the stack. Creates the preferred ini file (inserting it
     * into the stack) if it doesn't exist yet.
     */
    public function dispatchSectionToPreferredFiles()
    {
        // exact declarations are always considered, even for a section that doesn't exist
        // anywhere yet; wildcard declarations are only considered for sections that concretely
        // exist somewhere in the stack, otherwise there would be nothing to dispatch and no
        // reason to create their target file
        $sections = array();
        foreach ($this->preferredFileBySection as $key => $filename) {
            if (substr($key, -1) !== '*') {
                $sections[$key] = $filename;
            }
        }
        foreach ($this->getSectionList() as $section) {
            if (!isset($sections[$section]) && ($filename = $this->resolvePreferredFileForSection($section)) !== null) {
                $sections[$section] = $filename;
            }
        }

        foreach ($sections as $section => $filename) {
            $this->dispatchOneSectionToPreferredFile($section, $filename);
        }
    }

    /**
     * @param string $section
     * @param string $filename
     */
    protected function dispatchOneSectionToPreferredFile($section, $filename)
    {
        $target = $this->findModifierByFileName($filename);
        if ($target === null) {
            $target = new IniModifier($filename);
            $this->insertModifierBeforeLast($filename, $target);
        } elseif (!($target instanceof IniModifierInterface)) {
            return;
        }

        foreach ($this->modifiers as $mod) {
            if ($mod === $target || !($mod instanceof IniModifierInterface)) {
                continue;
            }
            if (!$mod->isSection($section)) {
                continue;
            }
            $target->setValues($mod->getValues($section), $section);
            $mod->removeSection($section);
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

        $filename = $this->resolvePreferredFileForSection($section);
        if ($filename !== null) {
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
