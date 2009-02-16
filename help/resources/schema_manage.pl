#!/usr/bin/perl -w
#
#
# WARNING: THIS SCRIPT IS CURRENTLY UNDER DEVELOPMENT AND IS NOT CURRENT SAFE TO USE
# AND MOST LIKELY WILL NOT WORK
#
# resources/utilities/schema_manage.pl
#
# This script installs/updates the schema data for the Amberdms Billing System. It works
# by performing the following steps:
#
# 1. Check if the database is installed already - if so, get version.
# 2. Run through install or update SQL files (depending what is required).
# 3. Update version information in MySQL.
#
# It can also be used to remove the schema and data for a particular module
# if desired. In that case, it simple runs the delete SQL file.
# 
#

use strict;
use DBI;
use Getopt::Long;



## DEFAULT SETTINGS ##

my $debug = 0;
my $help;

my $opt_cfgfile			= "/etc/amberdms/billing_system/config.php";

my $opt_module_name;
my $opt_module_schema;
my $opt_module_version_schema;

my $opt_action = "install";

# get CLI options
GetOptions ("cfgfile=s"		=> \$opt_cfgfile,	"c=s"	=> \$opt_cfgfile,
	    "schemadir=s"	=> \$opt_module_schema,	"s=s"	=> \$opt_module_schema,
	    "action=s"		=> \$opt_action,	"a=s"	=> \$opt_action,
	    "verbose"		=> \$debug,		"v"	=> \$debug,
	    "help"		=> \$help,		"h"	=> \$help);

if ($help)
{
	print "AOConf Schema Management Utility\n";
	print "\n";
	print "Usage: schema_manage.pl --schemadir=<schemadir>\n";
	print "\n";
	print "-c, --cfgfile=FILENAME            Billing System configuration file with MySQL settings.\n";
	print "\n";
	print "-s, --schemadir=LOCATION          Location of the schema SQL files..\n";
	print "\n";
	print "-a, --action=[install|delete]     Whether to install or delete the schema.\n";
	print "\n";
	print "-v, --verbose\n";
	print "\n";
	print "-h, --help\n";
	print "\n";

	exit 0;
}



# check options
if (!$opt_cfgfile)
{
	die("Error: No config file specified.\n");
}

if (!$opt_module_schema)
{
	die("Error: No schmea directory provided.\n");
}

if ($opt_action ne "install" && $opt_action ne "delete")
{
	die("Error: Invalid action provided - needs to be either install or delete\n");
}


## GET CONFIG ##
my ($db_host, $db_name, $db_user, $db_pass);

open(CFG, $opt_cfgfile) || die("Error: Unable to open config file $opt_cfgfile");

while (my $line = <CFG>)
{
	chomp($line);
	
	if ($line =~ /"db_host"\]\s*=\s*"(\S*)";/)
	{
		$db_host = $1;
	}

	if ($line =~ /"db_name"\]\s*=\s*"(\S*)";/)
	{
		$db_name = $1;
	}
	
	if ($line =~ /"db_user"\]\s*=\s*"(\S*)";/)
	{
		$db_user = $1;
	}

	if ($line =~ /"db_pass"\]\s*=\s*"(\S*)";/)
	{
		$db_pass = $1;
	}
}

close(CFG);

# check that DB config was gathered.
if (!$db_host || !$db_name || !$db_user || !$db_pass)
{
	die("Error: Unable to gather required information from config file $opt_cfgfile");
}



## PROGRAM ##

# connect to MySQL DB.
my $mysql_handle = DBI->connect("dbi:mysql:database=$db_name;host=$db_host;user=$db_user;password=$db_pass") || die("Error: Unable to connect to MySQL database: $DBI::errstr\n");
my ($mysql_string, $mysql_result, $mysql_data);


## 1. CHECK IF DB IS ALREADY INSTALLED
print "Checking if module is already installed...\n" if $debug;

$mysql_string = "SELECT * FROM `app_modules` WHERE name='$opt_module_name'";
$mysql_result = $mysql_handle->prepare($mysql_string) || die("Error: SQL query ($mysql_string) failed: $DBI::errstr\n");
$mysql_result->execute;

while ($mysql_data = $mysql_result->fetchrow_hashref())
{
	if ($opt_action eq "install")
	{
		$opt_action = "upgrade";
	}

	$opt_module_version_schema	= $mysql_data->{version_schema};
}
$mysql_result->finish;


## 2. INSTALL/UPGRADE SCHEMA
print "Action is: $opt_action\n" if $debug;

if ($opt_action eq "install")
{
	print "Searching $opt_module_schema for latest install schema...\n" if $debug;

	# determine the latest install file
	my @data	= glob("$opt_module_schema/version_*_install.sql");
	my $count	= scalar @data;

	if ($count == 0)
	{
		print "No schema exists for this module.\n" if $debug;
	}
	else
	{
		$count = $count - 1;
		
		print $data[$count] ." is the latest file and will be used for the install.\n" if $debug;

		# import schema
		print "Importing file $data[$count]\n" if $debug;
		import_sql($data[$count], $mysql_handle);
	}

}
elsif ($opt_action eq "upgrade")
{
	print "Existing schema version is: $opt_module_version_schema\n" if $debug;

	# get a list of upgrade files
	my @data	= glob("$opt_module_schema/version_*_upgrade.sql");
	my $count	= scalar @data;

	if ($count == 0)
	{
		print "No data needs to be imported.\n" if $debug;
	}
	else
	{
		print "Applying any upgrade SQL files that match...\n" if $debug;
		
		my $latestversion;
		foreach my $sqlfile (@data)
		{
			if ($sqlfile =~ /_([0-9]{4}[0-9]{2}[0-9]{2}[0-9]*)_/)
			{
				$latestversion = $1;
				
				if ($latestversion > $opt_module_version_schema)
				{
					# need to import this schema upgrade file
					print "Importing file $sqlfile\n" if $debug;
					import_sql($sqlfile, $mysql_handle);
				}
			}
			else
			{
				print "Warning: Incorrectly named SQL upgrade file: $sqlfile\n";
			}
		}
	}
}
elsif ($opt_action eq "delete")
{
	print "Searching $opt_module_schema for latest delete queries...\n" if $debug;

	# determine the latest install file
	my @data	= glob("$opt_module_schema/version_*_delete.sql");
	my $count	= scalar @data;

	if ($count == 0)
	{
		print "No delete SQL queries exists for this module.\n" if $debug;
	}
	else
	{
		$count = $count - 1;
		
		print $data[$count] ." is the latest file and will be used for the delete.\n" if $debug;

		# import schema
		print "Importing file $data[$count]\n" if $debug;
		import_sql($data[$count], $mysql_handle);
	}
}


# disconnect from MySQL
$mysql_handle->disconnect;

print "Schema installation complete!\n" if $debug;
exit 0;


## FUNCTIONS


#
# import_sql ( filename, MySQL handle )
#
# Imports the specified SQL file into MySQL
#
sub import_sql
{
	my $sqlfile		= shift;
	my $mysql_handle	= shift;

	open(SQL, "$sqlfile") or die("Error: Unable to open $sqlfile\n");
				
	my @statements = split(/;\n/,join('',<SQL>));
	foreach my $sqlline ( @statements )
	{
		# remove crap lines
		if ($sqlline =~ /^\s*$/)
		{
			next;
		}
		
		if ($sqlline =~ /^#/)
		{
			next;
		}


		# line is good - process it.
		if ($sqlline)
		{
			$mysql_handle->do($sqlline);
		}
	}
				    
	close(SQL);
}


