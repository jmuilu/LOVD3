<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2012-03-27
 * Modified    : 2012-08-30
 * For LOVD    : 3.0-beta-08
 *
 * Copyright   : 2004-2012 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *               Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
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





class LOVD_Template {
    // This class provides the code necessary to view the headers and footers.
    // It's replacing inc-top.php, inc-top-clean.php, inc-bot.php, inc-bot-clean.php,
    //   and the lovd_printHeader() function from inc-lib-init.php.
    var $bTopIncluded = false; // Will become true if header has been included already.
    var $bBotIncluded = false; // Will become true if bottom has been included already.
    var $aTitlesPrinted = array(); // Makes sure we don't print the same title twice.
    var $bFull = true; // Will become false if the "clean" header has been requested.
    var $aMenu = array(); // Contains the menu with all its links. Built up in buildMenu().





    function __construct ()
    {
        // Constructor.
        if (substr(lovd_getProjectFile(), 0, 6) == '/ajax/') {
            // We are in an AJAX call right now, so never include the header or footer.
            $this->bTopIncluded = true;
            $this->bBotIncluded = true;
        }
    }





    function buildMenu ()
    {
        // Builds up the menu array, to be used in the full text/html header.
        // Can't be in the constructor, because that one is called before we have $_SESSION.
        global $_AUTH;

        if (ROOT_PATH == '../' || defined('NOT_INSTALLED')) {
            // In install directory.
            $this->aMenu = array();
            return true;
        }

        $this->aMenu =
            array(
                        'genes' => (!empty($_SESSION['currdb'])? $_SESSION['currdb'] . ' homepage' : 'View all genes'),
                        'genes_' =>
                         array(
                                '' => array('menu_magnifying_glass.png', 'View all genes', 0),
                                '/genes/' . $_SESSION['currdb'] => array('menu_magnifying_glass.png', 'View the ' . $_SESSION['currdb'] . ' gene homepage', 0),
                                '/genes/' . $_SESSION['currdb'] . '/graphs' => array('menu_graphs.png', 'View graphs about the ' . $_SESSION['currdb'] . ' gene database', 0),
                                'create' => array('plus.png', 'Create a new gene entry', LEVEL_MANAGER),
                              ),
                        'transcripts' => 'View transcripts',
                        'transcripts_' =>
                         array(
                                '' => array('menu_transcripts.png', 'View all transcripts', 0),
                                '/transcripts/' . $_SESSION['currdb'] => array('menu_transcripts.png', 'View all transcripts of the ' . $_SESSION['currdb'] . ' gene', 0),
                                'create' => array('plus.png', 'Create a new transcript information entry', LEVEL_CURATOR),
                              ),
                        'variants' => 'View variants',
                        'variants_' =>
                         array(
                                '' => array('menu_magnifying_glass.png', 'View all genomic variants', 0),
                                '/variants/in_gene' => array('menu_magnifying_glass.png', 'View all variants affecting transcripts', 0),
                                '/variants/' . $_SESSION['currdb'] => array('menu_magnifying_glass.png', 'View all variants in the ' . $_SESSION['currdb'] . ' gene', 0),
                                '/view/' . $_SESSION['currdb'] => array('menu_magnifying_glass.png', 'Full data view for the ' . $_SESSION['currdb'] . ' gene', 0),
                                '/submit' => array('plus.png', 'Create a new data submission', LEVEL_SUBMITTER),
                              ),
                        'individuals' => 'View individuals',
                        'individuals_' =>
                         array(
                                '' => array('menu_magnifying_glass.png', 'View all individuals', 0),
                                '/individuals/' . $_SESSION['currdb'] => array('menu_magnifying_glass.png', 'View all individuals screened for ' . $_SESSION['currdb'], 0),
                                'create' => array('plus.png', 'Create a new data submission', LEVEL_SUBMITTER),
                                'hr',
                                '/columns/Individual?search_active_=1' => array('menu_columns.png', 'View active custom columns', LEVEL_MANAGER),
                                '/columns/Individual?search_active_=0' => array('menu_columns.png', 'Enable more custom columns', LEVEL_MANAGER),
                              ),
                        'diseases' => 'View diseases',
                         array(
                                '' => array('menu_magnifying_glass.png', 'View all diseases', 0),
                                'create' => array('plus.png', 'Create a new disease information entry', LEVEL_CURATOR),
                                '/columns/Phenotype' => array('menu_columns_add.png', 'View available phenotype columns', LEVEL_CURATOR),
                              ),
                        'screenings' => 'View screenings',
                        'screenings_' =>
                         array(
                                '' => array('menu_magnifying_glass.png', 'View all screenings', 0),
                                '/screenings/' . $_SESSION['currdb'] => array('menu_magnifying_glass.png', 'View all screenings for the ' . $_SESSION['currdb'] . ' gene', 0),
                                '/submit' => array('plus.png', 'Create a new data submission', LEVEL_SUBMITTER),
                                'hr',
                                '/columns/Screening?search_active_=1' => array('menu_columns.png', 'View active custom columns', LEVEL_MANAGER),
                                '/columns/Screening?search_active_=0' => array('menu_columns.png', 'Enable more custom columns', LEVEL_MANAGER),
                              ),
                        'submit' => 'Submit new data',
                         array(
                                 '' => array('plus.png', 'Submit new data', 0),
                              ),
                        'users' => 'LOVD users &amp; submitters',
                        'users_' =>
                         array(
                                '' => array('menu_magnifying_glass.png', 'View all users', LEVEL_MANAGER),
                                'create' => array('plus.png', 'Register a new user account', LEVEL_MANAGER), // FIXME; submitter_register?
                                // Public list of submitters?
                                // My submissions?
                              ),
                        'configuration' => 'LOVD configuration area',
                        'configuration_' =>
                         array(
/*
                                        array('variants.php', 'search_all&search_status_=Submitted%7CNon_Public%7CMarked', 'Curate', 'Curate', 'lovd_variants_curate'),
                                        'vr',
                                        array('config_free_edit.php', 'fnr', 'Find &amp; Replace', 'Find &amp; Replace', 'lovd_free_edit_fnr'),
                                        array('config_free_edit.php', 'copy', 'Copy Column', 'Copy Column', 'lovd_free_edit_copy'),
                                        'vr',
*/
                                '/columns/VariantOnTranscript' => array('menu_columns_add.png', 'Add variant column to ' . ($_SESSION['currdb']? $_SESSION['currdb'] : 'gene'), LEVEL_CURATOR),
                                '/genes/' . $_SESSION['currdb'] . '/columns' => array('menu_columns.png', 'View variant columns enabled in ' . ($_SESSION['currdb']? $_SESSION['currdb'] : 'gene'), LEVEL_CURATOR),
/*
                                        'vr',
                                        array('genes', 'manage', 'Edit gene db', 'Manage ' . $_SESSION['currdb'] . ' gene', 'lovd_database_edit'),
                                        array('genes', 'empty', 'Empty gene db', 'Empty ' . $_SESSION['currdb'] . ' gene', 'lovd_database_empty'),
                                        'vr',
                                        array('download.php', 'view_all', 'Download', 'Download all variants from the ' . $_SESSION['currdb'] . ' gene database', 'lovd_save'),
                                        array('import', '', 'Import', 'Import variants into the ' . $_SESSION['currdb'] . ' gene database', 'lovd_database_import'),
                                        'vr',
                                        array('scripts', '', 'Scripts', 'LOVD scripts', 'lovd_scripts'),
*/
                                      ),
                        'setup' => 'LOVD system setup',
                        'setup_' =>
                         array(
                                '/settings?edit' => array('menu_settings.png', 'LOVD system settings', LEVEL_MANAGER),
                                'hr',
                                '/columns?create' => array('menu_columns_create.png', 'Create new custom data column', LEVEL_MANAGER),
                                '/columns' => array('menu_columns.png', 'Browse all custom data columns', LEVEL_MANAGER),
                                'hr',
                                '/links?create' => array('menu_links.png', 'Create a new custom link', LEVEL_MANAGER),
                                '/links' => array('menu_links.png', 'Browse all available custom links', LEVEL_MANAGER),
                                'hr',
                                '/logs' => array('menu_logs.png', 'View system logs', LEVEL_MANAGER),
                              ),
//                        'docs' => 'LOVD documentation',
//                         array(
//                                '' => array('', 'LOVD manual table of contents', 0),
//                              ),
                    );

        // Remove certain menu entries, if the user has no access to them.
        // FIXME; Can't we foreach() through everything and, if all links from a manu item are removed, then also remove the item itself?
        if (!$_AUTH || $_AUTH['level'] < LEVEL_MANAGER) {
            unset($this->aMenu['users'], $this->aMenu['users_']); // FIXME; Submitter list should be public.
            unset($this->aMenu['setup'], $this->aMenu['setup_']);
            if (!$_AUTH || !count($_AUTH['curates'])) {
                unset($this->aMenu['configuration'], $this->aMenu['configuration_']);
            }
        }

        // Remove certain menu entries, if there is no gene selected.
        if (!$_SESSION['currdb']) {
            unset($this->aMenu['genes_']['/genes/']);
            unset($this->aMenu['genes_']['/genes//graphs']);
            unset($this->aMenu['transcripts_']['/transcripts/']);
            unset($this->aMenu['variants_']['/variants/']);
            unset($this->aMenu['variants_']['/view/']);
            unset($this->aMenu['individuals_']['/individuals/']);
            unset($this->aMenu['screenings_']['/screenings/']);
        }

        if (!defined('PAGE_TITLE')) {
            $sFile = substr(lovd_getProjectFile(), 1, strrpos(lovd_getProjectFile(), '.') - 1); // Isolate "genes" out of "/genes.php".
            if (array_key_exists($sFile, $this->aMenu)) {
                define('PAGE_TITLE', $this->aMenu[$sFile]);
            }
        }

        return true;
    }





    function printFooter ($Arg1 = true)
    {
        // Check which footer we're supposed to print, and forward.
        if (!$this->bTopIncluded) {
            // Never got header included! Forget it then, don't include the bot. Bug in LOVD.
            return false;
        } elseif ($this->bBotIncluded) {
            // Bottom has already been included! Forget it then, don't include the bot. Bug in LOVD.
            return false;
        }

        $this->bBotIncluded = true;
        switch (FORMAT) {
            case 'text/plain':
                return false;
            case 'text/html':
            default:
                return $this->printFooterHTML($Arg1);
        }
        $this->bBotIncluded = false;
        return false;
    }





    function printFooterHTML ($bCloseHTML = true)
    {
        // Print the LOVD footer, including the update checker and mapper (if $bFull == true).
        global $_AUTH, $_SETT, $_STAT;

        if (ROOT_PATH == '../') {
            // In the install directory, closing the tables opened by /install/index.php that /install/inc-bot.php used to close.
            print("\n\n" .
                  '    </TD>' . "\n" .
                  '  </TR>' . "\n" .
                  '</TABLE>' . "\n");
        }
        ?>









    </TD>
  </TR>
</TABLE>
<?php
        if (!$this->bFull) {
            if ($bCloseHTML) {
                // Close the <BODY> and <HTML> tags. Normal behaviour except when for instance the Progress Bar is used.
                print("\n" .
                      '</BODY>' . "\n" .
                      '</HTML>' . "\n");
            }
            return true;
        }
?>
</DIV>
<BR>

<TABLE border="0" cellpadding="0" cellspacing="0" width="100%" class="footer">
  <TR>
    <TD width="84">
      &nbsp;
    </TD>
    <TD align="center">
<?php
        if (substr(lovd_getProjectFile(), 0, 6) == '/docs/') {
            // In documents section.
            print('  For the latest version of the LOVD manual, <A href="' . $_SETT['upstream_URL'] . $_SETT['system']['tree'] . '/docs/" target="_blank">check the online version</A>.<BR>' . "\n");

        }
        print('  Powered by <A href="' . $_SETT['upstream_URL'] . $_STAT['tree'] . '/" target="_blank">LOVD v.' . $_STAT['tree'] . '</A> Build ' . $_STAT['build'] . '<BR>' . "\n" .
              '  &copy;2004-2012 <A href="http://www.lumc.nl/" target="_blank">Leiden University Medical Center</A>' . "\n");
?>
    </TD>
    <TD width="42" align="right">
      <IMG src="gfx/lovd_mapping_99.png" alt="" title="" width="32" height="32" id="mapping_progress" style="margin : 5px;">
    </TD>
    <TD width="42" align="right">
<?php
        if (!(defined('NOT_INSTALLED') || defined('MISSING_CONF') || defined('MISSING_STAT'))) {
            if ((time() - strtotime($_STAT['update_checked_date'])) > (60*60*24)) {
                // Check for updates!
                $sImgURL = 'check_update?icon';
            } else {
                // No need to re-check, use saved info.
                if ($_STAT['update_version'] == 'Error') {
                    $sType = 'error';
                } elseif (lovd_calculateVersion($_STAT['update_version']) > lovd_calculateVersion($_SETT['system']['version'])) {
                    $sType = 'newer';
                } else {
                    $sType = 'newest';
                }
                $sImgURL = 'gfx/lovd_update_' . $sType . '_blue.png';
            }
            if ($_AUTH && ($_AUTH['level'] >= LEVEL_MANAGER || count($_AUTH['curates']))) {
                print('      <A href="#" onclick="lovd_openWindow(\'' . lovd_getInstallURL() . 'check_update\', \'CheckUpdate\', 650, 175); return false;"><IMG src="' . $sImgURL . '" alt="" width="32" height="32" style="margin : 5px;"></A>' . "\n");
            } else {
                print('      <IMG src="' . $sImgURL . '" alt="" width="32" height="32" style="margin : 5px;">' . "\n");
            }
        }
?>
    </TD>
  </TR>
</TABLE>

</TD></TR></TABLE>
<SCRIPT type="text/javascript">
  <!--
<?php
        if (!(ROOT_PATH == '../' || defined('NOT_INSTALLED'))) {
            // In install directory.
            print('
function lovd_mapVariants ()
{
    // This function requests the script that will do the actual work.

    // First unbind any onclick handlers on the status image.
    $("#mapping_progress").unbind();

    // Now request the script.
    $.get("' . ROOT_PATH . 'ajax/map_variants.php", function (sResponse)
        {
            // The server responded successfully. Let\'s see what he\'s saying.
            aResponse = sResponse.split("\t");
            $("#mapping_progress").attr({"src": "gfx/lovd_mapping_" + aResponse[1] + (aResponse[1] == "preparing"? ".gif" : ".png"), "title": aResponse[2]});

            if (sResponse.indexOf("Notice") >= 0 || sResponse.indexOf("Warning") >= 0 || sResponse.indexOf("Error") >= 0 || sResponse.indexOf("Fatal") >= 0) {
                // Something went wrong while processing the request, don\'t try again.
                $("#mapping_progress").attr({"src": "gfx/lovd_mapping_99.png", "title": "There was a problem with LOVD while mapping variants to transcripts."});
            } else if (aResponse[0] == "' . AJAX_TRUE . '") {
                // More variants to map. Re-call.
                setTimeout("lovd_mapVariants()", 50);
            } else {
                // No more variants to map. But allow the user to try.
                $("#mapping_progress").click(lovd_mapVariants);
            }
        }
    ).error(function ()
        {
            // Something went wrong while contacting the server, don\'t try again.
            $("#mapping_progress").attr({"src": "gfx/lovd_mapping_99.png", "title": "There was a problem with LOVD while mapping variants to transcripts."});
        }
    );
}
');

            // Not every page request should trigger the mapping...
            if (!empty($_SESSION['mapping']['time_complete']) && $_SESSION['mapping']['time_complete'] >= (time() - 60 * 60 * 24)) {
                // If it is less than one day ago that mapping was complete, don't start it automatically.
                print('$("#mapping_progress").click(lovd_mapVariants);' . "\n");
            } elseif (!empty($_SESSION['mapping']['time_error']) && $_SESSION['mapping']['time_error'] >= (time() - 60 * 60)) {
                // If it is less than one hour ago that an error occurred, don't start it either.
                print('$("#mapping_progress").click(lovd_mapVariants);' . "\n");
                print('$("#mapping_progress").attr("Title", "Mapping is temporarily suspended because of network problems on the last attempt. Click to retry.");' . "\n");
            } else {
                // If we won't start it, the user should be able to start it himself.
                print('setTimeout("lovd_mapVariants()", 500);' . "\n");
            }
        }
?>
  // -->
</SCRIPT>

<?php
        if ($bCloseHTML) {
            // Close the <BODY> and <HTML> tags. Normal behaviour except when for instance the Progress Bar is used.
            print('</BODY>' . "\n" .
                  '</HTML>' . "\n");
        } else {
            flush();
            @ob_end_flush(); // Can generate errors on the screen if no buffer found.
        }
        return true;
    }





    function printHeader ($bFull = true)
    {
        // Check which header we're supposed to print, and forward.

        if ($this->bTopIncluded) {
            // Already included before!
            return false;
        }

        $this->bFull = ($bFull && !isset($_GET['in_window']));
        $this->bTopIncluded = true;
        switch (FORMAT) {
            case 'text/plain':
                if (!defined('FORMAT_ALLOW_TEXTPLAIN')) {
                    die('text/plain not allowed here');
                }
                return false;
            case 'text/html':
            default:
                return $this->printHeaderHTML($this->bFull);
        }
        $this->bTopIncluded = false;
        return false;
    }





    function printHeaderHTML ($bFull = true)
    {
        // Print the LOVD header, including the menu (if $bFull == true).
        global $_AUTH, $_CONF, $_DB, $_SETT, $_STAT;

        // Build menu, if tabs are shown.
        if ($bFull) {
            $this->buildMenu();
        }

        ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<HTML lang="en_US">
<HEAD>
  <TITLE><?php echo (!defined('PAGE_TITLE')? '' : PAGE_TITLE . ' - ') . $_CONF['system_title']; ?></TITLE>
  <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <META name="author" content="LOVD development team, LUMC, Netherlands">
  <META name="generator" content="gPHPEdit / GIMP @ GNU/Linux (Ubuntu)">
  <BASE href="<?php echo lovd_getInstallURL(); ?>">
  <LINK rel="stylesheet" type="text/css" href="styles.css">
  <LINK rel="shortcut icon" href="favicon.ico" type="image/x-icon">

<?php
// FIXME; later?
/*  <LINK rel="alternate" type="application/atom+xml" title="<?php echo $_CONF['system_title']; ?> Atom 1.0 feed" href="<?php echo ROOT_PATH; ?>api/feed.php" />*/
        lovd_includeJS('inc-js-openwindow.php', 1);
        lovd_includeJS('inc-js-toggle-visibility.js', 1); // Used on forms and variant overviews for small info tables.
        lovd_includeJS('lib/jQuery/jquery.min.js', 1);
        lovd_includeJS('lib/jQuery/jquery-ui.core.min.js', 1);

        if (!$bFull) {
?>
</HEAD>

<BODY style="margin : 10px;">

<TABLE border="0" cellpadding="0" cellspacing="0" width="100%">
  <TR>
    <TD>










<?php
            return true;
        }
?>

  <SCRIPT type="text/javascript">
    <!--

<?php
        if (!empty($_SESSION['currdb'])) {
            // A quick way to switch genes, regardless of on which page you are.
            // FIXME; Currently we don't support "=GENE" matching (for instance, on the disease tab) because changing that value will not trigger a change in CURRDB... Yet.
            //$sGeneSwitchURL = preg_replace('/(\/|=)' . preg_quote($_SESSION['currdb'], '/') . '\b/', "$1{{GENE}}", $_SERVER['REQUEST_URI']);
            $sGeneSwitchURL = preg_replace('/(\/)' . preg_quote($_SESSION['currdb'], '/') . '\b/', "$1{{GENE}}", $_SERVER['REQUEST_URI']);
            print('    var sURL = "' . $sGeneSwitchURL . '";' . "\n" .
                  '    function lovd_switchGeneInline () {' . "\n" .
            // FIXME; It is very very difficult to keep the hash, it should be selective since otherwise you might be loading the EXACT SAME VL, BUT ON A DIFFERENT PAGE (viewing variants belonging to gene X, on a page that says you're looking at gene Y).
//              '      var sForm = \'<FORM action="" id="SelectGeneDBInline" method="get" style="margin : 0px;" onsubmit="document.location.href=(sURL.replace(\\\'{{GENE}}\\\', $(this).children(\\\'select\\\').val()) + (!window.location.hash? \\\'\\\' : window.location.hash)); return false;">' .
                  '      var sForm = \'<FORM action="" id="SelectGeneDBInline" method="get" style="margin : 0px;" onsubmit="document.location.href=(sURL.replace(\\\'{{GENE}}\\\', $(this).children(\\\'select\\\').val())); return false;">' .
                                      '<SELECT name="select_db" onchange="$(this).parent().submit();">');
            $qGenes = $_DB->query('SELECT id, CONCAT(id, " (", name, ")") AS name FROM ' . TABLE_GENES . ' ORDER BY id');
            while ($zGene = $qGenes->fetchAssoc()) {
                // This will shorten the gene names nicely, to prevent long gene names from messing up the form.
                $zGene['name'] = lovd_shortenString($zGene['name'], 75);
                print('<OPTION value="' . $zGene['id'] . '"' . ($_SESSION['currdb'] == $zGene['id']? ' selected' : '') . '>' . addslashes($zGene['name']) . '</OPTION>');
            }
            print('</SELECT>' .
                  '<INPUT type="submit" value="Switch"></FORM>\';' . "\n" .
                  '      document.getElementById(\'gene_name\').innerHTML=sForm;' . "\n" .
                  '    }' . "\n");
        }
        ?>

    //-->
  </SCRIPT>
<?php
        lovd_includeJS('lib/jeegoocontext/jquery.jeegoocontext.min.js', 1);
?>
  <LINK rel="stylesheet" type="text/css" href="lib/jeegoocontext/style.css">
  <LINK rel="stylesheet" type="text/css" href="lib/jQuery/css/cupertino/jquery-ui.custom.css">
</HEAD>

<BODY style="margin : 0px;">

<TABLE border="0" cellpadding="0" cellspacing="0" width="100%"><TR><TD>

<TABLE border="0" cellpadding="0" cellspacing="0" width="100%" class="logo">
  <TR>
<?php
        if (!is_readable(ROOT_PATH . $_CONF['logo_uri'])) {
            $_CONF['logo_uri'] = 'gfx/LOVD_logo130x50.jpg';
        }
        $aImage = @getimagesize(ROOT_PATH . $_CONF['logo_uri']);
        if (!is_array($aImage)) {
            $aImage = array('130', '50', '', 'width="130" heigth="50"');
        }
        list($nWidth, $nHeight, $sType, $sSize) = $aImage;
        print('    <TD valign="top" width="' . ($nWidth + 20) . '" height="' . ($nHeight + 5) . '">' . "\n" .
              '      <IMG src="' . $_CONF['logo_uri'] . '" alt="LOVD - Leiden Open Variation Database" ' . $sSize . '>' . "\n" .
              '    </TD>' . "\n");

        $sCurrSymbol = $sCurrGene = '';
/*
        // FIXME; how will we handle this?
        // During submission, show the gene we're submitting to instead of the currently selected gene.
        if (lovd_getProjectFile() == '/submit.php' && !empty($_POST['gene']) && $_POST['gene'] != $_SESSION['currdb']) {
            // Fetch gene's info from db... we don't have it anywhere yet.
            list($sCurrSymbol, $sCurrGene) = $_DB->query('SELECT id, gene FROM ' . TABLE_DBS . ' WHERE id = ?', array($_POST['gene']))->fetchRow();
        } else*/if (!empty($_SESSION['currdb'])) {
            // Just use currently selected database.
            $sCurrSymbol = $_SESSION['currdb'];
            $sCurrGene = $_SETT['currdb']['name'];
        }

        print('    <TD valign="top" style="padding-top : 2px;">' . "\n" .
              '      <H2 style="margin-bottom : 2px;">' . $_CONF['system_title'] . '</H2>' . "\n" .
              (!($sCurrSymbol && $sCurrGene)? '' : '      <H5 id="gene_name">' . $sCurrGene . ' (' . $sCurrSymbol . ')' .
              (strpos($sGeneSwitchURL, '{{GENE}}') === false? '' : '&nbsp;<A href="#" onclick="lovd_switchGeneInline(); return false;"><IMG src="gfx/lovd_genes_switch_inline.png" width="23" height="23" alt="Switch gene" title="Switch gene database" align="top"></A>') .
              '</H5>' . "\n") .
              '    </TD>' . "\n" .
              '    <TD valign="top" align="right" style="padding-right : 5px; padding-top : 2px;">' . "\n" .
              '      LOVD v.' . $_STAT['tree'] . ' Build ' . $_STAT['build'] .
              (!defined('NOT_INSTALLED')? ' [ <A href="status">Current LOVD status</A> ]' : '') .
              '<BR>' . "\n");
        if (!(ROOT_PATH == '../' || defined('NOT_INSTALLED'))) {
            if ($_AUTH) {
                print('      <B>Welcome, ' . $_AUTH['name'] . '</B><BR>' . "\n" .
                      '      <A href="users/' . $_AUTH['id'] . '"><B>Your account</B></A> | ' . (false && $_AUTH['level'] == LEVEL_SUBMITTER && $_CONF['allow_submitter_mods']? '<A href="variants?search_created_by=' . $_AUTH['id'] . '"><B>Your submissions</B></A> | ' : '') . (!empty($_AUTH['saved_work']['submissions']['individual']) || !empty($_AUTH['saved_work']['submissions']['screening'])? '<A href="users/' . $_AUTH['id'] . '?submissions"><B>Unfinished submissions</B></A> | ' : '') . '<A href="logout"><B>Log out</B></A>' . "\n");
            } else {
                print('      <A href="users?register"><B>Register as submitter</B></A> | <A href="login"><B>Log in</B></A>' . "\n");
            }
        }

        print('    </TD>' . "\n" .
              '  </TR>' . "\n");

        // Add curator info to header.
        if ($sCurrSymbol && $sCurrGene) {
            $sCurators = '';
            $aCurators = $_DB->query('SELECT u.name, u.email FROM ' . TABLE_USERS . ' AS u LEFT JOIN ' . TABLE_CURATES . ' AS u2g ON (u.id = u2g.userid) WHERE u2g.geneid = ? AND u2g.allow_edit = 1 AND u2g.show_order > 0 ORDER BY u2g.show_order ASC, u.level DESC, u.name ASC', array($sCurrSymbol))->fetchAllAssoc();
            $nCurators = count($aCurators);
            foreach ($aCurators as $i => $z) {
                $i ++;
                $sCurators .= ($sCurators? ($i == $nCurators? ' and ' : ', ') : '') . '<A href="mailto:' . str_replace(array("\r\n", "\r", "\n"), ', ', trim($z['email'])) . '">' . $z['name'] . '</A>';
            }

            if ($sCurators) {
                print('  <TR>' . "\n" .
                      '    <TD width="150">&nbsp;</TD>' . "\n" .
                      '    <TD valign="top" colspan="2" style="padding-bottom : 2px;"><B>Curator' . ($nCurators > 1? 's' : '') . ': ' . $sCurators . '</B></TD>' . "\n" .
                      '  </TR>' . "\n");
            }
        }

        print('</TABLE>' . "\n\n");



        // Build menu tabs...
        print('<TABLE border="0" cellpadding="0" cellspacing="0" width="100%" class="logo"' . (count($this->aMenu)? '' : ' style="border-bottom : 2px solid #000000;"') . '>' . "\n" .
              '  <TR>' . "\n" .
              '    <TD align="left" style="background : url(\'gfx/tab_fill.png\'); background-repeat : repeat-x;">' . "\n");

        // Loop menu.
        $n         = 0;
        $bSel      = false;
        $bPrevSel  = false;
        $aMenus    = array();
        $bCurator  = ($_AUTH && (count($_AUTH['curates']) || $_AUTH['level'] > LEVEL_CURATOR)); // We can't check LEVEL_CURATOR since it may not be set.
        foreach ($this->aMenu as $sPrefix => $Title) {
            // Arrays (children links of parent tabs) can only be processed if we still have the $sFile from the previous run.
            if (is_array($Title)) {
                if (empty($sFile)) {
                    continue;
                }
                $sPrefix = substr($sFile, 4); // Remove 'tab_'.

                // Menu will be built in an UL, that will be transformed into a dropdown menu by using the Jeegocontext script by www.planitworks.nl.
                $sUL = '<UL id="menu_' . $sFile . '" class="jeegoocontext">' . "\n";

                $bHR = false;
                foreach ($Title as $sURL => $aItem) {
                    if (!is_array($aItem)) {
                        if ($aItem == 'hr') {
                            $bHR = true;
                        }
                        continue;
                    }
                    list($sIMG, $sName, $nRequiredLevel) = $aItem;
                    $bDisabled = false;
                    if ($nRequiredLevel && (($nRequiredLevel == LEVEL_CURATOR && !$bCurator) || ($nRequiredLevel != LEVEL_CURATOR && $nRequiredLevel > $_AUTH['level']))) {
                        $bDisabled = true;
                    } else {
                        if (!$sURL) {
                            // Default action of default page.
                            $sURL = $sPrefix;
                        } elseif ($sURL{0} == '/') {
                            // Direct URL.
                            $sURL = substr($sURL, 1);
                        } else {
                            // Action given.
                            $sURL = $sPrefix . '?' . $sURL;
                        }
                    }

                    if (!$bDisabled) {
                        // IE (who else) refuses to respect the BASE href tag when using JS. So we have no other option than to include the full path here.
                        // Not using the "separator" class from the original code, since it's not compatible to our changes.
                        $sUL .= ($bHR? '  <LI class="hr disabled"><HR></LI>' . "\n" : '') .
                                '  <LI' . (!$sIMG? '' : ' class="icon"') . '><A href="' . lovd_getInstallURL(false) . $sURL . '">' .
                                (!$sIMG? '' : '<SPAN class="icon" style="background-image: url(gfx/' . $sIMG . ');"></SPAN>') . $sName .
                                '</A></LI>' . "\n";
                        $bHR = false;
                    }
// class disabled, disabled. Nu gewoon maar even weggehaald.
//                    $sUL .= '  <LI' . ($bDisabled? ' class="disabled">' : (!$sIMG? '' : ' class="icon"') . '><A href="' . $sURL . '">') .
//                        (!$sIMG? '' : '<SPAN class="icon" style="background-image: url(gfx/' . $sIMG . ');"></SPAN>') . $sName .
//                        ($bDisabled? '' : '</A>') . '</LI>' . "\n";
                }
                $sUL .= '</UL>' . "\n";

                $aMenus[$sFile] = $sUL;
                continue;
            }



            // Determine if we're the current tab.
            $bSel = (substr(lovd_getProjectFile(), 1, strrpos(lovd_getProjectFile(), '.') - 1) == $sPrefix);
            // Auch! Hard coded exception!
            if (!$bSel && defined('TAB_SELECTED') && TAB_SELECTED == $sPrefix) { $bSel = true; }
            $sFile = 'tab_' . $sPrefix;

            // Print transition.
            print('      <IMG src="gfx/tab_' . (!$n? '0' : ($bPrevSel? 'F' : 'B')) . ($bSel? 'F' : 'B') . '.png" alt="" width="25" height="25" align="left">' . "\n");

            // Get header info.
            $sFileName = 'gfx/' . $sFile . '_' . ($bSel? 'F' : 'B') . '.png';
            $aImage = @getimagesize($sFileName);
            $sSize = $aImage[3];

            // Print header.
            $sURL = $sPrefix;
            // If a gene has been selected, some of the tabs get different default URLs.
            if ($_SESSION['currdb']) {
                if (in_array($sPrefix, array('configuration', 'genes', 'transcripts', 'variants', 'screenings', 'individuals'))) {
                    $sURL = $sPrefix . '/' . $_SESSION['currdb'];
                } elseif ($sPrefix == 'diseases') {
                    $sURL = $sPrefix . '?search_genes_=' . $_SESSION['currdb'];
                }
            }
            print('      <A href="' . $sURL . '"><IMG src="' . $sFileName . '" alt="' . $Title . '" id="' . $sFile . '" ' . $sSize . ' align="left"></A>' . "\n");

            $bPrevSel = $bSel;
            $n ++;
        }

        // If we've had tabs at all, close the transition.
        if (count($this->aMenu)) {
            print('      <IMG src="gfx/tab_' . ($bPrevSel? 'F' : 'B') . '0.png" alt="" width="25" height="25" align="left">' . "\n");
        }
        // Close menu table.
        print('    </TD>' . "\n" .
              '  </TR>' . "\n" .
              '</TABLE>' . "\n\n");

        // Attach dropdown menus.
        print('<!-- Start drop down menu definitions -->' . "\n");
        foreach ($aMenus as $sUL) {
            print($sUL . "\n");
        }
        print('
<SCRIPT type="text/javascript">
  $(function(){
    var aMenuOptions = {
        widthOverflowOffset: 0,
        heightOverflowOffset: 1,' .
//                submenuLeftOffset: -4,
//                submenuTopOffset: -2,
'
        startLeftOffset: -20,
        event: "mouseover",
        openBelowContext: true,
        autoHide: true,
        delay: 100,
        onSelect: function(e, context){
            if($(this).hasClass("disabled"))
            {
                return false;
            } else {
                window.location = $(this).find("a").attr("href");
                return false;
            }
        }
    };' . "\n");

        foreach (array_keys($aMenus) as $sTabID) {
            print('    $(\'#' . $sTabID . '\').jeegoocontext(\'menu_' . $sTabID . '\', aMenuOptions);' . "\n");
        }
        print('  });
</SCRIPT>' . "\n" .
'<!-- End drop down menu definitions -->' . "\n");
?>



<DIV style="padding : 0px 10px;">
<TABLE border="0" cellpadding="0" cellspacing="0" width="100%">
  <TR>
    <TD style="padding-top : 10px;">







<?php
        return true;
    }





    function printTitle ($sTitle = '', $sStyle = 'H2')
    {
        // Check which title we're supposed to print, and forward.

        if (!$sTitle && defined('PAGE_TITLE')) {
            $sTitle = PAGE_TITLE;
        }

        if (in_array($sTitle, $this->aTitlesPrinted)) {
            return false;
        } else {
            $this->aTitlesPrinted[] = $sTitle;
        }

        switch (FORMAT) {
            case 'text/plain':
                return false;
            case 'text/html':
            default:
                return $this->printTitleHTML($sTitle, $sStyle);
        }
        return false;
    }





    function printTitleHTML ($sTitle, $sStyle = 'H2')
    {
        // Prints the page's title header.

        $aStyles = array('H2', 'H3', 'H4');
        if (!in_array($sStyle, $aStyles)) {
            $sStyle = $aStyles[0];
        }
        print('      <' . $sStyle . ' class="LOVD">' . $sTitle . '</' . $sStyle . '>' . "\n\n");
    }
}
?>
