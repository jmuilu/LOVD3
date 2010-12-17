<?php
/******************************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2009-10-22
 * Modified    : 2010-09-25
 * For LOVD    : 3.0-pre-09
 *
 * Copyright   : 2004-2010 Leiden University Medical Center; http://www.LUMC.nl/
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

// STILL TODO: genomic DNA field is standard and not custom???
// VARIANTS_COLS en PHENOTYPE_COLS samenvoegen... why not? Misschien zelfs samenvoegen met ACTIVE_COLS tot 1 tabel? Ik moet dat echt nog ff uitdenken...
// Heeft variant niet een owner id nodig? Of dat is de owner van de screening? Maar dat kunnen meerdere mensen zijn...
// De variant kolommen moeten nog verdeeld worden; kolommen moeten naar transcript specifieke tabel.
// transcripts echt altijd aan genen vast??? Of misschien niet??? microRNA's??
// PATHOGENICITY.....
// All those IDs for the genes!!! Store differently?
// Change allele to diploid_count structure??? How will we then create per-allele descriptions for mutalyzer? Usually homozygous entries are the same anyway. Allele=homozygous then?
// "Parental_origin and Origin attributes have been merged into one attribute called as genetic_source."
// variant <-> pathogenicity <-> disease? Link pathogenicity specifically to one of the phenotypes or diseases?
// Allow download staat nu per gen, en de losse varianten dan?

// DMD_SPECIFIC
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', '../');
    require ROOT_PATH . 'inc-init.php';
}

$aTableSQL =
         array('TABLE_COUNTRIES' =>
   'CREATE TABLE ' . TABLE_COUNTRIES . ' (
    id CHAR(2) NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY (id))
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_USERS' =>
   'CREATE TABLE ' . TABLE_USERS . ' (
    id SMALLINT(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    name VARCHAR(75) NOT NULL,
    institute VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    telephone VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(255) NOT NULL,
    countryid CHAR(2),
    email TEXT NOT NULL,
    reference VARCHAR(50) NOT NULL,
    username VARCHAR(20) NOT NULL,
    password CHAR(32) NOT NULL,
    password_autogen CHAR(32),
    password_force_change BOOLEAN NOT NULL,
    phpsessid CHAR(32),
    current_db VARCHAR(12),
    saved_work TEXT,
    level TINYINT(1) UNSIGNED NOT NULL,
    allowed_ip VARCHAR(255) NOT NULL,
    login_attempts TINYINT(1) UNSIGNED NOT NULL,
    last_login DATETIME,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    PRIMARY KEY (id),
    INDEX(countryid),
    UNIQUE (username),
    INDEX (created_by),
    INDEX (edited_by),
    FOREIGN KEY (countryid) REFERENCES ' . TABLE_COUNTRIES . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_GENES' =>
   'CREATE TABLE ' . TABLE_GENES . ' (
    id VARCHAR(12) NOT NULL,
    symbol VARCHAR(12) NOT NULL,
    name VARCHAR(255) NOT NULL,
    chrom_location VARCHAR(20) NOT NULL,
    refseq_genomic VARCHAR(15) NOT NULL,
    reference VARCHAR(255) NOT NULL,
    url_homepage VARCHAR(255) NOT NULL,
    url_external TEXT NOT NULL,
    allow_download BOOLEAN NOT NULL,
    allow_index_wiki BOOLEAN NOT NULL,
    id_hgnc INT(10) UNSIGNED NOT NULL,
    id_entrez INT(10) UNSIGNED NOT NULL,
    id_omim INT(10) UNSIGNED NOT NULL,
    id_uniprot VARCHAR(8) NOT NULL,
    show_hgmd BOOLEAN NOT NULL,
    show_genecards BOOLEAN NOT NULL,
    show_genetests BOOLEAN NOT NULL,
    note_index TEXT NOT NULL,
    note_listing TEXT NOT NULL,
    genbank TINYINT(1) UNSIGNED NOT NULL,
    genbank_uri VARCHAR(25) NOT NULL,
    refseq VARCHAR(1) NOT NULL,
    refseq_url VARCHAR(255) NOT NULL,
    disclaimer TINYINT(1) UNSIGNED NOT NULL,
    disclaimer_text TEXT NOT NULL,
    header TEXT NOT NULL,
    header_align TINYINT(1) NOT NULL,
    footer TEXT NOT NULL,
    footer_align TINYINT(1) NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    updated_by SMALLINT(5) UNSIGNED,
    updated_date DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (created_by),
    INDEX (edited_by),
    INDEX (updated_by),
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_CURATES' =>
   'CREATE TABLE ' . TABLE_CURATES . ' (
    userid SMALLINT(5) UNSIGNED NOT NULL,
    geneid VARCHAR(12) NOT NULL,
    allow_edit BOOLEAN NOT NULL,
    show_order TINYINT(2) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (userid, geneid),
    INDEX (geneid),
    FOREIGN KEY (userid) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (geneid) REFERENCES ' . TABLE_GENES . ' (id) ON DELETE CASCADE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_TRANSCRIPTS' =>
   'CREATE TABLE ' . TABLE_TRANSCRIPTS . ' (
    id SMALLINT(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    geneid VARCHAR(12) NOT NULL,
    name VARCHAR(255) NOT NULL,
    id_ncbi VARCHAR(255) NOT NULL,
    id_ensembl VARCHAR(255) NOT NULL,
    id_protein_ncbi VARCHAR(255) NOT NULL,
    id_protein_ensembl VARCHAR(255) NOT NULL,
    chromosome VARCHAR(2) NOT NULL,
    position_c_mrna_start SMALLINT NOT NULL,
    position_c_mrna_end MEDIUMINT UNSIGNED NOT NULL,
    position_c_cds_end MEDIUMINT UNSIGNED NOT NULL,
    position_g_mrna_start INT UNSIGNED NOT NULL,
    position_g_mrna_end INT UNSIGNED NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    PRIMARY KEY (id),
    INDEX (geneid),
    INDEX (created_by),
    INDEX (edited_by),
    FOREIGN KEY (geneid) REFERENCES ' . TABLE_GENES . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_DISEASES' =>
   'CREATE TABLE ' . TABLE_DISEASES . ' (
    id SMALLINT(5) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    symbol VARCHAR(15) NOT NULL,
    name VARCHAR(255) NOT NULL,
    id_omim INT(10) UNSIGNED NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    PRIMARY KEY (id),
    UNIQUE (symbol),
    INDEX (created_by),
    INDEX (edited_by),
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_GEN2DIS' =>
   'CREATE TABLE ' . TABLE_GEN2DIS . ' (
    geneid VARCHAR(12) NOT NULL,
    diseaseid SMALLINT(5) UNSIGNED ZEROFILL NOT NULL,
    PRIMARY KEY (geneid, diseaseid),
    INDEX (diseaseid),
    FOREIGN KEY (geneid) REFERENCES ' . TABLE_GENES . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (diseaseid) REFERENCES ' . TABLE_DISEASES . ' (id) ON DELETE CASCADE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_DATA_STATUS' =>
   'CREATE TABLE ' . TABLE_DATA_STATUS . ' (
    id TINYINT(1) UNSIGNED NOT NULL,
    name VARCHAR(15) NOT NULL,
    PRIMARY KEY (id))
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_PATHOGENIC' =>
   'CREATE TABLE ' . TABLE_PATHOGENIC . ' (
    id TINYINT(2) UNSIGNED ZEROFILL NOT NULL,
    name VARCHAR(5) NOT NULL,
    PRIMARY KEY (id))
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_PATIENTS' =>
   'CREATE TABLE ' . TABLE_PATIENTS . ' (
    id MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL,
    ownerid SMALLINT(5) UNSIGNED ZEROFILL,
    statusid TINYINT(1) UNSIGNED,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL DEFAULT "9999-12-31",
    deleted BOOLEAN NOT NULL,
    deleted_by SMALLINT(5) UNSIGNED,
    PRIMARY KEY (id, valid_from),
    INDEX (valid_to),
    INDEX (ownerid),
    INDEX (statusid),
    INDEX (created_by),
    INDEX (edited_by),
    INDEX (deleted_by),
    FOREIGN KEY (ownerid) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (statusid) REFERENCES ' . TABLE_DATA_STATUS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (deleted_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_VARIANTS' =>
   'CREATE TABLE ' . TABLE_VARIANTS . ' (
    id MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL,
    patientid MEDIUMINT(8) UNSIGNED ZEROFILL,
    allele TINYINT(2) UNSIGNED NOT NULL,
    pathogenicid TINYINT(2) UNSIGNED ZEROFILL,
    position_g_start INT UNSIGNED,
    position_g_end INT UNSIGNED,
    type VARCHAR(10),
    statusid TINYINT(1) UNSIGNED,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL DEFAULT "9999-12-31",
    deleted BOOLEAN NOT NULL,
    deleted_by SMALLINT(5) UNSIGNED,
    PRIMARY KEY (id, valid_from),
    INDEX (valid_to),
    INDEX (patientid),
    INDEX (allele),
    INDEX (pathogenicid),
    INDEX (position_g_start, position_g_end),
    INDEX (statusid),
    INDEX (created_by),
    INDEX (edited_by),
    INDEX (deleted_by),
    FOREIGN KEY (patientid) REFERENCES ' . TABLE_PATIENTS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (pathogenicid) REFERENCES ' . TABLE_PATHOGENIC . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (statusid) REFERENCES ' . TABLE_DATA_STATUS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (deleted_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_VARIANTS_ON_TRANSCRIPTS' =>
   'CREATE TABLE ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' (
    id MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL,
    transcriptid SMALLINT(5) UNSIGNED ZEROFILL NOT NULL,
    pathogenicid TINYINT(2) UNSIGNED ZEROFILL,
    chromosome VARCHAR(2) NOT NULL,
    position_c_start MEDIUMINT,
    position_c_start_intron INT,
    position_c_end MEDIUMINT,
    position_c_end_intron INT,
    valid_from DATETIME NOT NULL,
    PRIMARY KEY (id, valid_from, transcriptid),
    INDEX (transcriptid),
    INDEX (pathogenicid),
    INDEX (position_c_start, position_c_end),
    INDEX (position_c_start, position_c_start_intron, position_c_end, position_c_end_intron),
    FOREIGN KEY (transcriptid) REFERENCES ' . TABLE_TRANSCRIPTS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (pathogenicid) REFERENCES ' . TABLE_PATHOGENIC . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_PHENOTYPES' =>
   'CREATE TABLE ' . TABLE_PHENOTYPES . ' (
    id INT(10) UNSIGNED ZEROFILL NOT NULL,
    diseaseid SMALLINT(5) UNSIGNED ZEROFILL NOT NULL,
    patientid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL,
    ownerid SMALLINT(5) UNSIGNED ZEROFILL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL DEFAULT "9999-12-31",
    deleted BOOLEAN NOT NULL,
    deleted_by SMALLINT(5) UNSIGNED,
    PRIMARY KEY (id, valid_from),
    INDEX (valid_to),
    INDEX (diseaseid),
    INDEX (patientid),
    INDEX (ownerid),
    INDEX (created_by),
    INDEX (edited_by),
    INDEX (deleted_by),
    FOREIGN KEY (diseaseid) REFERENCES ' . TABLE_DISEASES . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (patientid) REFERENCES ' . TABLE_PATIENTS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (ownerid) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (deleted_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_SCREENINGS' =>
   'CREATE TABLE ' . TABLE_SCREENINGS . ' (
    id INT(10) UNSIGNED ZEROFILL NOT NULL,
    patientid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL,
    ownerid SMALLINT(5) UNSIGNED ZEROFILL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL DEFAULT "9999-12-31",
    deleted BOOLEAN NOT NULL,
    deleted_by SMALLINT(5) UNSIGNED,
    PRIMARY KEY (id, valid_from),
    INDEX (valid_to),
    INDEX (patientid),
    INDEX (ownerid),
    INDEX (created_by),
    INDEX (edited_by),
    INDEX (deleted_by),
    FOREIGN KEY (patientid) REFERENCES ' . TABLE_PATIENTS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (ownerid) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (deleted_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_SCR2GENE' =>
   'CREATE TABLE ' . TABLE_SCR2GENE . ' (
    screeningid INT(10) UNSIGNED ZEROFILL NOT NULL,
    geneid VARCHAR(12) NOT NULL,
    PRIMARY KEY (screeningid, geneid),
    INDEX (screeningid),
    INDEX (geneid),
    FOREIGN KEY (screeningid) REFERENCES ' . TABLE_SCREENINGS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (geneid) REFERENCES ' . TABLE_GENES . ' (id) ON DELETE CASCADE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_SCR2VAR' =>
   'CREATE TABLE ' . TABLE_SCR2VAR . ' (
    screeningid INT(10) UNSIGNED ZEROFILL NOT NULL,
    variantid MEDIUMINT(8) UNSIGNED ZEROFILL NOT NULL,
    PRIMARY KEY (screeningid, variantid),
    INDEX (screeningid),
    INDEX (variantid),
    FOREIGN KEY (screeningid) REFERENCES ' . TABLE_SCREENINGS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (variantid) REFERENCES ' . TABLE_VARIANTS . ' (id) ON DELETE CASCADE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_COLS' =>
   'CREATE TABLE ' . TABLE_COLS . ' (
    id VARCHAR(100) NOT NULL,
    col_order SMALLINT(5) UNSIGNED NOT NULL,
    width SMALLINT(5) UNSIGNED NOT NULL,
    hgvs BOOLEAN NOT NULL,
    standard BOOLEAN NOT NULL,
    mandatory BOOLEAN NOT NULL,
    head_column VARCHAR(50) NOT NULL,
    description_form TEXT NOT NULL,
    description_legend_short TEXT NOT NULL,
    description_legend_full TEXT NOT NULL,
    mysql_type VARCHAR(255) NOT NULL,
    form_type VARCHAR(255) NOT NULL,
    select_options TEXT NOT NULL,
    preg_pattern VARCHAR(255) NOT NULL,
    public_view BOOLEAN NOT NULL,
    public_add BOOLEAN NOT NULL,
    allow_count_all BOOLEAN NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    PRIMARY KEY (id),
    INDEX (created_by),
    INDEX (edited_by),
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_ACTIVE_COLS' =>
   'CREATE TABLE ' . TABLE_ACTIVE_COLS . ' (
    colid VARCHAR(100) NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    PRIMARY KEY (colid),
    INDEX (created_by),
    FOREIGN KEY (colid) REFERENCES ' . TABLE_COLS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_VARIANT_COLS' =>
   'CREATE TABLE ' . TABLE_VARIANT_COLS . ' (
    geneid VARCHAR(12) NOT NULL,
    colid VARCHAR(100) NOT NULL,
    col_order TINYINT(3) UNSIGNED NOT NULL,
    width SMALLINT(5) UNSIGNED NOT NULL,
    mandatory BOOLEAN NOT NULL,
    description_form TEXT NOT NULL,
    description_legend_short TEXT NOT NULL,
    description_legend_full TEXT NOT NULL,
    select_options TEXT NOT NULL,
    public_view BOOLEAN NOT NULL,
    public_add BOOLEAN NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    PRIMARY KEY (geneid, colid),
    INDEX (colid),
    INDEX (created_by),
    INDEX (edited_by),
    FOREIGN KEY (geneid) REFERENCES ' . TABLE_GENES . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (colid) REFERENCES ' . TABLE_ACTIVE_COLS . ' (colid) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_PHENOTYPE_COLS' =>
   'CREATE TABLE ' . TABLE_PHENOTYPE_COLS . ' (
    diseaseid SMALLINT(5) UNSIGNED ZEROFILL NOT NULL,
    colid VARCHAR(100) NOT NULL,
    col_order TINYINT(3) UNSIGNED NOT NULL,
    width SMALLINT(5) UNSIGNED NOT NULL,
    mandatory BOOLEAN NOT NULL,
    description_form TEXT NOT NULL,
    description_legend_short TEXT NOT NULL,
    description_legend_full TEXT NOT NULL,
    select_options TEXT NOT NULL,
    public_view BOOLEAN NOT NULL,
    public_add BOOLEAN NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    PRIMARY KEY (diseaseid, colid),
    INDEX (colid),
    INDEX (created_by),
    INDEX (edited_by),
    FOREIGN KEY (diseaseid) REFERENCES ' . TABLE_DISEASES . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (colid) REFERENCES ' . TABLE_ACTIVE_COLS . ' (colid) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_LINKS' =>
   'CREATE TABLE ' . TABLE_LINKS . ' (
    id TINYINT(3) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    pattern_text VARCHAR(25) NOT NULL,
    replace_text TEXT NOT NULL,
    description TEXT NOT NULL,
    created_by SMALLINT(5) UNSIGNED,
    created_date DATETIME NOT NULL,
    edited_by SMALLINT(5) UNSIGNED,
    edited_date DATETIME,
    PRIMARY KEY (id),
    UNIQUE (name),
    UNIQUE (pattern_text),
    INDEX (created_by),
    INDEX (edited_by),
    FOREIGN KEY (created_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE SET NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_COLS2LINKS' =>
   'CREATE TABLE ' . TABLE_COLS2LINKS . ' (
    colid VARCHAR(100) NOT NULL,
    linkid TINYINT(3) UNSIGNED NOT NULL,
    PRIMARY KEY (colid, linkid),
    INDEX (linkid),
    FOREIGN KEY (colid) REFERENCES ' . TABLE_COLS . ' (id) ON DELETE CASCADE,
    FOREIGN KEY (linkid) REFERENCES ' . TABLE_LINKS . ' (id) ON DELETE CASCADE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_CONFIG' =>
   'CREATE TABLE ' . TABLE_CONFIG . ' (
    system_title VARCHAR(255) NOT NULL,
    institute VARCHAR(255) NOT NULL,
    location_url VARCHAR(255) NOT NULL,
    email_address VARCHAR(75) NOT NULL,
    send_admin_submissions BOOLEAN NOT NULL,
    api_feed_history TINYINT(2) UNSIGNED NOT NULL,
    refseq_build VARCHAR(4) NOT NULL,
    send_stats BOOLEAN NOT NULL,
    include_in_listing BOOLEAN NOT NULL,
    lock_users BOOLEAN NOT NULL,
    allow_unlock_accounts BOOLEAN NOT NULL,
    allow_submitter_mods BOOLEAN NOT NULL,
    allow_count_hidden_entries BOOLEAN NOT NULL,
    use_ssl BOOLEAN NOT NULL,
    use_versioning BOOLEAN NOT NULL,
    lock_uninstall BOOLEAN NOT NULL)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_STATUS' =>
   'CREATE TABLE ' . TABLE_STATUS . ' (
    lock_update BOOLEAN NOT NULL,
    version VARCHAR(15) NOT NULL,
    signature CHAR(32) NOT NULL,
    update_checked_date DATETIME,
    update_version VARCHAR(15),
    update_level TINYINT(1) UNSIGNED,
    update_description VARCHAR(255),
    update_released_date DATE,
    installed_date DATE NOT NULL,
    updated_date DATE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_SOURCES' =>
   'CREATE TABLE ' . TABLE_SOURCES . ' (
    name VARCHAR(15) NOT NULL,
    url VARCHAR(255) NOT NULL,
    PRIMARY KEY (name))
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_LOGS' =>
   'CREATE TABLE ' . TABLE_LOGS . ' (
    name VARCHAR(10) NOT NULL,
    date DATETIME NOT NULL,
    mtime MEDIUMINT(6) UNSIGNED ZEROFILL NOT NULL,
    userid SMALLINT(5) UNSIGNED,
    event VARCHAR(20) NOT NULL,
    log TEXT NOT NULL,
    PRIMARY KEY (name, date, mtime),
    INDEX (userid),
    FOREIGN KEY (userid) REFERENCES ' . TABLE_USERS . ' (id) ON DELETE CASCADE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_MODULES' =>
   'CREATE TABLE ' . TABLE_MODULES . ' (
    id VARCHAR(15) NOT NULL,
    name VARCHAR(50) NOT NULL,
    version VARCHAR(15) NOT NULL,
    description VARCHAR(255) NOT NULL,
    active BOOLEAN NOT NULL,
    settings TEXT NOT NULL,
    installed_date DATE NOT NULL,
    updated_date DATE,
    PRIMARY KEY (id))
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'

         , 'TABLE_HITS' =>
   'CREATE TABLE ' . TABLE_HITS . ' (
    geneid VARCHAR(12) NOT NULL,
    type VARCHAR(10) NOT NULL,
    year SMALLINT(4) UNSIGNED NOT NULL,
    month TINYINT(2) UNSIGNED NOT NULL,
    hits SMALLINT(5) UNSIGNED NOT NULL,
    PRIMARY KEY (geneid, type, year, month),
    FOREIGN KEY (geneid) REFERENCES ' . TABLE_GENES . ' (id) ON DELETE CASCADE)
    TYPE=InnoDB,
    DEFAULT CHARACTER SET utf8'
          );

// DMD_SPECIFIC;
if (lovd_getProjectFile() == '/install/inc-sql-tables.php') {
    header('Content-type: text/plain; charset=UTF-8');
    var_dump($aTableSQL);
}
?>