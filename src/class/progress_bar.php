<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-09-10
 * Modified    : 2012-09-21
 * For LOVD    : 3.0-beta-09
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





class ProgressBar {
    // This class creates a progress bar that can be controlled in various ways.
    var $sID = '';
    var $nCurrentPercentage = 0;




    function ProgressBar ($sID = '', $sMessageInit = '', $sMessageDone = '')
    {
        // Default constructor.

        if (!is_string($sID)) {
            $sID = '';
        }
        if (!is_string($sMessageInit)) {
            $sMessageInit = '';
        }
        if (!is_string($sMessageDone)) {
            $sMessageDone = '';
        }
        $this->sID = $sID;

        print('      <TABLE border="0" cellpadding="0" cellspacing="0" width="440" id="lovd_' . $this->sID . '_progress_table" >' . "\n" .
              '        <TR>' . "\n" .
              '          <TD width="400" style="border : 1px solid black; height : 15px;">' . "\n" .
              '            <IMG src="gfx/trans.png" alt="" title="0%" width="0%" height="15" id="lovd_' . $this->sID . '_progress_bar" style="background : #224488;"></TD>' . "\n" .
              '          <TD width="40" align="right" id="lovd_' . $this->sID . '_progress_value">0%</TD></TR></TABLE>' . "\n\n" .
              '      <DIV id="lovd_' . $this->sID . '_progress_message" style="margin-top : 0px;">' . "\n" .
              '        ' . $sMessageInit . "\n" .
              '      </DIV><BR>' . "\n\n\n" .
              '      <DIV id="lovd_' . $this->sID . '_progress_message_done" style="visibility : hidden;">' . "\n" .
              '        ' . $sMessageDone . "\n" .
              '      </DIV>' . "\n\n" .
              '      <SCRIPT type="text/javascript">' . "\n" .
              '        var oPB_' . $this->sID . ' = document.getElementById(\'lovd_' . $this->sID . '_progress_bar\');' . "\n" .
              '        var oPB_' . $this->sID . '_table = document.getElementById(\'lovd_' . $this->sID . '_progress_table\');' . "\n" .
              '        var oPB_' . $this->sID . '_value = document.getElementById(\'lovd_' . $this->sID . '_progress_value\');' . "\n" .
              '        var oPB_' . $this->sID . '_message = document.getElementById(\'lovd_' . $this->sID . '_progress_message\');' . "\n" .
              '        var oPB_' . $this->sID . '_message_done = document.getElementById(\'lovd_' . $this->sID . '_progress_message_done\');' . "\n" .
              '      </SCRIPT>' . "\n\n\n");
              flush();
              @ob_end_flush(); // Can generate errors on the screen if no buffer found.
    }





    function appendMessage ($sMessage, $sType = '')
    {
        // Appends the message text in the specified <DIV>.
        return $this->setMessage($sMessage, $sType, true);
    }





    function redirectTo ($sURL, $nTime = 1)
    {
        // Sends the JS necessary to redirect the viewer to another URL.
        // When using this class, PHP's header() function does not work anymore because we have output already.

        // Most likely, this function is available, but we can't be sure.
        if (function_exists('lovd_matchURL') && !lovd_matchURL($sURL, true)) {
            return false;
        }
        if (!is_numeric($nTime)) {
            $nTime = 1;
        }
        $nTime *= 1000; // JS works in miliseconds, not seconds.

        print('<SCRIPT type="text/javascript">setTimeout("window.location.href=\'' . str_replace('\'', '\\\'', $sURL) . '\'", ' . $nTime . ');</SCRIPT>' . "\n");
        flush();
        return true;
    }





    function remove ()
    {
        // Hides the progress bar itself, not the messages.

        print('<SCRIPT type="text/javascript">$(oPB_' . $this->sID . '_table).hide(); $(oPB_' . $this->sID . '_message).hide(); $(oPB_' . $this->sID . '_message_done).hide(); </SCRIPT>' . "\n");
        flush();
        return true;
    }





    function setMessage ($sMessage, $sType = '', $bAppend = false)
    {
        // Sets the message text in the specified <DIV>.

        if (!is_string($sMessage)) {
            $sMessage = '';
        }
        if ($sType != 'done') {
            $sType = '';
        }

        print('<SCRIPT type="text/javascript">oPB_' . $this->sID . '_message' . (!$sType? '' : '_done') . '.innerHTML' . ($bAppend? '+' : '') . '=\'' . str_replace(array('\'', "\r", "\n"), array('\\\'', '', '\n'), $sMessage) . '\';</SCRIPT>' . "\n");
        flush();
        return true;
    }





    function setMessageVisibility ($sType, $bVisible)
    {
        // Shows or hides the message visibility.

        if ($sType != 'done') {
            $sType = '';
        }

        print('<SCRIPT type="text/javascript">oPB_' . $this->sID . '_message' . (!$sType? '' : '_done') . '.style.visibility=\'' . ($bVisible? 'visible' : 'hidden') . '\';</SCRIPT>' . "\n");
        flush();
        return true;
    }





    function setProgress ($nPercentage)
    {
        // Sets the progress (0-100) of the bar.

        if (!is_numeric($nPercentage) || $nPercentage < 0) {
            $nPercentage = 0;
        } elseif ($nPercentage > 100) {
            $nPercentage = 100;
        } else {
            $nPercentage = round($nPercentage);
        }

        if ($this->nCurrentPercentage != $nPercentage) {
            // Only 'update' the percentage if it actually changes...
            $this->nCurrentPercentage = $nPercentage;
            print('<SCRIPT type="text/javascript">oPB_' . $this->sID . '.style.width = \'' . $nPercentage . '%\'; oPB_' . $this->sID . '_value.innerHTML = \'' . $nPercentage . '%\'; </SCRIPT>' . "\n");
            flush();
        }
        return $nPercentage;
    }
}
?>
