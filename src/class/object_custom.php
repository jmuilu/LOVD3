<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-02-17
 * Modified    : 2012-09-24
 * For LOVD    : 3.0-beta-09
 *
 * Copyright   : 2004-2012 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
 *               Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

// Don't allow direct access.
if (!defined('ROOT_PATH')) {
    exit;
}
// Require parent class definition.
require_once ROOT_PATH . 'class/objects.php';





class LOVD_Custom extends LOVD_Object {
    // This class extends the basic Object class and it handles the Link object.
    var $sObject = 'Custom';
    var $sCategory = '';
    var $bShared = false;
    var $aColumns = array();
    var $aCustomLinks = array();
    var $sObjectID = '';
    var $nID = '';




    function __construct ()
    {
        // Default constructor.
        global $_AUTH, $_DB;

        $aArgs = array();

        $this->sCategory = (empty($this->sCategory)? $this->sObject : $this->sCategory);

        if (!$this->bShared) {
            // "Simple", non-shared, data types (individuals, genomic variants, screenings).
            $sSQL = 'SELECT c.*, ac.* ' .
                    'FROM ' . TABLE_ACTIVE_COLS . ' AS ac ' .
                    'LEFT OUTER JOIN ' . TABLE_COLS . ' AS c ON (c.id = ac.colid) ' .
                    'WHERE c.id LIKE "' . $this->sCategory . '/%" ' .
                    'ORDER BY c.col_order';
        } else {
            // Shared data type (variants on transcripts, phenotypes).
            if ($this->sObjectID) {
                // Parent object given (a gene for variants, a disease for phenotypes).
                if ($this->sObject == 'Phenotype') {
                    $sSQL = 'SELECT c.*, sc.* ' .
                            'FROM ' . TABLE_COLS . ' AS c ' .
                            'INNER JOIN ' . TABLE_SHARED_COLS . ' AS sc ON (sc.colid = c.id) ' .
                            'WHERE c.id LIKE "' . $this->sCategory . '/%" ' .
                            'AND sc.diseaseid = ? ' .
                            'ORDER BY sc.col_order, sc.colid';
                    $aArgs[] = $this->sObjectID;
                } elseif ($this->sObject == 'Transcript_Variant') {
                    $aArgs = explode(',', $this->sObjectID);
                    $sSQL = 'SELECT c.*, sc.* ' .
                            'FROM ' . TABLE_COLS . ' AS c ' .
                            'INNER JOIN ' . TABLE_SHARED_COLS . ' AS sc ON (sc.colid = c.id) ' .
                            'WHERE c.id LIKE "' . $this->sCategory . '/%" ' .
                            'AND sc.geneid IN(?' . str_repeat(', ?', count($aArgs) - 1) . ') ' .
                            'ORDER BY sc.col_order, sc.colid';
                }
            } else {
                // FIXME; kan er niet wat specifieke info in de objects (e.g. object_phenotypes) worden opgehaald, zodat dit stukje hier niet nodig is?
                // FIXME; don't we need a way to fetch all active custom column info, so we can make a general phenotype overview?
                if ($this->sObject == 'Phenotype') {
                    $sSQL = 'SELECT c.*, sc.*, p.id AS phenotypeid ' .
                            'FROM ' . TABLE_COLS . ' AS c ' .
                            'INNER JOIN ' . TABLE_SHARED_COLS . ' AS sc ON (sc.colid = c.id) ' .
                            'INNER JOIN ' . TABLE_PHENOTYPES . ' AS p USING (diseaseid) ' .
                            'WHERE c.id LIKE "' . $this->sCategory . '/%" ' .
                            'AND p.id = ? ' .
                            'ORDER BY sc.col_order';
                } elseif ($this->sObject == 'Transcript_Variant') {
                    $sSQL = 'SELECT c.*, sc.*, vot.id AS variantid ' .
                            'FROM ' . TABLE_COLS . ' AS c ' .
                            'INNER JOIN ' . TABLE_SHARED_COLS . ' AS sc ON (sc.colid = c.id) ' .
                            'INNER JOIN ' . TABLE_TRANSCRIPTS . ' AS t USING (geneid) ' .
                            'INNER JOIN ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' AS vot ON (t.id = vot.transcriptid) ' .
                            'WHERE c.id LIKE "' . $this->sCategory . '/%" ' .
                            'AND vot.id = ? ' .
                            'ORDER BY sc.col_order';
                }
                $aArgs[] = $this->nID;
            }
        }
        $q = $_DB->query($sSQL, $aArgs);
        while ($z = $q->fetchAssoc()) {
            $z['custom_links'] = array();
            $z['form_type'] = explode('|', $z['form_type']);
            $z['select_options'] = explode("\r\n", $z['select_options']);
            $this->aColumns[$z['id']] = $z;
        }



        // Gather the custom link information.
        $aLinks = $_DB->query('SELECT l.*, GROUP_CONCAT(c2l.colid SEPARATOR ";") AS colids FROM ' . TABLE_LINKS . ' AS l INNER JOIN ' . TABLE_COLS2LINKS . ' AS c2l ON (l.id = c2l.linkid) WHERE c2l.colid LIKE ? GROUP BY l.id',
            array($this->sCategory . '/%'))->fetchAllAssoc();
        foreach ($aLinks as $aLink) {
            $aLink['regexp_pattern'] = '/' . str_replace(array('{', '}'), array('\{', '\}'), preg_replace('/\[\d\]/', '(.*)', $aLink['pattern_text'])) . '/';
            $aLink['replace_text'] = preg_replace('/\[(\d)\]/', '\$$1', $aLink['replace_text']);
            $aCols = explode(';', $aLink['colids']);
            foreach ($aCols as $sColID) {
                if (isset($this->aColumns[$sColID])) {
                    $this->aColumns[$sColID]['custom_links'][] = $aLink['id'];
                }
            }
            $this->aCustomLinks[$aLink['id']] = $aLink;
        }

        parent::__construct();

        // Hide entries that are not marked or public.
        if ($_AUTH['level'] < LEVEL_COLLABORATOR) { // This check assumes lovd_isAuthorized() has already been called for gene-specific overviews.
            if (in_array($this->sCategory, array('VariantOnGenome', 'VariantOnTranscript'))) {
                $sAlias = 'vog';
            } else {
                $sAlias = strtolower($this->sCategory{0});
            }
            $this->aSQLViewList['WHERE'] .= (!empty($this->aSQLViewList['WHERE'])? ' AND ' : '') . '(' . ($this->sObject == 'Screening'? 'i' : $sAlias) . '.statusid >= ' . STATUS_MARKED . ' OR (' . $sAlias . '.created_by = "' . $_AUTH['id'] . '" OR ' . $sAlias . '.owned_by = "' . $_AUTH['id'] . '")';
            $this->aSQLViewEntry['WHERE'] .= (!empty($this->aSQLViewEntry['WHERE'])? ' AND ' : '') . '(' . ($this->sObject == 'Screening'? 'i' : $sAlias) . '.statusid >= ' . STATUS_MARKED . ' OR (' . $sAlias . '.created_by = "' . $_AUTH['id'] . '" OR ' . $sAlias . '.owned_by = "' . $_AUTH['id'] . '"))';
            if ($this->sCategory == 'VariantOnGenome' && $_AUTH && (count($_AUTH['curates']) || count($_AUTH['collaborates']))) {
                // Added so that Curators and Collaborators can view the variants for which they have viewing rights in the genomic variant viewlist.
                $this->aSQLViewList['WHERE'] .= ' OR t.geneid IN ("' . implode('", "', array_merge($_AUTH['curates'], $_AUTH['collaborates'])) . '"))';
            } else {
                $this->aSQLViewList['WHERE'] .= ')';
            }
        }
    }





    function buildFields ()
    {
        // Gathers the columns to be used for lovd_(insert/update)Entry and returns them
        global $_AUTH;

        $aFields = array();
        foreach($this->aColumns as $sCol => $aCol) {
            if (!$aCol['public_add'] && $_AUTH['level'] < LEVEL_CURATOR) {
                continue;
            }
            $aFields[] = $sCol;
        }
        return $aFields;
    }





    function buildForm ($sPrefix = '')
    {
        // Builds the array needed to display the form.
        global $_AUTH;
        $aFormData = array();

        foreach ($this->aColumns as $sCol => $aCol) {
            if (!$aCol['public_add'] && $_AUTH['level'] < LEVEL_CURATOR) {
                continue;
            }
            // Build what type of form entry?
            $aEntry = array();
            if ($aCol['form_type'][2] != 'select') {
                // No select entry; add entry name.
                foreach ($aCol['form_type'] as $key => $val) {
                    if (!$key && !$aCol['mandatory']) {
                        // Add '(Optional)'.
                        $val .= ' (optional)';
                    }
                    $aEntry[] = $val;
                    if ($key == 2) {
                        // Add the form entry name.
                        $aEntry[] = $sPrefix . $sCol;
                    }
                }
                // Setting the key allows easy post-processing of the form.
                $aFormData[$sPrefix . $sCol] = $aEntry;

            } else {
                // Select entries are modified a little more - need source data.
                foreach ($aCol['form_type'] as $key => $val) {
                    if (!$key && !$aCol['mandatory']) {
                        // Add '(Optional)'.
                        $val .= ' (optional)';
                    } elseif ($key == 3) { // Size
                        // We need to place the form entry name (e.g. "Individual/Gender") in between.
                        $aEntry[] = $sPrefix . $sCol;
                    } elseif ($key == 4) { // Select: true|false|--select--
                        // We need to place the form entry data in between.
                        $aData = array();
                        foreach ($aCol['select_options'] as $sLine) {
                            if (substr_count($sLine, '=')) {
                                list($sKey, $sVal) = explode('=', $sLine, 2);
                                $sVal = lovd_shortenString(trim($sVal), 75);
                                // NOTE: This array *refuses* to create string keys if the contents are integer strings. So the keys can actually be integers.
                                $aData[trim($sKey)] = $sVal;
                            } else {
                                $sVal = trim($sLine);
                                $sVal = lovd_shortenString($sVal, 75);
                                $aData[$sVal] = $sVal;
                            }
                        }

                        // Add currently filled in data if it's not in the selection_values, or else we'll lose it!
                        if (!empty($_POST[$sCol])) {
                            if (is_array($_POST[$sCol])) {
                                $aPOST = $_POST[$sCol]; // Multiple selection list.
                            } else {
                                $aPOST = array($_POST[$sCol]); // Drop down list.
                            }
                            foreach ($aPOST as $sOption) {
                                if ($sOption && !array_key_exists($sOption, $aData)) {
                                    // Add entry!
                                    $aData[$sOption] = $sOption;
                                }
                            }
                        }

                        $aEntry[] = $aData;
                    }

                    if ($val == 'false') {
                        $val = false;
                    } elseif ($val == 'true') {
                        $val = true;
                    }
                    $aEntry[] = $val;
                }

                // Shorten selection list if source data is shorter.
                if ($aEntry[4] > 1) {
                    // Size > 1.
                    $nItems = count($aEntry[5]);
                    if ($nItems < $aEntry[4]) {
                        // Set size = number of options.
                        $aEntry[4] = $nItems;
                    }
                }

                // Setting the key allows easy post-processing of the form.
                $aFormData[$sPrefix . $sCol] = $aEntry;
            }

            // Any custom links we want to mention?
            if (!empty($aCol['custom_links'])) {
                $sLinks = '';
                foreach ($aCol['custom_links'] as $nLink) {
                    $aLink = $this->aCustomLinks[$nLink];
                    $sToolTip = str_replace(array("\r\n", "\r", "\n"), '<BR>', 'Click to insert:<BR>' . $aLink['pattern_text'] . '<BR><BR>' . addslashes(htmlspecialchars($aLink['description'])));
                    $sLinks .= ($sLinks? ', ' : '') . '<A href="#" onmouseover="lovd_showToolTip(\'' . $sToolTip . '\');" onmouseout="lovd_hideToolTip();" onclick="lovd_insertCustomLink(this, \'' . $aLink['pattern_text'] . '\'); return false">' . $aLink['name'] . '</A>';
                }
                $aFormData[$sPrefix . $sCol . '_links'] = array('', '', 'print', '<SPAN class="S11">(Active custom link' . (count($aCol['custom_links']) == 1? '' : 's') . ' : ' . $sLinks . ')</SPAN>');
            }

            // Need to add description?
            if ($aCol['description_form']) {
                $aFormData[$sPrefix . $sCol . '_notes'] = array('', '', 'note', $aCol['description_form']);
            }
        }

        return $aFormData;
    }





    function buildViewEntry ()
    {
        // Gathers the columns which are active for the current data type and returns them in a viewEntry format
        global $_AUTH;
        $aViewEntry = array();
        foreach ($this->aColumns as $sID => $aCol) {
            if (!$aCol['public_view'] && $_AUTH['level'] < LEVEL_OWNER) {
                continue;
            }
            $aViewEntry[$sID] = $aCol['head_column'];
        }
        return $aViewEntry;
    }





    function buildViewList ()
    {
        // Gathers the columns which are active for the current data type and returns them in a viewList format
        global $_AUTH;
        $aViewList = array();
        foreach ($this->aColumns as $sID => $aCol) {
            if (!$aCol['public_view'] && $_AUTH['level'] < LEVEL_OWNER) {
                continue;
            }
            $bAlignRight = preg_match('/^(DEC|(TINY|SMALL|MEDIUM|BIG)?INT)/', $aCol['mysql_type']);

            $aViewList[$sID] =
                            array(
                                    'view' => array($aCol['head_column'], $aCol['width'], ($bAlignRight? ' align="right"' : '')),
                                    'db'   => array('`' . $aCol['colid'] . '`', 'ASC', true),
                                 );
        }
        return $aViewList;
    }





    function checkFields ($aData, $zData = false)
    {
        global $_AUTH, $_SETT, $_DB;
        // Checks fields before submission of data.
        foreach ($this->aColumns as $sCol => $aCol) {
            if ($aCol['mandatory']) {
                $this->aCheckMandatory[] = $sCol;
            }
            if (isset($aData[$sCol])) {
                $this->checkInputRegExp($sCol, $aData[$sCol]);
                $this->checkSelectedInput($sCol, $aData[$sCol]);
            }
        }

        if (!empty($aData['owned_by'])) {
            if ($_AUTH['level'] >= LEVEL_CURATOR) {
                if (!$_DB->query('SELECT COUNT(*) FROM ' . TABLE_USERS . ' WHERE id = ?', array($aData['owned_by']))->fetchColumn()) {
                    // FIXME; clearly they haven't used the selection list, so possibly a different error message needed?
                    lovd_errorAdd('owned_by', 'Please select a proper owner from the \'Owner of this data\' selection box.');
                }
            } else {
                // FIXME; this is a hack attempt. We should consider logging this. Or just plainly ignore the value.
                lovd_errorAdd('owned_by', 'Not allowed to change \'Owner of this data\'.');
            }
        }

        if (!empty($aData['statusid'])) {
            $aSelectStatus = $_SETT['data_status'];
            unset($aSelectStatus[STATUS_IN_PROGRESS], $aSelectStatus[STATUS_PENDING]);
            if ($_AUTH['level'] >= LEVEL_CURATOR) {
                if (!array_key_exists($aData['statusid'], $aSelectStatus)) {
                    lovd_errorAdd('statusid', 'Please select a proper status from the \'Status of this data\' selection box.');
                }
            } else {
                // FIXME; wie, lager dan LEVEL_CURATOR, komt er op dit formulier? Alleen de data owner. Moet die de status kunnen aanpassen?
                lovd_errorAdd('statusid', 'Not allowed to set \'Status of this data\'.');
            }
        }

        parent::checkFields($aData);
    }





    function checkInputRegExp ($sCol, $val)
    {
        // Checks if field input corresponds to the given regexp pattern.
        $sColClean = preg_replace('/^\d{5}_/', '', $sCol); // Remove prefix (transcriptid) that LOVD_TranscriptVariants puts there.
        if ($this->aColumns[$sColClean]['preg_pattern'] && !empty($_POST[$sCol])) {
            if (!preg_match($this->aColumns[$sColClean]['preg_pattern'], $val)) {
                lovd_errorAdd($sCol, 'The input in the \'' . $this->aColumns[$sColClean]['form_type'][0] . '\' field does not correspond to the required input pattern.');
            }
        }
    }





    function checkSelectedInput ($sCol, $Val)
    {
        // Checks if the selected values are indeed from the selection list.
        $sColClean = preg_replace('/^\d{5}_/', '', $sCol); // Remove prefix (transcriptid) that LOVD_TranscriptVariants puts there.
        if ($this->aColumns[$sColClean]['form_type'][2] == 'select' && $this->aColumns[$sColClean]['form_type'][3] >= 1) {
            if (!empty($Val)) {
                $aOptions = preg_replace('/ *(=.*)?$/', '', $this->aColumns[$sColClean]['select_options']); // Trim whitespace from the options.
                (!is_array($Val)? $Val = array($Val) : false);
                foreach ($Val as $sValue) {
                    $sValue = trim($sValue); // Trim whitespace from $sValue to ensure match independent of whitespace.
                    if (!in_array($sValue, $aOptions)) {
                        lovd_errorAdd($sCol, 'Please select a valid entry from the \'' . $this->aColumns[$sColClean]['form_type'][0] . '\' selection box, \'' . strip_tags($sValue) . '\' is not a valid value.');
                        break;
                    }
                }
            }
        }
    }





    function getDefaultValue ($sCol)
    {
        // Returns the column type, so the input can be checked.
        if (preg_match('/ DEFAULT (\d+|"[^"]+")/', $this->aColumns[$sCol]['mysql_type'], $aRegs)) {
            // Process default values.
            return trim($aRegs[1], '"');
        } else {
            return '';
        }
    }





    function initDefaultValues ()
    {
        // Initiate default values of fields in $_POST.
        foreach ($this->aColumns as $sCol => $aCol) {
            // Fill $_POST with the column's default value.
            $_POST[$sCol] = $this->getDefaultValue($sCol);
        }
    }





    function loadEntry ($nID = false)
    {
        // Loads and returns an entry from the database.
        $zData = parent::loadEntry($nID);

        foreach ($this->aColumns as $sCol => $aCol) {
            if ($aCol['form_type'][2] == 'select' && $aCol['form_type'][3] > 1) {
                $zData[$sCol] = explode(';', $zData[$sCol]);
            }
        }

        return $zData;
    }





    function prepareData ($zData = '', $sView = 'list')
    {
        // Prepares the data before returning it to the user.
        global $_AUTH;

        $zData = parent::prepareData($zData, $sView);
        foreach ($this->aColumns as $sCol => $aCol) {
            if (!$aCol['public_view'] && $_AUTH['level'] < LEVEL_OWNER) {
                continue;
            }
            if (!empty($aCol['custom_links'])) {
                foreach ($aCol['custom_links'] as $nLink) {
                    $sRegexpPattern = $this->aCustomLinks[$nLink]['regexp_pattern'];
                    $sReplaceText = $this->aCustomLinks[$nLink]['replace_text'];
                    if ($sView == 'list') {
                        $sReplaceText = '<SPAN class="custom_link" onmouseover="lovd_showToolTip(\'' . str_replace('"', '\\\'', $sReplaceText) . '\', this);">' . strip_tags($sReplaceText) . '</SPAN>';
                    }
                    $zData[$aCol['id']] = preg_replace($sRegexpPattern . 'U', $sReplaceText, $zData[$aCol['id']]);
                }
            }
        }
        return $zData;
    }
}
?>
