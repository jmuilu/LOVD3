                                <?php exit(); ?>                                
#################### DO NOT MODIFY OR REMOVE THE LINE ABOVE ####################
################################################################################
#                              LOVD settings file                              #
#                                    v. 3.0                                    #
################################################################################
#                                                                              #
# Lines starting with # are comments and ignored by LOVD, as are empty lines.  #
#                                                                              #
# Default values of directives are mentioned when applicable. To keep the      #
# default settings, leave the line untouched.                                  #
#                                                                              #
################################################################################



[database]

# MySQL database host. Defaults to 'localhost'.
#
hostname = localhost

# MySQL database username and password (required).
#
username = lovd
password = lovd_pw

# MySQL database name (required).
#
database = lovd3

# This is for the table prefixes; if you wish to install more than one LOVD
# system per MySQL database, use different directories for these installations
# and change the setting below to a unique value.
# Please use alphanumeric characters only. Defaults to 'lovd'.
#
table_prefix = lovd_v3
# (test alternative is lovd_v33)
