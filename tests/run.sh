#!/usr/bin/env bash
# Run every brevo-campaign-generator test script. Exit non-zero if any fails.
set -u
cd /var/www/vhosts/rfsdev.co.uk/httpdocs/atsdiamondtools || exit 1
fail=0
for t in wp-content/plugins/brevo-campaign-generator/tests/test-*.php; do
	echo "== $t"
	wp eval-file "$t" --skip-themes || fail=1
done
exit $fail
