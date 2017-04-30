<?php
/**
* @author      Laurent Jouanneau
* @copyright   2008-2016 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/
use \Jelix\IniFile\IniModifier;
use \Jelix\IniFile\MultiIniModifier;
use \Jelix\IniFile\IniModifierArray;

define('TEMP_PATH', __DIR__.'/temp/');

class testIniFileModifier extends IniModifier {

    function getContent() {
       return $this->content;
    }
    
    function generate(){ return $this->generateIni(); }

}

class testMultiIniFileModifier extends MultiIniModifier {

    function generateMaster(){ return $this->master->generateIni(); }

    function generateOverrider(){ return $this->overrider->generateIni(); }
}

class testIniFileModifierArray extends IniModifierArray {

    function generateIni($index){ return $this->modifiers[$index]->generateIni(); }
}
