<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-03-18
 * Modified    : 2011-09-01
 * For LOVD    : 3.0-alpha-04
 *
 * Copyright   : 2004-2011 Leiden University Medical Center; http://www.LUMC.nl/
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

define('ROOT_PATH', './');
require ROOT_PATH . 'inc-init.php';

if ($_AUTH) {
    // If authorized, check for updates.
    require ROOT_PATH . 'inc-upgrade.php';
}





if (empty($_PATH_ELEMENTS[1]) && !ACTION) {
    // URL: /screenings
    // View all entries.

    define('PAGE_TITLE', 'View screenings');
    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    require ROOT_PATH . 'class/object_screenings.php';
    $_DATA = new LOVD_Screening();
    $_DATA->viewList(false, 'screeningid');

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (!empty($_PATH_ELEMENTS[1]) && ctype_digit($_PATH_ELEMENTS[1]) && !ACTION) {
    // URL: /screenings/0000000001
    // View specific entry.

    $nID = str_pad($_PATH_ELEMENTS[1], 10, '0', STR_PAD_LEFT);
    define('PAGE_TITLE', 'View screening #' . $nID);
    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    // Load appropiate user level for this screening entry.
    lovd_isAuthorized('screening', $nID);

    require ROOT_PATH . 'class/object_screenings.php';
    $_DATA = new LOVD_Screening($nID);
    $zData = $_DATA->viewEntry($nID);
    
    $sNavigation = '';
    if ($_AUTH) {
        if ($_AUTH['level'] >= LEVEL_OWNER) {
            $sNavigation = '<A href="screenings/' . $nID . '?edit">Edit screening information</A>';
            $sNavigation .= ' | <A href="variants?create&amp;target=' . $nID . '">Add variant to screening</A>';
            if ($_AUTH['level'] >= LEVEL_CURATOR) {
                $sNavigation .= ' | <A href="screenings/' . $nID . '?delete">Delete screening entry</A>';
            }
        } elseif ($_AUTH['level'] >= LEVEL_SUBMITTER) {
            // FIXME; maybe remove these links? Decourage submitters to add info to whatever individual? Or maybe an alert (This is not a record submitted by you, are you sure?)
            $sNavigation = '<A href="variants?create&amp;target=' . $nID . '">Add variant to screening</A>';
        }
    }

    if ($sNavigation) {
        print('      <IMG src="gfx/trans.png" alt="" width="1" height="5"><BR>' . "\n");
        lovd_showNavigation($sNavigation);
    }

    $_GET['search_geneid'] = (!empty($zData['search_geneid'])? html_entity_decode(rawurldecode($zData['search_geneid'])) : 0);
    print('<BR><BR>' . "\n\n");
    lovd_printHeader('Genes screened', 'H4');
    require ROOT_PATH . 'class/object_genes.php';
    $_DATA = new LOVD_Gene();
    $_DATA->setSortDefault('id');
    $_DATA->viewList(false, 'geneid', true, true, false);
    unset($_GET['search_geneid']);
    
    $_GET['search_screeningids'] = $nID;
    print('<BR><BR>' . "\n\n");
    lovd_printHeader('Variants found', 'H4');
    require ROOT_PATH . 'class/object_genome_variants.php';
    $_DATA = new LOVD_GenomeVariant();
    $_DATA->setSortDefault('id');
    $_DATA->viewList(false, array('id', 'screeningids'), false, false, false);

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (empty($_PATH_ELEMENTS[1]) && ACTION == 'create') {
    // URL: /screenings?create
    // Create a new entry.

    define('LOG_EVENT', 'ScreeningCreate');

    lovd_requireAUTH();
    
    if (isset($_GET['target']) && ctype_digit($_GET['target'])) {
        $_GET['target'] = str_pad($_GET['target'], 8, "0", STR_PAD_LEFT);
        if (mysql_num_rows(lovd_queryDB_Old('SELECT id FROM ' . TABLE_INDIVIDUALS . ' WHERE id = ?', array($_GET['target'])))) {
            $_POST['individualid'] = $_GET['target'];
            define('PAGE_TITLE', 'Create a new screening information entry for individual #' . $_GET['target']);
        } else {
            define('PAGE_TITLE', 'Create a new screening information entry');
            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            lovd_showInfoTable('The individual ID given is not valid, please go to the desired individual entry and click on the "Add screening" button.', 'stop');
            require ROOT_PATH . 'inc-bot.php';
            exit;
        }
    } else {
        exit;
    }

    require ROOT_PATH . 'class/object_screenings.php';
    $_DATA = new LOVD_Screening();
    require ROOT_PATH . 'inc-lib-form.php';

    if (POST) {
        lovd_errorClean();

        $_DATA->checkFields($_POST);

        if (!lovd_error()) {
            // Fields to be used.
            $aFields = array_merge(
                            array('individualid', 'ownerid', 'created_by', 'created_date'),
                            $_DATA->buildFields());

            // Prepare values.
            $_POST['ownerid'] = ($_AUTH['level'] >= LEVEL_CURATOR? $_POST['ownerid'] : $_AUTH['id']);
            $_POST['created_by'] = $_AUTH['id'];
            $_POST['created_date'] = date('Y-m-d H:i:s');

            $nID = $_DATA->insertEntry($_POST, $aFields);

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Created screening information entry ' . $nID);
            
            $aSuccessGenes = array();
            if (!empty($_POST['genes']) && is_array($_POST['genes'])) {
                foreach ($_POST['genes'] as $sGene) {
                    // Add disease to gene.
                    if (in_array($sGene, lovd_getGeneList())) {
                        $q = lovd_queryDB_Old('INSERT INTO ' . TABLE_SCR2GENE . ' VALUES (?, ?)', array($nID, $sGene));
                        if (!$q) {
                            // Silent error.
                            // FIXME; maybe better to group the error messages, just like when editing?
                            lovd_writeLog('Error', LOG_EVENT, 'Gene entry ' . $sGene . ' - could not be added to screening ' . $nID);
                        } else {
                            $aSuccessGenes[] = $sGene;
                        }
                    }
                }
            }

            if (count($aSuccessGenes)) {
                lovd_writeLog('Event', LOG_EVENT, 'Gene entries successfully added to screening ' . $nID);
            }

            if (!isset($_SESSION['work']['submits'][$_POST['individualid']]['screenings'])) {
                $_SESSION['work']['submits'][$_POST['individualid']]['screenings'] = array();
            }

            $_SESSION['work']['submits'][$_POST['individualid']]['screenings'][$nID] = array();
            $sPersons = (false && $_POST['panel_size'] > 1? 'this group of individuals' : 'this individual');
            $bSubmit = isset($_SESSION['work']['submits'][$_POST['individualid']]);

            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            print('      Were there any variants found with this mutation screening?<BR><BR>' . "\n\n" .
                  '      <TABLE border="0" cellpadding="5" cellspacing="1" class="option">' . "\n" .
                  '        <TR onclick="window.location.href=\'' . lovd_getInstallURL() . 'variants?create&amp;target=' . $nID . '\'">' . "\n" .
                  '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
                  '          <TD><B>Yes, I want to submit variants found by this mutation screening</B></TD></TR>' . "\n" .
                  '        <TR onclick="window.location.href=\'' . lovd_getInstallURL() . 'screenings?create&amp;target=' . $_POST['individualid'] . '\'">' . "\n" .
                  '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
                  '          <TD><B>No, I want to submit another mutation screening on ' . $sPersons . ' instead</B></TD></TR>' . "\n" .
                  '        <TR onclick="window.location.href=\'' . lovd_getInstallURL() . 'submit/individual?individualid=' . $_POST['individualid'] . '\'">' . "\n" .
                  '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
                  '          <TD><B>No, I have finished' . ($bSubmit? ' my submission' : '' ) . '</B></TD></TR></TABLE><BR>' . "\n\n");
            require ROOT_PATH . 'inc-bot.php';
            exit;
        }

    } else {
        // Default values.
        $_DATA->setDefaultValues();
    }



    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    if (GET) {
        print('      To create a new screening information entry, please fill out the form below.<BR>' . "\n" .
              '      <BR>' . "\n\n");
    }

    lovd_errorPrint();

    // Tooltip JS code.
    lovd_includeJS('inc-js-tooltip.php');
    lovd_includeJS('inc-js-custom_links.php');

    // Table.
    print('      <FORM action="' . CURRENT_PATH . '?' . ACTION . '&amp;target=' . $_GET['target'] . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array_merge(
                 $_DATA->getForm(),
                 array(
                        array('', '', 'submit', 'Create screening information entry'),
                      ));
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (!empty($_PATH_ELEMENTS[1]) && ctype_digit($_PATH_ELEMENTS[1]) && ACTION == 'edit') {
    // URL: /screenings/0000000001?edit
    // Edit an entry.

    $nID = str_pad($_PATH_ELEMENTS[1], 10, "0", STR_PAD_LEFT);
    define('PAGE_TITLE', 'Edit an screening information entry');
    define('LOG_EVENT', 'ScreeningEdit');

    // Load appropiate user level for this screening entry.
    lovd_isAuthorized('screening', $nID);
    lovd_requireAUTH(LEVEL_OWNER);

    require ROOT_PATH . 'class/object_screenings.php';
    $_DATA = new LOVD_Screening();
    $zData = $_DATA->loadEntry($nID);
    require ROOT_PATH . 'inc-lib-form.php';

    if (POST) {
        lovd_errorClean();

        $_DATA->checkFields($_POST);

        if (!lovd_error()) {
            // Fields to be used.
            $aFields = array_merge(
                            array('ownerid', 'edited_by', 'edited_date'),
                            $_DATA->buildFields());

            // Prepare values.
            $_POST['individualid'] = $zData['individualid'];
            $_POST['ownerid'] = ($_AUTH['level'] >= LEVEL_CURATOR? $_POST['ownerid'] : $_AUTH['id']);
            $_POST['edited_by'] = $_AUTH['id'];
            $_POST['edited_date'] = date('Y-m-d H:i:s');
            
            // FIXME: implement versioning in updateEntry!
            $_DATA->updateEntry($nID, $_POST, $aFields);

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Edited screening information entry ' . $nID);

            // Change linked genes?
            // Genes the screening is currently linked to.

            // Remove genes.
            $aToRemove = array();
            foreach ($zData['genes'] as $sGene) {
                if (!in_array($sGene, $_POST['genes'])) {
                    // User has requested removal...
                    $aToRemove[] = $sGene;
                }
            }

            if ($aToRemove) {
                $q = lovd_queryDB_Old('DELETE FROM ' . TABLE_SCR2GENE . ' WHERE screeningid = ? AND geneid IN (?' . str_repeat(', ?', count($aToRemove) - 1) . ')', array_merge(array($zData['id']), $aToRemove));
                if (!$q) {
                    // Silent error.
                    lovd_writeLog('Error', LOG_EVENT, 'Gene information entr' . (count($aToRemove) == 1? 'y' : 'ies') . ' ' . implode(', ', $aToRemove) . ' could not be removed from screening ' . $nID);
                } else {
                    lovd_writeLog('Event', LOG_EVENT, 'Gene information entr' . (count($aToRemove) == 1? 'y' : 'ies') . ' ' . implode(', ', $aToRemove) . ' successfully removed from screening ' . $nID);
                }
            }

            // Add genes.
            $aSuccess = array();
            $aFailed = array();
            foreach ($_POST['genes'] as $sGene) {
                if (!in_array($sGene, $zData['genes']) && in_array($sGene, lovd_getGeneList())) {
                    // Add gene to screening.
                    $q = lovd_queryDB_Old('INSERT IGNORE INTO ' . TABLE_SCR2GENE . ' VALUES (?, ?)', array($nID, $sGene));
                    if (!$q) {
                        $aFailed[] = $sGene;
                    } else {
                        $aSuccess[] = $sGene;
                    }
                }
            }
            if ($aFailed) {
                // Silent error.
                lovd_writeLog('Error', LOG_EVENT, 'Gene information entr' . (count($aFailed) == 1? 'y' : 'ies') . ' ' . implode(', ', $aFailed) . ' could not be added to screening ' . $nID);
            } elseif ($aSuccess) {
                lovd_writeLog('Event', LOG_EVENT, 'Gene information entr' . (count($aSuccess) == 1? 'y' : 'ies') . ' ' . implode(', ', $aSuccess) . ' successfully added to screening ' . $nID);
            }

            // Thank the user...
            header('Refresh: 3; url=' . lovd_getInstallURL() . $_PATH_ELEMENTS[0] . '/' . $nID);

            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            lovd_showInfoTable('Successfully edited the screening information entry!', 'success');

            require ROOT_PATH . 'inc-bot.php';
            exit;

        }
    } else {
        // Default values.
        foreach ($zData as $key => $val) {
            $_POST[$key] = $val;
        }
    }



    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    if (GET) {
        print('      To edit an screening information entry, please fill out the form below.<BR>' . "\n" .
              '      <BR>' . "\n\n");
    }

    lovd_errorPrint();

    // Tooltip JS code.
    lovd_includeJS('inc-js-tooltip.php');
    lovd_includeJS('inc-js-custom_links.php');

    // Table.
    print('      <FORM action="' . $_PATH_ELEMENTS[0] . '/' . $nID . '?' . ACTION . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array_merge(
                 $_DATA->getForm(),
                 array(
                        array('', '', 'submit', 'Edit screening information entry'),
                      ));
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (!empty($_PATH_ELEMENTS[1]) && ctype_digit($_PATH_ELEMENTS[1]) && ACTION == 'delete') {
    // URL: /screenings/0000000001?delete
    // Drop specific entry.

    $nID = str_pad($_PATH_ELEMENTS[1], 5, '0', STR_PAD_LEFT);
    define('PAGE_TITLE', 'Delete screening information entry ' . $nID);
    define('LOG_EVENT', 'ScreeningDelete');

    // Require manager clearance.
    lovd_requireAUTH(LEVEL_MANAGER);

    require ROOT_PATH . 'class/object_screenings.php';
    $_DATA = new LOVD_Screening();
    $zData = $_DATA->loadEntry($nID);
    require ROOT_PATH . 'inc-lib-form.php';

    if (!empty($_POST)) {
        lovd_errorClean();

        // Mandatory fields.
        if (empty($_POST['password'])) {
            lovd_errorAdd('password', 'Please fill in the \'Enter your password for authorization\' field.');
        }

        // User had to enter his/her password for authorization.
        if ($_POST['password'] && !lovd_verifyPassword($_POST['password'], $_AUTH['password'])) {
            lovd_errorAdd('password', 'Please enter your correct password for authorization.');
        }

        if (!lovd_error()) {
            // Query text.
            // This also deletes the entries in gen2dis and transcripts.
            $_DATA->deleteEntry($nID);

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Deleted screening information entry ' . $nID);

            // Thank the user...
            header('Refresh: 3; url=' . lovd_getInstallURL() . 'screenings');

            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            lovd_showInfoTable('Successfully deleted the screening information entry!', 'success');

            require ROOT_PATH . 'inc-bot.php';
            exit;

        } else {
            // Because we're sending the data back to the form, I need to unset the password fields!
            unset($_POST['password']);
        }
    }



    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    lovd_errorPrint();

    // Table.
    print('      <FORM action="' . $_PATH_ELEMENTS[0] . '/' . $nID . '?' . ACTION . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array_merge(
                 array(
                        array('POST', '', '', '', '40%', '14', '60%'),
                        array('Deleting screening information entry', '', 'print', $nID),
                        'skip',
                        array('Enter your password for authorization', '', 'password', 'password', 20),
                        array('', '', 'submit', 'Delete screening information entry'),
                      ));
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    require ROOT_PATH . 'inc-bot.php';
    exit;
}

?>
