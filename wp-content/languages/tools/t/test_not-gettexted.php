<?php
/**
 * Tests for not-gettexted.php
 *
 * @version $Id: test_not-gettexted.php 4129 2008-04-22 16:42:37Z nbachiyski $
 * @package wordpress-i18n
 * @subpackage tools
 */
error_reporting(E_ALL);
require_once('PHPUnit/Framework.php');
require_once('../not-gettexted.php');

class Test_Not_Gettexted extends PHPUnit_Framework_TestCase {
	function test_make_string_aggregator() {
		global $baba;
		$f = make_string_aggregator('baba');
		call_user_func($f, 'x', 'y', 'z');
		call_user_func($f, 'a', 'b', 'c');
		$this->assertEquals(array(array('x', 'y', 'z'), array('a', 'b', 'c')), $baba);
	}

	function test_walk() {
		$code = '
<?php
	$s = 8;
echo /* WP_I18N_GUGU*/ 	"yes" /* /WP_I18N_UGU		*/;
	if ($x == "18181") { wp_die(sprintf(/*WP_I18N_DIE*/\'We died %d times!\'/*WP_I18N_DIE*/)); }
?>';
		$tokens = token_get_all($code);	
		$this->assertEquals('', walk_tokens($tokens, 'ignore_token', 'ignore_token'));
		$this->assertEquals('"yes"\'We died %d times!\'', walk_tokens($tokens, 'unchanged_token', 'ignore_token'));
		$this->assertEquals($code, walk_tokens($tokens, 'unchanged_token', 'unchanged_token'));
		$this->assertEquals($code, walk_tokens($tokens, 'unchanged_token', 'unchanged_token'));
	}

	function test_replace() {
		# copy to a new file, so that we don't corrupt the old one
		copy('data/not-gettexted-0.php', 'data/not-gettexted-0-work.php');
		command_replace('data/not-gettexted-0.mo', 'data/not-gettexted-0-work.php');
		$this->assertEquals(file_get_contents('data/not-gettexted-0-result.php'), file_get_contents('data/not-gettexted-0-work.php'));
		unlink();
	}

}
?>
