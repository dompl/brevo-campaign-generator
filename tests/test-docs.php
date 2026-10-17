<?php
/**
 * Docs and help page describe pound billing, and no ledger/Gemini/Stripe code remains.
 */
require_once __DIR__ . '/lib.php';

$changelog = file_get_contents( BCG_PLUGIN_DIR . 'CHANGELOG.md' );
bcg_assert( false !== strpos( $changelog, '## [1.6.0]' ), 'CHANGELOG has a 1.6.0 entry' );
bcg_assert( strpos( $changelog, '## [1.6.0]' ) < strpos( $changelog, '## [1.5.50]' ), '1.6.0 entry sits above 1.5.50' );

$readme = file_get_contents( BCG_PLUGIN_DIR . 'README.md' );
foreach ( array( 'Gemini', 'Stripe', 'Credits & Billing', 'BCG_OPENAI_API_KEY', 'credit pack' ) as $gone ) {
	bcg_assert( false === stripos( $readme, $gone ), "README no longer mentions {$gone}" );
}
bcg_assert( false !== strpos( $readme, 'AI Credits' ), 'README points at the AI Credits page' );

$help = file_get_contents( BCG_PLUGIN_DIR . 'admin/views/page-help.php' );
foreach ( array( 'Credits & Billing', 'credits are automatically refunded', 'credit pack', 'Top Up" to buy' ) as $gone ) {
	bcg_assert( false === stripos( $help, $gone ), "help page no longer says {$gone}" );
}
bcg_assert( false !== strpos( $help, 'AI Credits' ), 'help page names the AI Credits page' );
bcg_assert( false !== strpos( $help, 'id="ai-credits"' ), 'help section anchor kept' );

// Leftover sweep over every PHP/JS file: the only permitted mentions are the 1.6.0 upgrade
// (class-bcg-activator.php) and uninstall.php's DROP TABLE lines.
$leftovers = array();
$iter      = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( BCG_PLUGIN_DIR, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iter as $file ) {
	$path = $file->getPathname();
	if ( ! preg_match( '/\.(php|js)$/', $path ) || false !== strpos( $path, '/tests/' ) || false !== strpos( $path, '/node_modules/' ) || false !== strpos( $path, '/vendor/' ) ) {
		continue;
	}
	$rel = substr( $path, strlen( BCG_PLUGIN_DIR ) );
	if ( in_array( $rel, array( 'includes/class-bcg-activator.php', 'uninstall.php' ), true ) ) {
		continue;
	}
	foreach ( file( $path ) as $n => $line ) {
		if ( preg_match( '/Gemini|gemini|BCG_Credits|BCG_Stripe|bcg_credits|bcg_transactions|credits_used|new_balance|[Ss]tripe(?!d)|credit_pack|bcg_test_mode|insufficient_credits/', $line ) ) {
			$leftovers[] = $rel . ':' . ( $n + 1 ) . ' ' . trim( $line );
		}
	}
}
bcg_assert( array() === $leftovers, 'no ledger/Gemini/Stripe leftovers: ' . implode( ' | ', array_slice( $leftovers, 0, 10 ) ) );
bcg_done( 'test-docs' );
