<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-11-09
 * Modified    : 2012-07-12
 * For LOVD    : 3.0-beta-07
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

define('ROOT_PATH', '../');
require ROOT_PATH . 'inc-init.php';

if (empty($_GET['id']) || empty($_GET['object']) || !preg_match('/^[A-Z_]+$/i', $_GET['object'])) {
    die(AJAX_DATA_ERROR);
}

// The required security to load the viewEntry() depends on the data that is shown.
// To prevent security problems if we forget to set a requirement here, we default to LEVEL_ADMIN.
$aNeededLevel =
         array(
                'Transcript_Variant' => 0,
              );

if (isset($aNeededLevel[$_GET['object']])) {
    $nNeededLevel = $aNeededLevel[$_GET['object']];
} else {
    $nNeededLevel = LEVEL_ADMIN;
}

// We can't authorize Curators without loading their level!
if ($_AUTH['level'] < LEVEL_MANAGER && !empty($_AUTH['curates'])) {
    if ($_GET['object'] == 'Transcript_Variant') {
        list($nVariantID, $nTranscriptID) = explode(',', $_GET['id']);
        lovd_isAuthorized('variant', $nVariantID);
    }
    // FIXME; other lovd_isAuthorized() calls?
}

// Require special clearance?
if ($nNeededLevel && (!$_AUTH || $_AUTH['level'] < $nNeededLevel)) {
    // If not authorized, die with error message.
    die(AJAX_NO_AUTH);
}

if (FORMAT == 'text/plain' && !defined('FORMAT_ALLOW_TEXTPLAIN')) {
    die(AJAX_NO_AUTH);
}

$sFile = ROOT_PATH . 'class/object_' . strtolower($_GET['object']) . 's.php';

if (!file_exists($sFile)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}



$sObjectID = '';
$nID = '';
if (in_array($_GET['object'], array('Phenotype', 'Transcript_Variant', 'Custom_ViewList'))) {
    if (isset($_GET['object_id'])) {
        $sObjectID = $_GET['object_id'];
    }
    if (isset($_GET['id'])) {
        $nID = $_GET['id'];
    }

    // Exception for VOT viewEntry, we need to isolate the gene from the ID to correctly pass this to the data object.
    if ($_GET['object'] == 'Transcript_Variant') {
        // This line below is redundant as long as it's also called at the lovd_isAuthorized() call. Remove it here maybe...?
        list($nVariantID, $nTranscriptID) = explode(',', $nID);
        $sObjectID = $_DB->query('SELECT geneid FROM ' . TABLE_TRANSCRIPTS . ' WHERE id = ?', array($nTranscriptID))->fetchColumn();
    }
}
require $sFile;
$_GET['object'] = 'LOVD_' . str_replace('_', '', $_GET['object']); // FIXME; test dit op een windows, test case-insensitivity.
$_DATA = new $_GET['object']($sObjectID);
$_DATA->viewEntry($nID);
?>
