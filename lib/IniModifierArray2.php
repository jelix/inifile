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
     * modify an option in the most relevant ini file of the stack. If the option doesn't exist,
     * it is created into the modifiable file closest to the end of the list.
     *
     * @param string $name    the name of the option to modify
     * @param string $value   the new value
     * @param string $section the section where to set the item. 0 is the global section
     * @param string $key     for option which is an item of array, the key in the array
     */
    public function setValue($name, $value, $section = 0, $key = null)
    {
        $target = $this->resolveTargetModifier($name, $section, $key);
        if ($target === null) {
            trigger_error('None of the ini contents is alterable', E_USER_WARNING);
            return;
        }
        $target->setValue($name, $value, $section, $key);
    }

    /**
     * modify several options. Each option may end up in a different ini file of the stack,
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
     * @param string $key
     * @return \Jelix\IniFile\IniModifierInterface|null
     */
    protected function resolveTargetModifier($name, $section, $key = null)
    {
        $fallback = null;         // closest-to-end modifiable modifier
        $sectionOnlyMatch = null; // closest-to-end modifiable modifier having the section

        foreach ($this->reversedModifiers as $mod) {
            if (!($mod instanceof IniModifierInterface)) {
                continue;
            }
            if ($fallback === null) {
                $fallback = $mod;
            }
            if ($mod->getValue($name, $section, $key) !== null) {
                return $mod;
            }
            if ($sectionOnlyMatch === null && $mod->isSection($section)) {
                $sectionOnlyMatch = $mod;
            }
        }

        return $sectionOnlyMatch !== null ? $sectionOnlyMatch : $fallback;
    }
}
