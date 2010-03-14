<?php
require_once 'makepot.php';

$options = getopt("c:p:m:n:s");
if (empty($options)) {
?>
	-c	Application svn checkout
	-p	POT svn checkout
	-m	MakePOT project
	-n	POT filename
<?php
	die;
}

$application_svn_checkout = realpath($options['c']);
$pot_svn_checkout = realpath($options['p']);
$makepot_project = str_replace('-', '_', $options['m']);
$pot_name = $options['n'];
$no_branch_dirs = isset($options['s']);

$makepot = new MakePOT(false);

$versions = array();

chdir($application_svn_checkout);
system("svn up");
if (is_dir("trunk")) $versions[] = 'trunk';
$branches = glob('branches/*');
if (false !== $branches) $versions = array_merge($versions, $branches);
$tags = glob('tags/*');
if (false !== $tags) $versions = array_merge($versions, $tags);

if ($no_branch_dirs)
	$versions = array('.');

chdir($pot_svn_checkout);
system("svn up");
foreach($versions as $version) {
	$pot = "$version/$pot_name";
	$exists = is_file($pot);
	// do not update old tag pots
	if ('tags/' == substr($version, 0, 5) && $exists) continue;
	if (!is_dir($version)) system("svn mkdir $version");
	$real_application_svn_checkout = realpath($application_svn_checkout);
	call_user_func(array(&$makepot, $makepot_project), "$real_application_svn_checkout/$version", "$pot_svn_checkout/$pot");
	if (!$exists) system("svn add $pot");
	// do not commit if the difference is only in the header, but always commit a new file
	if (!$exists || `svn diff $pot | wc -l` > 13) {
		preg_match('/Revision:\s+(\d+)/', `svn info $real_application_svn_checkout/$version`, $matches);
		$logmsg = isset($matches[1]) && intval($matches[1])? "POT, generated from r".intval($matches[1]) : 'Automatic POT update';
		system("svn ci $pot --message='$logmsg'");
	}
}

?>
