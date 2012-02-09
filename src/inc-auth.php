<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2009-10-23
 * Modified    : 2012-02-09
 * For LOVD    : 3.0-beta-03
 *
 * Copyright   : 2004-2012 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
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

// If logged in, load account data.
$_AUTH = false;

if (isset($_SESSION['auth']) && is_array($_SESSION['auth'])) {
    $_SESSION['auth'] = @$_DB->query('SELECT * FROM ' . TABLE_USERS . ' WHERE username = ? AND password = ? AND login_attempts < 3', array($_SESSION['auth']['username'], $_SESSION['auth']['password']), false)->fetchAssoc();
    if (is_array($_SESSION['auth'])) {
        $_AUTH = & $_SESSION['auth'];

        // Load curated DBs.
        $_AUTH['curates']      = array();
        $_AUTH['collaborates'] = array();
        if ($_AUTH['level'] < LEVEL_MANAGER) {
            $q = $_DB->query('SELECT geneid, allow_edit FROM ' . TABLE_CURATES . ' WHERE userid = ?', array($_AUTH['id']));
            while ($r = $q->fetchRow()) {
                if ($r[1]) {
                    $_AUTH['curates'][] = $r[0];
                } else {
                    $_AUTH['collaborates'][] = $r[0];
                }
            }
        }
    }
}

// IP based blocking.
if ($_AUTH && $_AUTH['allowed_ip']) {
    if (!lovd_validateIP($_AUTH['allowed_ip'], $_SERVER['REMOTE_ADDR'])) {
        // Log the user out.
        session_destroy();
        $_AUTH = false;

        if (!defined('_INC_TOP_INCLUDED_')) {
            if (is_readable('inc-top.php')) {
                require 'inc-top.php';
            } else {
                require ROOT_PATH . 'inc-top.php';
            }
        }

        lovd_showInfoTable('Your current IP address does not allow you access using this username.', 'stop');

        if (defined('_INC_TOP_INCLUDED_')) {
            if (is_readable('inc-bot.php')) {
                require 'inc-bot.php';
            } else {
                require ROOT_PATH . 'inc-bot.php';
            }
        } elseif (defined('_INC_TOP_CLEAN_INCLUDED_')) {
            if (is_readable('inc-bot-clean.php')) {
                require 'inc-bot-clean.php';
            } else {
                require ROOT_PATH . 'inc-bot-clean.php';
            }
        }
        exit;
    }
}




if (!$_AUTH) {
    // We need to check for cookies, so set whatever and check whether it's there later...
    if (!isset($_COOKIE['lovd_cookie_check'])) {
        setcookie('lovd_cookie_check', 'OK');
    }
}
?>
