<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2011-11-08
 * Modified    : 2012-06-22
 * For LOVD    : 3.0-beta-06
 *
 * Copyright   : 2004-2012 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
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

header('Content-type: text/javascript; charset=UTF-8');

define('AJAX_FALSE', '0');
define('AJAX_TRUE', '1');
define('AJAX_NO_AUTH', '8');
define('AJAX_DATA_ERROR', '9');
?>

function lovd_checkHGVS () {
    // Function that is being called everytime a change has been made to a DNA field. This will run the Mutalyzer checkHGVS module and will return the response to the user.
    var oVariantDNA = $(this);
    $(oVariantDNA).removeClass();
    $(oVariantDNA).siblings('img:first').attr({
        src: 'gfx/lovd_loading.gif',
        alt: 'Loading...',
        title: 'Loading...',
        class: '',
        onmouseover: '',
        onmouseout: ''
    }).show();
    // Grab the corresponding protein description field if it exists.
    var oProtein = $(oVariantDNA).parent().parent().siblings().find('input[name="' + $(oVariantDNA).attr('name').substring(0,5) + '_VariantOnTranscript/Protein"]');
    // Add a transparent placeholder for the indicator at the protein field, so that the form will not shift when it is added.
    $(oProtein).siblings('img:first').removeClass().attr('src', 'gfx/trans.png');
    if (oVariantDNA.attr('name') == 'VariantOnGenome/DNA') {
        var sVariantNotation = 'g:' + oVariantDNA.val(); // The actual chromosome is not important, it's just the variant syntax that matters here.
    } else {
        var sVariantNotation = 'c:' + oVariantDNA.val(); // The actual transcript is not important, it's just the variant syntax that matters here.
    }
    $.get('ajax/check_hgvs.php', { variant: sVariantNotation },
        function(sData) {
            if (sData != '<?php echo AJAX_TRUE; ?>') {
                // Either Mutalyzer says No, our regexp didn't find a c. or g. at the beginning or user lost $_AUTH.
                $(oVariantDNA).siblings('img:first').attr({
                    src: 'gfx/cross.png',
                    alt: 'Not a valid HGVS syntax!',
                    title: 'Not a valid HGVS syntax!'
                }).show();
                if (!$.isEmptyObject(aTranscripts)) {
                    $(oVariantDNA).siblings('button:eq(0)').hide();
                    $(oProtein).siblings('button:eq(0)').hide();
                }

            } else {
                $(oVariantDNA).siblings('img:first').attr({
                    src: 'gfx/check.png',
                    alt: 'Valid HGVS syntax!',
                    title: 'Valid HGVS syntax!'
                }).show();
                // Check if the variant description is a c.? or a g.?. If it is, then do not let the user map the variant.
                if (oVariantDNA.val().substring(1,3) != '.?' && !$.isEmptyObject(aTranscripts)) {
                    $(oVariantDNA).siblings('button:eq(0)').show();
                    $(oProtein).siblings('button:eq(0)').show();
                } else if (oVariantDNA.val().substring(1,3) == '.?') {
                    $(oVariantDNA).siblings('button:eq(0)').hide();
                    $(oProtein).siblings('button:eq(0)').hide();
                }
            }
        });
    return false;
}

function lovd_convertPosition (oElement) {
    // Function that can map a variant to other transcripts or the genome.
    var oThisDNA = $(oElement).siblings('input:first');
    var oAllDNA = $('input[name$="_VariantOnTranscript/DNA"]');
    $(oAllDNA).removeClass().siblings('img:first').attr({
        src: 'gfx/trans.png',
        alt: '',
        title: '',
        class: '',
        onmouseover: '',
        onmouseout: ''
    }).show();
    var oAllProteins = $('input[name$="_VariantOnTranscript/Protein"]');
    $(oAllProteins).siblings('img:first').attr({
        src: 'gfx/trans.png',
        alt: '',
        title: '',
    }).show();
    $(oThisDNA).siblings('img:first').attr({
        src: 'gfx/lovd_loading.gif',
        alt: 'Loading...',
        title: 'Loading...',
        class: '',
        onmouseover: '',
        onmouseout: ''
    }).show();
    if (oThisDNA.attr('name') == 'VariantOnGenome/DNA') {
        // This function was called from the genomic variant, so build a list of genes and prepare the variant accordingly for mutalyzer.
        var sVariantNotation = 'chr<?php echo $_GET['chromosome']; ?>:' + oThisDNA.val();
        var aGenes = [];
        for (nTranscriptID in aTranscripts) {
            if ($.inArray(aTranscripts[nTranscriptID][1], aGenes) == -1) { 
                aGenes.push(aTranscripts[nTranscriptID][1]);
            }
        }
    } else {
        // This function was called from a transcript variant, so prepare the variant accordingly for mutalyzer.
        var nTranscriptID = oThisDNA.attr('name').substring(0,5);
        var sVariantNotation = aTranscripts[nTranscriptID][0] + ':' + oThisDNA.val();
        // This value will not be used by mutalyzer for mapping to the genome, but we
        // need to fill something in for the call.
        var aGenes = [ aTranscripts[nTranscriptID][1] ];
    }
    for (i in aGenes) {
        // Run the following code for each gene the variant is mapped to, since Mutalyzer can only map per gene.
        var sGene = aGenes[i];
        $.get('ajax/convert_position.php', { variant: sVariantNotation, gene: sGene },
            function(sData) {
                if (sData != '<?php echo AJAX_DATA_ERROR; ?>' && sData != '<?php echo AJAX_FALSE; ?>' && sData != '<?php echo AJAX_NO_AUTH; ?>') {
                    if (oThisDNA.attr('name') == 'VariantOnGenome/DNA') {
                        // This function was called from the genomic variant, so fill in the return values from mutalyzer in the transcript DNA fields.
                        aVariants = sData.split(';');
                        var nVariants = aVariants.length;
                        for (i = 0; i < nVariants; i++) {
                            var aVariant = /^(N[RM]_\d{6,9}\.\d{1,2}):(c\..+)$/.exec(aVariants[i]);
                            if (aVariant != null) {
                                var oInput = $('#variantForm input[id_ncbi="' + aVariant[1] + '"]');
                                if (oInput[0] != undefined) {
                                    // If the transcript returned by mutalyzer is present in the form, fill in the respons from mutalyzer.  
                                    oInput.attr('value', aVariant[2]);
                                    oInput.siblings('img:first').attr({
                                        src: 'gfx/check.png',
                                        alt: 'Valid HGVS syntax!',
                                        title: 'Valid HGVS syntax!'
                                    }).show();
                                    // Hide the "Map variant" button, so that the button cannot be pressed again. It has finished anyway and there
                                    // is no use to run this function again when the DNA field hasn't changed.
                                    oInput.siblings('button:eq(0)').hide();
                                    // Grab the corresponding protein description field if it exists.
                                    var oProtein = $(oInput).parent().parent().siblings().find('input[name="' + $(oInput).attr('name').substring(0,5) + '_VariantOnTranscript/Protein"]');
                                    if (!oInput[0].disabled) {
                                        // Transcript is not disabled, so let mutalyzer predict the protein description.
                                        lovd_getProteinChange(oProtein);
                                    } else {
                                        // Transcript is disabled, empty the protein field.
                                        oProtein.attr('value', '');
                                    }
                                }
                            }
                        }
                        $(oThisDNA).siblings('button:eq(0)').hide();
                    } else {
                        // This function was called from a transcript variant, so fill in the return value from mutalyzer in the genomic DNA field.
                        var aVariant = /:(g\..+)$/.exec(sData);
                        if (aVariant != null) {
                            var oInput = $('#variantForm input[name="VariantOnGenome/DNA"]');
                            oInput.attr('value', aVariant[1]);
                            oInput.siblings('img:first').attr({
                                src: 'gfx/check.png',
                                alt: 'Valid HGVS syntax!',
                                title: 'Valid HGVS syntax!'
                            }).show();
                            // Call this function again, but with the new genomic information. This way, the variant will be mapped from the genome to all transcripts.
                            lovd_convertPosition(oInput.siblings('button:eq(0)'));
                        }
                    }
                    $(oThisDNA).siblings('img:first').attr({
                        src: 'gfx/check.png',
                        alt: 'Valid HGVS syntax!',
                        title: 'Valid HGVS syntax!'
                    }).show();
                } else {
                    // Either Mutalyzer says No, our regexp didn't match with the full variant notation or user lost $_AUTH.
                    $(oThisDNA).siblings('img:first').attr({
                        src: 'gfx/cross.png',
                        alt: 'Error during mapping!',
                        title: 'Error during mapping!'
                    }).show();
                    $(oThisDNA).siblings('button:eq(0)').hide();
                }
        });
    }
    return false;
}

function lovd_getProteinChange (oElement) {
    // Function that can predict a protein description of a variant based on a transcript DNA field.
    var oThisProtein = $(oElement).parent().find('input:first');
    $(oThisProtein).attr('value', '').removeAttr('style');
    $(oThisProtein).removeClass();
    $(oThisProtein).siblings('img:first').attr({
        src: 'gfx/lovd_loading.gif',
        alt: 'Loading...',
        title: 'Loading...'
    }).show();
    // Collect the corresponding transcript variant information, because Mutalyzer needs it to make a prediction.
    var nTranscriptID = $(oThisProtein).attr('name').substring(0,5);
    var oThisDNA = $(oElement).parent().parent().siblings().find('input[name="' + nTranscriptID + '_VariantOnTranscript/DNA"]');
    var sVariantNotation = aUDrefseqs[aTranscripts[nTranscriptID][1]] + '(' + aTranscripts[nTranscriptID][1] + '_v' + aTranscripts[nTranscriptID][2] + '):' + $(oThisDNA).val();

    $.get('ajax/check_variant.php', { variant: sVariantNotation, gene: aTranscripts[nTranscriptID][1] },
            function(sData) {
                if (sData.length == 1) {
                    // Either Mutalyzer says No, our regexp didn't match with the full variant notation or user lost $_AUTH.
                    if (sData == '<?php echo AJAX_NO_AUTH; ?>') {
                        alert('Lost your session!');
                    }
                    if (!oThisProtein.attr('disabled')) {
                        $(oThisProtein).siblings('img:first').attr({
                            src: 'gfx/cross.png',
                            alt: 'Error on mutalyzer request!\nError code: ' + sData,
                            title: 'Error on mutalyzer request!\nError code: ' + sData
                        }).show();
                    }
                } else {
                    var aData = sData.split(';;'); // aData[0] = errors, aData[1] = actual reply.
                    var aError = aData[0].split(':');
                    var sErrorCode = aError[0];
                    aError.splice(0,1);
                    var sErrorMessage = aError.join(':');

                    if (sErrorCode == 'ERANGE') {
                        // Ignore 'ERANGE' as an actual error, because we can always interpret this as p.(=), p.? or p.0.
                        sErrorCode = 'WRANGE';
                        sErrorMessage = '';
                        var aVariantRange = $(oThisDNA).val().split('_');
                        // Check what the variant looks like and act accordingly.
                        if (aVariantRange.length == 2 && /-u\d+/.exec(aVariantRange[0]) != null && /-u\d+/.exec(aVariantRange[1]) != null) {
                            // Variant has 2 positions. Variant has both the start and end positions upstream of the transcript, we can assume that the product will not be affected.
                            sPredict = 'p.(=)';
                        } else if (aVariantRange.length == 2 && /-u\d+/.exec(aVariantRange[0]) != null && /\+d\d+/.exec(aVariantRange[1]) != null) {
                            // Variant has 2 positions. Variant has an upstream start position and a downstream end position, we can assume that the product will not be expressed.
                            sPredict = 'p.0';
                        } else if (aVariantRange.length == 2 && /\+d\d+/.exec(aVariantRange[0]) != null && /\+d\d+/.exec(aVariantRange[1]) != null) {
                            // Variant has 2 positions. Variant has both the start and end positions downstream of the transcript, we can assume that the product will not be affected.
                            sPredict = 'p.(=)';
                        } else if (aVariantRange.length == 1 && (/-u\d+/.exec(aVariantRange[0]) != null || /\+d\d+/.exec(aVariantRange[0]) != null)) {
                            // Variant has 1 position and is either upstream or downstream from the transcript, we can assume that the product will not be affected.
                            sPredict = 'p.(=)';
                        } else {
                            // The one of the positions of the variant falls within the transcript, so we can not make any assumptions based on that.
                            sPredict = 'p.?';
                        }
                        // Fill in our assumption in aData to forge that this information came from Mutalyzer.
                        aData[1] = aUDrefseqs[aTranscripts[nTranscriptID][1]] + '(' + aTranscripts[nTranscriptID][1] + '_i' + aTranscripts[nTranscriptID][2] + '):' + sPredict;
                    }
                    if (sErrorCode.substring(0, 1) == 'E' || !aData[1]) {
                        if (!oThisProtein.attr('disabled')) {
                            // Mutalyzer returned an error so we add the err class to make the field red. We Also add an image with a tooltip that shows the error.
                            $(oThisDNA).attr('class', 'err');
                            $(oThisDNA).siblings('img:first').attr({
                                src: 'gfx/lovd_form_warning.png',
                                alt: '',
                                title : '',
                                class: 'help',
                                onmouseover : 'lovd_showToolTip(\'' + escape(sErrorMessage) + '\');',
                                onmouseout: 'lovd_hideToolTip();'
                            }).show();
                            $(oThisProtein).siblings('img:first').attr({
                                src: 'gfx/cross.png',
                                alt: 'Encountered an error during protein prediction!',
                                title: 'Encountered an error during protein prediction!'
                            }).show();
                            $(oThisProtein).siblings('button:eq(0)').hide();
                        }
                    } else {
                        var aProteinDescriptions = aData[1].split(';');
                        $(aProteinDescriptions).each( function(index, value) {
                            if (value.replace(/UD_\d+\(/, '').replace(/\):p\..+/, '') == aTranscripts[nTranscriptID][1] + '_i' + aTranscripts[nTranscriptID][2] && !oThisProtein.attr('disabled')) {
                                if (sErrorMessage && sErrorCode != 'WSPLICE') {
                                    // Mutalyzer returned a warning so we add the warn class to make the field yellow. We Also add an image with a tooltip that shows the warning.
                                    // The exception here is WSPLICE, since this only says something about another transcript.
                                    $(oThisDNA).attr('class', 'warn');
                                    $(oThisDNA).siblings('img:first').attr({
                                        src: 'gfx/lovd_form_information.png',
                                        alt: '',
                                        title : '',
                                        class: 'help',
                                        onmouseover : 'lovd_showToolTip(\'' + escape(sErrorMessage) + '\');',
                                        onmouseout: 'lovd_hideToolTip();'
                                    }).show();
                                } else {
                                    $(oThisDNA).siblings('img:first').attr({
                                        alt: 'HGVS compliant!',
                                        title : 'HGVS compliant!'
                                    }).show();
                                }
                                if (sErrorCode != 'WSPLICESELECTED') {
                                    // Fill in the predicted value in the corresponding protein field.
                                    $(oThisProtein).attr('value', value.replace(/UD_\d+\(.+\):/, ''));
                                } else {
                                    // WSPLICESELECTED, so we don't know what the consequence will be. Mutalyzer returns a p.(=), which we don't want here.
                                    $(oThisProtein).attr('value', 'p.?');
                                }
                                // Highlight the protein input field which has been modified.
                                $(oThisProtein).attr('style', 'background : #AAFFAA;');

                                // Fade background to white, then remove the class.
                                var nColor = 170;
                                for (i = nColor; i < 255; i++) {
                                    setTimeout(function () {
                                        $(oThisProtein).attr('style', 'background : #' + nColor.toString(16).toUpperCase() + 'FF' + nColor.toString(16).toUpperCase() + ';');
                                        nColor ++;
                                    }, (i - 130) * 40);
                                }
                                setTimeout(function () {
                                    $(oThisProtein).removeAttr('style');
                                }, (i - 130) * 40);

                                $(oThisProtein).siblings('img:first').attr({
                                    src: 'gfx/check.png',
                                    alt: 'Prediction OK!',
                                    title: 'Prediction OK!'
                                }).show();
                                $(oThisProtein).siblings('button:eq(0)').hide();
                            }
                        });
                    }
                }
    });
    return false;
}

$( function () {
    var oGenomicVariant = $('#variantForm input[name="VariantOnGenome/DNA"]');
    // Add the button and image at the end of the genomic DNA field.
    $(oGenomicVariant).parent().append('&nbsp;&nbsp;<IMG style="display:none;" align="top" width="16" height="16">&nbsp;<BUTTON class="mapVariant" type="button" onclick="lovd_convertPosition(this); return false;" style="display:none;">Map variant</BUTTON>');
    // Add an onChange event that runs lovd_checkHGVS.
    $(oGenomicVariant).change(lovd_checkHGVS);
    var oTranscriptVariants = $('#variantForm input[name$="_VariantOnTranscript/DNA"]');
    if (oTranscriptVariants[0] != undefined) {
        // Add the buttons and images at the end of the transcripts DNA fields.
        $(oTranscriptVariants).parent().append('&nbsp;&nbsp;<IMG style="display:none;" align="top" width="16" height="16">&nbsp;<BUTTON class="mapVariant" type="button" onclick="lovd_convertPosition(this); return false;" style="display:none;">Map variant</BUTTON>');
        var nTranscriptVariants = oTranscriptVariants.size();
        for (i=0; i < nTranscriptVariants; i++) {
            // Add an artificial attribute "id_ncbi" to the transcripts DNA input field. This is needed to link the response from Mutalyzer to this field, if needed.
            $(oTranscriptVariants[i]).attr('id_ncbi', aTranscripts[$(oTranscriptVariants[i]).attr('name').substring(0,5)][0]);
        }
        // Add an onChange event that runs lovd_checkHGVS.
        $(oTranscriptVariants).change(lovd_checkHGVS);
    }
    var oProteinVariants = $('#variantForm input[name$="_VariantOnTranscript/Protein"]');
    if (oProteinVariants[0] != undefined) {
        // Add the buttons and images at the end of the protein description fields.
        $(oProteinVariants).parent().append('&nbsp;&nbsp;<IMG src="gfx/trans.png" style="display:inline;" align="top" width="16" height="16">&nbsp;<BUTTON class="proteinChange" type="button" onclick="lovd_getProteinChange(this); return false;" style="display:none;">Predict</BUTTON>');
    }
});
