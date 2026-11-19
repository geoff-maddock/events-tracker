/* global process, console */
// Runs `npm audit` and fails on any high or critical advisory except those listed in IGNORED.
// npm audit has no ignore option (composer audit does: composer.json config.audit.ignore).
// Only add an advisory that has no patched release, with the reason; remove it once a fix ships.
import { execFileSync } from 'node:child_process';

// Example entry:  'GHSA-xxxx-xxxx-xxxx': 'package, no fix released',
const IGNORED = {};

const GATED = new Set(['high', 'critical']);

let output;
try {
    output = execFileSync('npm', ['audit', '--json'], { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 });
} catch (error) {
    // npm audit exits non-zero when it finds anything; the JSON report is still on stdout.
    output = error.stdout;
    if (!output) {
        throw error;
    }
}

const report = JSON.parse(output);
if (report.error) {
    console.error(`npm audit failed: ${report.error.summary ?? JSON.stringify(report.error)}`);
    process.exit(1);
}

// Each advisory appears as an object in `via`; string entries just point at a vulnerable dependency.
const advisories = new Map();
for (const vulnerability of Object.values(report.vulnerabilities ?? {})) {
    for (const via of vulnerability.via) {
        if (typeof via === 'object' && GATED.has(via.severity)) {
            advisories.set(via.url, via);
        }
    }
}

const ids = (advisory) => advisory.url.split('/').pop();
const failing = [...advisories.values()].filter((advisory) => !(ids(advisory) in IGNORED));
const ignored = [...advisories.values()].filter((advisory) => ids(advisory) in IGNORED);

for (const advisory of ignored) {
    console.log(`ignored  ${advisory.severity}  ${advisory.name} ${advisory.range}  ${advisory.url} (${IGNORED[ids(advisory)]})`);
}
for (const advisory of failing) {
    console.log(`FAILING  ${advisory.severity}  ${advisory.name} ${advisory.range}  ${advisory.title}  ${advisory.url}`);
}

const seen = new Set([...advisories.values()].map(ids));
for (const id of Object.keys(IGNORED).filter((id) => !seen.has(id))) {
    console.log(`note: ${id} is ignored but no longer reported; remove it from ${import.meta.url.split('/').pop()}`);
}

if (failing.length > 0) {
    console.log(`\n${failing.length} unignored high/critical advisor${failing.length === 1 ? 'y' : 'ies'}. Run \`npm audit\` for details.`);
    process.exit(1);
}
console.log('No unignored high or critical advisories.');
