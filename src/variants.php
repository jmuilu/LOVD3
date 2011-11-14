<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-12-21
 * Modified    : 2011-11-11
 * For LOVD    : 3.0-alpha-06
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





if (!ACTION && (empty($_PATH_ELEMENTS[1]) || preg_match('/^chr[0-9A-Z]{1,2}$/', $_PATH_ELEMENTS[1]))) {
    // URL: /variants
    // URL: /variants/chrX
    // View all genomic variant entries, optionally restricted by chromosome.

    if (!empty($_PATH_ELEMENTS[1])) {
        $sChr = $_PATH_ELEMENTS[1];
    } else {
        $sChr = '';
    }

    define('PAGE_TITLE', 'View genomic variants');
    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    require ROOT_PATH . 'class/object_genome_variants.php';
    $_DATA = new LOVD_GenomeVariant();
    $aColsToHide = array('screeningids');
    if ($sChr) {
        $_GET['search_chromosome'] = '="' . substr($sChr, 3) . '"';
        $aColsToHide[] = 'chromosome';
    }
    $_DATA->viewList(false, $aColsToHide);

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (!ACTION && !empty($_PATH_ELEMENTS[1]) && !ctype_digit($_PATH_ELEMENTS[1])) {
    // URL: /variants/in_gene
    // URL: /variants/DMD
    // View all entries in any gene or a specific gene.

    if ($_PATH_ELEMENTS[1] == 'in_gene') {
        $sGene = '';
    } elseif (in_array(rawurldecode($_PATH_ELEMENTS[1]), lovd_getGeneList())) {
        $_GET['search_geneid'] = $sGene = rawurldecode($_PATH_ELEMENTS[1]);
        lovd_isAuthorized('gene', $sGene); // To show non public entries.
    } else {
        // Command or gene not understood.
        // FIXME; perhaps a HTTP/1.0 501 Not Implemented? If so, provide proper output (gene not found) and
        //   test if browsers show that output or their own error page. Also, then, use the same method at
        //   the bottom of all files, as a last resort if command/URL is not understood. Do all of this LATER.
        exit;
    }

    define('PAGE_TITLE', 'View transcript variants' . (!$sGene? '' : ' in ' . $sGene));
    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    require ROOT_PATH . 'class/object_custom_viewlists.php';
    $_DATA = new LOVD_CustomViewList(array('Transcript', 'VariantOnTranscript', 'VariantOnGenome'));
    $_DATA->viewList(false, (!$sGene? '' : array('geneid', 'chromosome')));

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (!empty($_PATH_ELEMENTS[1]) && ctype_digit($_PATH_ELEMENTS[1]) && !ACTION) {
    // URL: /variants/0000000001
    // View specific entry.

    $nID = sprintf('%010d', $_PATH_ELEMENTS[1]);
    define('PAGE_TITLE', 'View genomic variant #' . $nID);
    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    require ROOT_PATH . 'class/object_genome_variants.php';
    $_DATA = new LOVD_GenomeVariant();
    $zData = $_DATA->viewEntry($nID);
    lovd_isAuthorized('variant', $nID);

    $sNavigation = '';
    if ($_AUTH && $_AUTH['level'] >= LEVEL_OWNER) {
        // Authorized user (admin or manager) is logged in. Provide tools.
        $sNavigation = '<A href="variants/' . $nID . '?edit">Edit variant entry</A>';
        if ($_AUTH['level'] >= LEVEL_CURATOR) {
            $sNavigation .= ' | <A href="variants/' . $nID . '?delete">Delete variant entry</A>';
        }
    }

    if ($sNavigation) {
        print('      <IMG src="gfx/trans.png" alt="" width="1" height="5"><BR>' . "\n");
        lovd_showNavigation($sNavigation);
    }

    print('<BR><BR>' . "\n\n" .
          '      <DIV id="viewentryDiv">' . "\n" .
          '      </DIV>' . "\n\n");

    $_GET['search_id_'] = $nID;
    print('<BR><BR>' . "\n\n");
    lovd_printHeader('Variant on transcripts', 'H4');
    require ROOT_PATH . 'class/object_transcript_variants.php';
    $_DATA = new LOVD_TranscriptVariant('', $nID);
    $_DATA->sRowID = '{{transcriptid}}';
    $_DATA->sRowLink = 'javascript:window.location.hash = \'{{ID}}\'; return false';
    $_DATA->viewList(false, array('id_', 'transcriptid', 'status'), true, true);
    unset($_GET['search_id_']);

?>

<SCRIPT type="text/javascript">
var prevHash = '';
$( function () {
    lovd_AJAX_viewEntryLoad();
    setInterval(lovd_AJAX_viewEntryLoad, 250);
});





function lovd_AJAX_viewEntryLoad () {
    var hash = window.location.hash; //Puts hash in variable, but does not removes the # character, because jQuery conveniently needs it.
    if (hash) {
        if (hash != prevHash) {
            // hash present in URL.
            $( prevHash ).attr('class', 'data');
            $( hash ).attr('class', 'data bold');

            $( '#viewentryDiv' ).stop().css('opacity','0');
            $.get('ajax/viewentry.php', { nID: '<?php echo $nID; ?>,' + hash.substring(1), object: 'Transcript_Variant', gene: 'ASL' },
                function(sData) {
                    if (sData != '<?php echo AJAX_NO_AUTH; ?>' && sData != '<?php echo AJAX_DATA_ERROR; ?>' && sData != '<?php echo AJAX_FALSE; ?>') {
                        $( '#viewentryDiv' ).html(sData).fadeTo(1000, 1);
                    }
                });
            prevHash = hash;
        } else {
            $( hash ).attr('class', 'data bold');
        }
    }
}
</SCRIPT>
<?php

    $_GET['search_screeningid'] = (!empty($zData['screeningids'])? $zData['screeningids'] : 0);
    print('<BR><BR>' . "\n\n");
    lovd_printHeader('Screenings', 'H4');
    require ROOT_PATH . 'class/object_screenings.php';
    $_DATA = new LOVD_Screening();
    $_DATA->viewList(false, array('screeningid', 'individualid', 'created_date', 'edited_date'), true, true);

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (empty($_PATH_ELEMENTS[1]) && ACTION == 'create') {
    // Create a new entry.

    lovd_requireAUTH(LEVEL_SUBMITTER);

    define('LOG_EVENT', 'VariantCreate');

    if (isset($_GET['target'])) {
        // On purpose not checking for numeric target. If it's not numeric, we'll automatically get to the error message below.
        $_GET['target'] = sprintf('%010d', $_GET['target']);
        if (mysql_num_rows(lovd_queryDB_Old('SELECT id FROM ' . TABLE_SCREENINGS . ' WHERE id = ?', array($_GET['target'])))) {
            $_POST['screeningid'] = $_GET['target'];
            $_GET['search_id_'] = $_DB->query('SELECT GROUP_CONCAT(DISTINCT geneid SEPARATOR "|") FROM ' . TABLE_SCR2GENE . ' WHERE screeningid = ?', array($_POST['screeningid']))->fetchColumn();
        } else {
            define('PAGE_TITLE', 'Create a new variant entry');
            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            lovd_showInfoTable('The screening ID given is not valid, please go to the desired screening entry and click on the "Add variant" button.', 'warning');
            require ROOT_PATH . 'inc-bot.php';
            exit;
        }
    }

    if (!isset($_GET['reference'])) {
        // URL: /variants?create
        // Select wether the you want to create a variant on the genome or on a transcript.
        define('PAGE_TITLE', 'Create a new variant entry');
        require ROOT_PATH . 'inc-top.php';
        lovd_printHeader(PAGE_TITLE);

        print('      What kind of variant would you like to submit?<BR><BR>' . "\n\n" .
              '      <TABLE border="0" cellpadding="5" cellspacing="1" width="600" class="option">' . "\n" .
              '        <TR onclick="window.location=\'variants?create&amp;reference=Genome' . (isset($_GET['target'])? '&amp;target=' . $_GET['target'] : '') . '\'">' . "\n" .
              '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
              '          <TD><B>I want to create a variant on genomic level &raquo;&raquo;</B></TD></TR>' . "\n" .
              '        <TR onclick="$(\'#viewlistDiv_SelectGeneForSubmit\').toggle();">' . "\n" .
              '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
              '          <TD><B>I want to create a variant on genomic & transcript level &raquo;&raquo;</B></TD></TR></TABLE><BR>' . "\n\n");

        require ROOT_PATH . 'class/object_genes.php';
        $_GET['page_size'] = 10;
        $_DATA = new LOVD_Gene();
        $_DATA->sRowLink = 'variants?create&reference=Transcript&geneid=' . $_DATA->sRowID . (isset($_GET['target'])? '&target=' . $_GET['target'] : '');
        $_GET['search_transcripts'] = '>0';
        $_DATA->viewList('SelectGeneForSubmit', array('geneid', 'transcripts', 'variants', 'diseases_', 'updated_date_'), false, false, false);
        print('      <SCRIPT type="text/javascript">' . "\n" .
              '        $("#viewlistDiv_SelectGeneForSubmit").hide();' . "\n" .
              '      </SCRIPT>' . "\n");

        require ROOT_PATH . 'inc-bot.php';
        exit;





    } elseif (!in_array($_GET['reference'], array('Genome', 'Transcript')) || ($_GET['reference'] == 'Transcript' && empty($_GET['geneid']))) {
        exit;
    }

    // URL: /variants?create&reference=('Genome'|'Transcript')
    // Create a variant on the genome.

    if ($_GET['reference'] == 'Transcript') {
        // On purpose not checking for format of $_GET['geneid']. If it's not right, we'll automatically get to the error message below.
        $sGene = $_GET['geneid'];
        if (!in_array($sGene, lovd_getGeneList())) {
            define('PAGE_TITLE', 'Create a new variant entry');
            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            lovd_showInfoTable('The gene symbol given is not valid, please go to the create variant page and select the desired gene entry.', 'warning');
            require ROOT_PATH . 'inc-bot.php';
            exit;
        } else {
            define('PAGE_TITLE', 'Create a new variant entry for gene ' . $_GET['geneid']);
        }
    } else {
        define('PAGE_TITLE', 'Create a new variant entry');
    }

    lovd_isAuthorized('gene', (isset($sGene)? $sGene : $_AUTH['curates']));

    require ROOT_PATH . 'class/object_genome_variants.php';
    $_DATA = array();
    $_DATA['Genome'] = new LOVD_GenomeVariant();
    if (isset($sGene)) {
        require ROOT_PATH . 'class/object_transcript_variants.php';
        $_DATA['Transcript'] = new LOVD_TranscriptVariant($sGene);
    }
    require ROOT_PATH . 'inc-lib-form.php';

    if (POST) {
        lovd_errorClean();

        $_DATA['Genome']->checkFields($_POST);

        if (isset($sGene)) {
            $_DATA['Transcript']->checkFields($_POST);
        }

        if (!lovd_error()) {
            // Prepare the fields to be used for both genomic and transcript variant information.
            $aFieldsGenome = array_merge(
                                array('allele', 'chromosome', 'owned_by', 'statusid', 'created_by', 'created_date'),
                                $_DATA['Genome']->buildFields());

            // Prepare values.
            $_POST['owned_by'] = ($_AUTH['level'] >= LEVEL_CURATOR? $_POST['owned_by'] : $_AUTH['id']);
            $_POST['statusid'] = ($_AUTH['level'] >= LEVEL_CURATOR? $_POST['statusid'] : STATUS_IN_PROGRESS);
            $_POST['created_by'] = $_AUTH['id'];
            $_POST['created_date'] = date('Y-m-d H:i:s');

            $nID = $_DATA['Genome']->insertEntry($_POST, $aFieldsGenome);

            if (isset($sGene)) {
                $_POST['id'] = $nID;
                $aFieldsTranscript = array_merge(
                                        array('id', 'transcriptid'),
                                        $_DATA['Transcript']->buildFields());
                $aTranscriptID = $_DATA['Transcript']->insertAll($_POST, $aFieldsTranscript);
            }

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Created variant entry ' . $nID);

            if (isset($_POST['screeningid'])) {
                // Add variant to screening.
                $q = lovd_queryDB_Old('INSERT INTO ' . TABLE_SCR2VAR . ' VALUES (?, ?)', array($_POST['screeningid'], $nID));
                if (!$q) {
                    // Silent error.
                    lovd_writeLog('Error', LOG_EVENT, 'Variant entry could not be added to screening #' . $_POST['screeningid']);
                }
            }

            $bSubmit = false;
            if (isset($_POST['screeningid']) && isset($_SESSION['work']['submits'])) {
                foreach($_SESSION['work']['submits'] as $nIndividualID => $aSubmit) {
                    if (in_array($_POST['screeningid'], $aSubmit['screenings'])) {
                        $bSubmit = true;
                        $_POST['individualid'] = $nIndividualID;
                        break;
                    }
                }
            }

            if ($bSubmit) {
                if (!isset($_SESSION['work']['submits'][$_POST['individualid']]['variants'])) {
                    $_SESSION['work']['submits'][$_POST['individualid']]['variants'] = array();
                }
                $_SESSION['work']['submits'][$_POST['individualid']]['variants'][] = $nID;
                $sPersons = ($_SESSION['work']['submits'][$_POST['individualid']]['is_panel']? 'this group of individuals' : 'this individual');
            } else {
                $_SESSION['work']['submits']['variantid'][$nID] = $nID;
            }

            if ($bSubmit) {
                require ROOT_PATH . 'inc-top.php';
                lovd_printHeader(PAGE_TITLE);
                print('      Were there more variants found with this mutation screening?<BR><BR>' . "\n\n" .
                      '      <TABLE border="0" cellpadding="5" cellspacing="1" class="option">' . "\n" .
                      '        <TR onclick="window.location.href=\'' . lovd_getInstallURL() . 'variants?create&amp;target=' . $_POST['screeningid'] . '\'">' . "\n" .
                      '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
                      '          <TD><B>Yes, I want to submit more variants found by this mutation screening</B></TD></TR>' . "\n" .
                      '        <TR onclick="window.location.href=\'' . lovd_getInstallURL() . 'screenings?create&amp;target=' . $_POST['individualid'] . '\'">' . "\n" .
                      '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
                      '          <TD><B>No, I want to submit another screening instead</B></TD></TR>' . "\n" .
                      '        <TR onclick="window.location.href=\'' . lovd_getInstallURL() . 'submit/finish/individual/' . $_POST['individualid'] . '\'">' . "\n" .
                      '          <TD width="30" align="center"><SPAN class="S18">&raquo;</SPAN></TD>' . "\n" .
                      '          <TD><B>No, I have finished my submission</B></TD></TR></TABLE><BR>' . "\n\n");
                require ROOT_PATH . 'inc-bot.php';
            } else {
                header('Location: ' . lovd_getInstallURL() . 'submit/finish/variant?variantid=' . $nID);
            }
            exit;
        }

    } else {
        // Default values.
        $_DATA['Genome']->setDefaultValues();
        if (isset($sGene)) {
            $_DATA['Transcript']->setDefaultValues();
        }
    }



    require ROOT_PATH . 'inc-top.php';
    lovd_printHeader(PAGE_TITLE);

    if (GET) {
        print('      To create a new variant entry, please fill out the form below.<BR>' . "\n" .
              '      <BR>' . "\n\n");
    }

    lovd_errorPrint();

    // Tooltip JS code.
    lovd_includeJS('inc-js-tooltip.php');
    lovd_includeJS('inc-js-custom_links.php');

    // Table.
    print('      <FORM id="variantForm" action="' . CURRENT_PATH . '?create&amp;reference=' . $_GET['reference'] . (isset($sGene)? '&amp;geneid=' . rawurlencode($sGene) : '') . (isset($_POST['screeningid'])? '&amp;target=' . $_GET['target'] : '') . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array_merge(
                 $_DATA['Genome']->getForm((isset($sGene)? $_DATA['Transcript']->getForm() : array())),
                 array(
                        array('', '', 'submit', 'Create variant entry'),
                      ));
    lovd_viewForm($aForm);

    print('      </FORM>' . "\n\n");

    lovd_includeJS('inc-js-variants.php?chromosome=' . $_POST['chromosome'] . (isset($sGene)? '&geneid=' . $sGene : ''));
?>
<SCRIPT type="text/javascript">
var aTranscripts = {
<?php 
    if (isset($sGene)) {
        $i = 0;
        foreach($_DATA['Transcript']->aTranscripts as $nTranscriptID => $sTranscriptNM) {
            if ($i == 0) {
                echo '';
            } else {
                echo ', ';
            }
            echo '\'' . $nTranscriptID . '\' : \'' . $sTranscriptNM . '\''; 
            $i++;
        }
    }
?>};
</SCRIPT>
<?php

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (!empty($_PATH_ELEMENTS[1]) && ctype_digit($_PATH_ELEMENTS[1]) && ACTION == 'edit') {
    // URL: /variants/0000000001?edit
    // Edit an entry.

    $nID = sprintf('%010d', $_PATH_ELEMENTS[1]);
    define('PAGE_TITLE', 'Edit a variant entry');
    define('LOG_EVENT', 'VariantEdit');

    // Require manager clearance.
    lovd_isAuthorized('variant', $nID);
    lovd_requireAUTH(LEVEL_OWNER);

    $bGene = false;
    $z = $_DB->query('SELECT vot.*, GROUP_CONCAT(DISTINCT t.geneid SEPARATOR ";") AS geneids FROM ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' AS vot LEFT OUTER JOIN ' . TABLE_TRANSCRIPTS . ' AS t ON (vot.transcriptid = t.id) WHERE vot.id = ? GROUP BY vot.id', array($nID))->fetchAssoc();
    if (!empty($z['geneids'])) {
        $aGenes = explode(';', $z['geneids']); 
        $bGene = true;
    }

    require ROOT_PATH . 'class/object_genome_variants.php';
    $_DATA = array();
    $_DATA['Genome'] = new LOVD_GenomeVariant();
    $zData = $_DATA['Genome']->loadEntry($nID);
    if ($bGene) {
        require ROOT_PATH . 'class/object_transcript_variants.php';
        foreach ($aGenes as $sGene) {
            if (lovd_isAuthorized('gene', $sGene)) {
                $_DATA['Transcript'][$sGene] = new LOVD_TranscriptVariant($sGene);
                $zData = array_merge($zData, $_DATA['Transcript'][$sGene]->loadAll($nID));
            }
        }
    }

    require ROOT_PATH . 'inc-lib-form.php';

    if (POST) {
        lovd_errorClean();

        $_DATA['Genome']->checkFields($_POST);

        if ($bGene) {
            $_DATA['Transcript'][$sGene]->checkFields($_POST);
        }

        if (!lovd_error()) {
            // Prepare the fields to be used for both genomic and transcript variant information.
            $aFieldsGenome = array_merge(
                                array('allele', 'chromosome', 'owned_by', 'statusid', 'edited_by', 'edited_date'),
                                $_DATA['Genome']->buildFields());

            // Prepare values.
            $_POST['owned_by'] = ($_AUTH['level'] >= LEVEL_CURATOR? $_POST['owned_by'] : $_AUTH['id']);
            $_POST['statusid'] = ($_AUTH['level'] >= LEVEL_CURATOR? $_POST['statusid'] : STATUS_IN_PROGRESS);
            $_POST['edited_by'] = $_AUTH['id'];
            $_POST['edited_date'] = date('Y-m-d H:i:s');

            // FIXME: implement versioning in updateEntry!
            $_DATA['Genome']->updateEntry($nID, $_POST, $aFieldsGenome);

            if ($bGene) {
                $aFieldsTranscript = $_DATA['Transcript'][$sGene]->buildFields();
                $aTranscriptID = $_DATA['Transcript'][$sGene]->updateAll($nID, $_POST, $aFieldsTranscript);
            }

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Edited variant entry ' . $nID);

            // Thank the user...
            header('Refresh: 3; url=' . lovd_getInstallURL() . $_PATH_ELEMENTS[0] . '/' . $nID);

            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            lovd_showInfoTable('Successfully edited the variant entry!', 'success');

            require ROOT_PATH . 'inc-bot.php';
            exit;

        } else {
            // Because we're sending the data back to the form, I need to unset the password field!
            unset($_POST['password']);
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
        print('      To edit a variant entry, please fill out the form below.<BR>' . "\n" .
              '      <BR>' . "\n\n");
    }

    lovd_errorPrint();

    // Tooltip JS code.
    lovd_includeJS('inc-js-tooltip.php');
    lovd_includeJS('inc-js-custom_links.php');

    // Table.
    print('      <FORM id="variantForm" action="' . $_PATH_ELEMENTS[0] . '/' . $nID . '?' . ACTION . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array_merge(
                 $_DATA['Genome']->getForm(($bGene? $_DATA['Transcript'][$sGene]->getForm() : array())),
                 array(
                        array('', '', 'submit', 'Edit variant entry'),
                      ));
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    lovd_includeJS('inc-js-variants.php?chromosome=' . $_POST['chromosome'] . ($bGene? '&geneid=' . $sGene : ''));

?>
<SCRIPT type="text/javascript">
var aTranscripts = {
<?php 
    if ($bGene) {
        $i = 0;
        foreach($_DATA['Transcript'][$sGene]->aTranscripts as $nTranscriptID => $sTranscriptNM) {
            if ($i == 0) {
                echo '';
            } else {
                echo ', ';
            }
            echo '\'' . $nTranscriptID . '\' : \'' . $sTranscriptNM . '\''; 
            $i++;
        }
    }
?>};
</SCRIPT>
<?php

    require ROOT_PATH . 'inc-bot.php';
    exit;
}





if (!empty($_PATH_ELEMENTS[1]) && ctype_digit($_PATH_ELEMENTS[1]) && ACTION == 'delete') {
    // URL: /variants/0000000001?delete
    // Drop specific entry.

    $nID = sprintf('%010d', $_PATH_ELEMENTS[1]);
    define('PAGE_TITLE', 'Delete variant entry #' . $nID);
    define('LOG_EVENT', 'VariantDelete');

    // Require manager clearance.
    lovd_requireAUTH(LEVEL_MANAGER);

    require ROOT_PATH . 'class/object_genome_variants.php';
    $_DATA = new LOVD_GenomeVariant();
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
            // This also deletes the entries in variants_on_transcripts.
            $_DATA->deleteEntry($nID);

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Deleted variant entry #' . $nID);

            // Thank the user...
            header('Refresh: 3; url=' . lovd_getInstallURL() . 'variants');

            require ROOT_PATH . 'inc-top.php';
            lovd_printHeader(PAGE_TITLE);
            lovd_showInfoTable('Successfully deleted the variant entry!', 'success');

            require ROOT_PATH . 'inc-bot.php';
            exit;

        } else {
            // Because we're sending the data back to the form, I need to unset the password field!
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
                        array('POST', '', '', '', '50%', '14', '50%'),
                        array('Deleting variant entry', '', 'print', $nID),
                        'skip',
                        array('Enter your password for authorization', '', 'password', 'password', 20),
                        array('', '', 'submit', 'Delete variant entry'),
                      ));
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    require ROOT_PATH . 'inc-bot.php';
    exit;
}
?>
