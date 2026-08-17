<?php

namespace App\Console\Commands;

use App\Models\EmailSuppression;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Imports a provider suppression export (Mailgun bounces/complaints/
 * unsubscribes, or any CSV with an address column) into email_suppressions.
 *
 * Run this before the SES cutover. SES starts with no knowledge of the
 * Mailgun sending history, so without an import the first send retries every
 * address the old provider had already given up on.
 */
class ImportEmailSuppressions extends Command
{
    protected $signature = 'email:import-suppressions
        {file : Path to the CSV export}
        {--reason=bounce : bounce|complaint|unsubscribe|manual}
        {--source=mailgun : Which provider the export came from}
        {--dry-run : Parse and report without writing}';

    protected $description = 'Import a provider bounce/complaint/unsubscribe export into the suppression list.';

    /** Columns an address may live under, in order of preference. */
    private const ADDRESS_COLUMNS = ['address', 'email', 'recipient'];

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $reason = (string) $this->option('reason');
        $source = (string) $this->option('source');
        $dryRun = (bool) $this->option('dry-run');

        $allowed = [
            EmailSuppression::REASON_BOUNCE,
            EmailSuppression::REASON_COMPLAINT,
            EmailSuppression::REASON_UNSUBSCRIBE,
            EmailSuppression::REASON_MANUAL,
        ];

        if (!in_array($reason, $allowed, true)) {
            $this->error('--reason must be one of: '.implode(', ', $allowed));

            return self::FAILURE;
        }

        if (!is_readable($path)) {
            $this->error("Cannot read {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if (false === $handle) {
            $this->error("Cannot open {$path}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if (!is_array($header)) {
            fclose($handle);
            $this->error('The file has no header row.');

            return self::FAILURE;
        }

        $header = array_map(static fn ($h) => mb_strtolower(trim((string) $h)), $header);

        $addressIndex = null;
        foreach (self::ADDRESS_COLUMNS as $candidate) {
            $found = array_search($candidate, $header, true);
            if (false !== $found) {
                $addressIndex = (int) $found;
                break;
            }
        }

        if (null === $addressIndex) {
            fclose($handle);
            $this->error('No address column found. Expected one of: '.implode(', ', self::ADDRESS_COLUMNS));

            return self::FAILURE;
        }

        $codeIndex = array_search('code', $header, true);
        $errorIndex = array_search('error', $header, true);
        $dateIndex = array_search('created_at', $header, true);

        $imported = 0;
        $alreadyPresent = 0;
        $skipped = 0;
        $seen = [];

        // Mailgun wraps multi-line SMTP errors in quotes, so a bounce export
        // has more lines than records — fgetcsv handles the continuation.
        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $raw = trim((string) ($row[$addressIndex] ?? ''));

            if ('' === $raw || !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                ++$skipped;
                continue;
            }

            $email = EmailSuppression::normalize($raw);
            if (isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;

            if ($dryRun) {
                ++$imported;
                continue;
            }

            $suppressedAt = null;
            if (false !== $dateIndex && !empty($row[$dateIndex])) {
                $ts = strtotime((string) $row[$dateIndex]);
                $suppressedAt = false === $ts ? null : date('Y-m-d H:i:s', $ts);
            }

            $record = EmailSuppression::suppress($email, $reason, $source, [
                'code' => false !== $codeIndex ? (substr((string) ($row[$codeIndex] ?? ''), 0, 16) ?: null) : null,
                'error' => false !== $errorIndex ? (($row[$errorIndex] ?? '') ?: null) : null,
                'suppressed_at' => $suppressedAt,
            ]);

            if ($record->wasRecentlyCreated) {
                ++$imported;
            } else {
                ++$alreadyPresent;
            }
        }

        fclose($handle);

        $verb = $dryRun ? 'Would import' : 'Imported';
        $this->info("{$verb}: {$imported}");
        if (!$dryRun) {
            $this->line("Already suppressed: {$alreadyPresent}");
        }
        $this->line("Skipped (blank or invalid): {$skipped}");

        if (!$dryRun) {
            Log::info('ImportEmailSuppressions: import complete', [
                'file' => $path,
                'reason' => $reason,
                'source' => $source,
                'imported' => $imported,
                'already_present' => $alreadyPresent,
                'skipped' => $skipped,
            ]);
        }

        return self::SUCCESS;
    }
}
