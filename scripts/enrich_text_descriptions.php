#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Internet History Directory
 * Textfile Description Enricher (v5)
 *
 * Memory-safe chunked version.
 *
 * Usage:
 *   php scripts/enrich_text_descriptions.php --limit=1000 --dry-run
 *   php scripts/enrich_text_descriptions.php --limit=20000
 *   php scripts/enrich_text_descriptions.php --limit=20000 --chunk=250
 *   php scripts/enrich_text_descriptions.php --limit=5000 --chunk=100 --upgrade-titles=1
 */

require __DIR__ . '/bootstrap.php';

$db = $GLOBALS['db'] ?? null;

if (!$db) {
    fwrite(STDERR, "Database handle not found in bootstrap.\n");
    exit(1);
}

$options = getopt('', [
    'limit::',
    'chunk::',
    'dry-run',
    'upgrade-titles::',
]);

$totalLimit = isset($options['limit']) ? max(1, (int)$options['limit']) : 100;
$chunkSize = isset($options['chunk']) ? max(1, min(1000, (int)$options['chunk'])) : 250;
$dryRun = isset($options['dry-run']);
$upgradeTitles = isset($options['upgrade-titles']);

echo "=== Enrich Text Descriptions (v5) ===\n";
echo "Target rows : {$totalLimit}\n";
echo "Chunk size  : {$chunkSize}\n";
echo $dryRun ? "Mode        : DRY RUN\n" : "Mode        : LIVE\n";
echo "Upgrade titles: " . ($upgradeTitles ? 'YES' : 'NO') . "\n\n";

function db_fetch_all($db, string $sql, array $params = []): array
{
    if (method_exists($db, 'fetchAll')) {
        return $db->fetchAll($sql, $params);
    }

    if (method_exists($db, 'query')) {
        $result = $db->query($sql, $params);
        return is_array($result) ? $result : [];
    }

    throw new RuntimeException('Unsupported DB wrapper: expected fetchAll() or query().');
}

function db_update_row($db, int $id, string $newDescription, ?string $newTitle = null): void
{
    if (method_exists($db, 'execute')) {
        if ($newTitle !== null) {
            $db->execute(
                "UPDATE sites SET description = ?, title = ?, updated_at = NOW() WHERE id = ?",
                [$newDescription, $newTitle, $id]
            );
        } else {
            $db->execute(
                "UPDATE sites SET description = ?, updated_at = NOW() WHERE id = ?",
                [$newDescription, $id]
            );
        }
        return;
    }

    if (method_exists($db, 'query')) {
        if ($newTitle !== null) {
            $db->query(
                "UPDATE sites SET description = ?, title = ?, updated_at = NOW() WHERE id = ?",
                [$newDescription, $newTitle, $id]
            );
        } else {
            $db->query(
                "UPDATE sites SET description = ?, updated_at = NOW() WHERE id = ?",
                [$newDescription, $id]
            );
        }
        return;
    }

    throw new RuntimeException('Unsupported DB wrapper: expected execute() or query().');
}

function normalize_encoding(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    if ($text === '') {
        return '';
    }

    if (!mb_check_encoding($text, 'UTF-8')) {
        $converted = @mb_convert_encoding($text, 'UTF-8', 'CP437,Windows-1252,ISO-8859-1,ASCII');
        if (is_string($converted) && $converted !== '') {
            $text = $converted;
        }
    }

    $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: $text;

    return $text;
}

function clean_whitespace(string $text): string
{
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return trim((string)$text);
}

function split_into_blocks(string $body): array
{
    $body = normalize_encoding($body);
    $body = clean_whitespace($body);

    if ($body === '') {
        return [];
    }

    $parts = preg_split("/\n\s*\n/", $body);
    if (!is_array($parts)) {
        return [];
    }

    $blocks = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $blocks[] = $part;
        }
    }

    return $blocks;
}

function line_has_mojibake(string $line): bool
{
    return (bool)preg_match('/[ÔûêÔöÇÔòÉÔòôÔòÜÔòÆÔòùÔòæ┬À]/u', $line);
}

function line_symbol_density_too_high(string $line): bool
{
    $len = mb_strlen($line);
    if ($len < 8) {
        return false;
    }

    preg_match_all('/[^A-Za-z0-9\s]/u', $line, $matches);
    $symbols = isset($matches[0]) ? count($matches[0]) : 0;

    return ($symbols / max(1, $len)) > 0.35;
}

function looks_like_contact_or_listing(string $line): bool
{
    if (preg_match('/\b(telnet|voice|fax|bbs list|list of|payment required|validated toll-free|accessible from the united states)\b/i', $line)) {
        return true;
    }

    if (preg_match('/\b\d{3}[-\)\s]\d{3}[-\s]\d{4}\b/', $line)) {
        return true;
    }

    if (preg_match('/\b[A-Z][A-Za-z]+\s+[A-Z]{2}\b/', $line) && preg_match('/\b(v\.32|v\.34|hst|bbs)\b/i', $line)) {
        return true;
    }

    return false;
}

function line_is_noise(string $line): bool
{
    $line = trim($line);

    if ($line === '') return true;
    if (mb_strlen($line) < 35) return true;
    if (line_has_mojibake($line)) return true;

    if (preg_match('/^[\-\=\*\#\_\/\\\\\[\]\(\)\:\;\+\~\.\%\<\>\|]{8,}$/', $line)) return true;
    if (preg_match('/(\[\%\]|\[\:\=\:\]|\/%{5,}|_{8,}|={8,}|-{8,}|\*{8,})/i', $line)) return true;
    if (preg_match('/\b(PKUNZIP|PKZIP|ZIP file|Copr\.|All Rights Reserved)\b/i', $line)) return true;
    if (preg_match('/\b(FILE|FILENAME|PATH|SUBJECT|FROM|TO|DATE)\s*:/i', $line)) return true;
    if (preg_match('/\bcontinued from previous\b/i', $line)) return true;
    if (preg_match('/^\s*(\d+[\.\)]|\-|\*)\s+/', $line)) return true;
    if (preg_match('/^\(?[A-Z]\,?\)\s+/', $line)) return true;
    if (preg_match('/^[A-Z0-9\W\s]{40,}$/', $line)) return true;
    if (preg_match('/\.(txt|zip|exe|com|arc|lzh|gif|jpg|jpeg|bmp)\b/i', $line)) return true;
    if (looks_like_contact_or_listing($line)) return true;
    if (line_symbol_density_too_high($line)) return true;

    return false;
}

function normalise_sentence_case(string $text): string
{
    $text = trim($text);
    if ($text === '') return '';

    $text = preg_replace('/\s+/', ' ', $text);

    $replacements = [
        '/\bbbs\b/i' => 'BBS',
        '/\bfidonet\b/i' => 'FidoNet',
        '/\bwwiv\b/i' => 'WWIV',
        '/\brbbs\b/i' => 'RBBS',
        '/\bqwk\b/i' => 'QWK',
        '/\bat command\b/i' => 'AT command',
        '/\bansi\b/i' => 'ANSI',
        '/\bms-dos\b/i' => 'MS-DOS',
        '/\bibm\b/i' => 'IBM',
        '/\bcp\/m\b/i' => 'CP/M',
        '/\bufo\b/i' => 'UFO',
        '/\bsysop\b/i' => 'sysop',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
}

function block_to_candidate_text(string $block): string
{
    $lines = preg_split("/\n/", $block);
    if (!is_array($lines)) return '';

    $kept = [];
    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '') continue;
        if (line_is_noise($line)) continue;
        $kept[] = $line;
    }

    if (empty($kept)) return '';

    return trim((string)preg_replace('/\s+/', ' ', implode(' ', $kept)));
}

function candidate_is_trustworthy(string $text): bool
{
    if (mb_strlen($text) < 90) return false;
    if (!preg_match('/[a-z]{3,}/i', $text)) return false;
    if (preg_match('/\b(continued from previous|all rights reserved|zip file)\b/i', $text)) return false;
    if (preg_match('/^[a-z]/', $text)) return false;
    if (preg_match('/^(and|but|so|then|because|or|also|anyways|yep|hi ho)\b/i', $text)) return false;
    if (preg_match('/^[A-Z0-9\W\s]{50,}$/', $text)) return false;
    return true;
}

function score_candidate(string $text): int
{
    $score = 0;
    $len = mb_strlen($text);

    if ($len >= 100) $score += 8;
    if ($len >= 140) $score += 8;
    if ($len <= 360) $score += 6;
    if (preg_match('/\./', $text)) $score += 10;
    if (preg_match('/\,/', $text)) $score += 2;

    if (preg_match('/\b(is|was|were|had|have|offers|explains|describes|tells|covers|shows|running|using|written|published|compiled|provides)\b/i', $text)) $score += 8;
    if (preg_match('/\b(BBS|bulletin board|sysop|modem|shareware|message base|FidoNet|Wildcat|WWIV|RBBS|QWK|telecommunications|Hayes)\b/i', $text)) $score += 8;
    if (preg_match('/\b(guide|faq|history|story|warning|application|list|magazine|tutorial|commandments|ethics|manual)\b/i', $text)) $score += 5;

    if (looks_like_contact_or_listing($text)) $score -= 25;
    if (preg_match('/\b(BY SIGNING|AFFIRM|APPLICATION|APPROVAL OF ACCESS)\b/i', $text)) $score -= 20;
    if (preg_match('/\b(IBM|RAM|RS232|MB hard drive|baud rate)\b/i', $text)) $score -= 10;
    if (preg_match('/\b(Thou shalt|Verily)\b/i', $text)) $score -= 18;
    if (preg_match('/\b(anyways|lemme|yep|hi ho|lol|dweeb)\b/i', $text)) $score -= 16;
    if (preg_match('/\bcaller logs on\b/i', $text)) $score -= 22;
    if (preg_match('/\bquestions and answers\b/i', $text)) $score += 6;
    if (preg_match('/\bthe following is reprinted\b/i', $text)) $score -= 8;

    return $score;
}

function find_best_paragraph(string $body): ?string
{
    $blocks = split_into_blocks($body);
    if (empty($blocks)) return null;

    $bestText = null;
    $bestScore = -9999;

    foreach ($blocks as $block) {
        $candidate = block_to_candidate_text($block);
        if ($candidate === '') continue;
        if (!candidate_is_trustworthy($candidate)) continue;

        $score = score_candidate($candidate);
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestText = $candidate;
        }
    }

    if ($bestText === null || $bestScore < 12) return null;

    return $bestText;
}

function extract_first_sentence(string $text): string
{
    $text = trim($text);
    if ($text === '') return '';

    if (preg_match('/^(.{60,240}?[.!?])(?:\s|$)/u', $text, $m)) {
        return trim($m[1]);
    }

    if (mb_strlen($text) > 220) {
        $cut = mb_substr($text, 0, 220);
        $cut = preg_replace('/\s+\S*$/u', '', $cut);
        return rtrim((string)$cut, " ,;:-") . '.';
    }

    return rtrim($text, " ,;:-") . '.';
}

function normalize_excerpt(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim((string)$text);
    $text = strtr($text, ["BBS's" => 'BBSes', "bbs's" => 'BBSes', "Bbs" => 'BBS']);
    return normalise_sentence_case($text);
}

function summarise_by_title_patterns(string $title, string $candidate): ?string
{
    $titleLower = mb_strtolower($title);
    $combined = $titleLower . ' ' . mb_strtolower($candidate);

    if (preg_match('/\b(at command|hayes)\b/i', $title)) {
        return 'An explanation of the Hayes AT command set used to control dial-up modems.';
    }
    if (preg_match('/\bfaq\b/i', $title)) {
        return 'A period FAQ covering common questions about running, using, and understanding bulletin board systems.';
    }
    if (preg_match('/\bapplication\b/i', $title)) {
        return 'A membership application from the BBS era, showing how access, identity, and user conduct were handled on private systems.';
    }
    if (preg_match('/\bcommandments\b|\bethics\b/i', $title)) {
        return 'A snapshot of BBS etiquette, laying out the rules, expectations, and habits that shaped life on early bulletin board systems.';
    }
    if (preg_match('/\blist\b/i', $title) && preg_match('/\b(bbs|modem|toll-free|v\.32|v\.34|hst)\b/i', $candidate)) {
        return 'A period list documenting bulletin board systems, modem standards, or dial-up access details from the era.';
    }
    if (preg_match('/\bguide\b|\btutorial\b/i', $title) && preg_match('/\b(bbs|modem|ansi|terminal|wildcat|sysop)\b/i', $candidate)) {
        return 'A practical guide to using or running a bulletin board system, written for the dial-up world it came from.';
    }
    if (preg_match('/\b(wildcat|rbbs|wwiv|remote access|vision x|firstclass)\b/i', $combined)) {
        return 'A technical guide or reference covering BBS software, setup, and day-to-day operation in the dial-up era.';
    }
    if (preg_match('/\b(modem|baud|communication|compatibility|features|prices)\b/i', $titleLower)) {
        return 'A technical note from the dial-up era explaining modem behaviour, standards, or practical buying and setup considerations.';
    }
    if (preg_match('/\b(history|story|from the beginning|goodbye|death|rant|warning|busted)\b/i', $titleLower)) {
        return 'A first-hand account reflecting on bulletin board culture, sysop life, or the rise and decline of a particular system.';
    }
    if (preg_match('/\bmagazine\b/i', $titleLower)) {
        return 'A magazine-style piece introducing bulletin board concepts, jargon, and everyday practices for readers of the time.';
    }
    if (preg_match('/\bbbs\b|\bbulletin board\b/i', $titleLower)) {
        return 'A BBS-era text capturing the language, culture, and practical realities of life on early bulletin board systems.';
    }

    return null;
}

function summarise_by_candidate_patterns(string $candidate): ?string
{
    if (preg_match('/\bcaller logs on\b/i', $candidate)) {
        return 'A reflection on the routines, frustrations, and social rhythms that shaped everyday life for sysops and callers on early BBSes.';
    }
    if (preg_match('/\bthe following are\b|\bthe following is\b|\bthis file is\b/i', $candidate)) {
        return 'A period text outlining the ideas, practices, or concerns that circulated through bulletin board culture.';
    }
    if (preg_match('/\bquestions? and answers?\b|\bq:\b/i', $candidate)) {
        return 'A period FAQ preserving common questions and practical answers from the bulletin board world.';
    }
    if (preg_match('/\bansi terminal emulation\b|\bterminal emulation\b/i', $candidate)) {
        return 'A guide explaining terminal settings and display standards needed to use dial-up bulletin board systems properly.';
    }
    if (preg_match('/\bwarning\b|\blegal and safe\b/i', $candidate)) {
        return 'A cautionary text reflecting the risks, disclaimers, and self-policing culture that often surrounded underground BBS material.';
    }
    if (preg_match('/\bmodem\b|\bbaud\b|\bcommand set\b|\battention\b/i', $candidate)) {
        return 'A technical explanation focused on modem operation, connection standards, or dial-up communication practices.';
    }
    if (preg_match('/\bsysop\b|\bbulletin board\b|\bbbs\b/i', $candidate)) {
        return 'A period document capturing the workings and culture of bulletin board systems in the dial-up era.';
    }

    return null;
}

function build_curated_description(string $title, string $candidate): ?string
{
    $candidate = normalize_excerpt($candidate);
    if ($candidate === '') return null;

    $summary = summarise_by_title_patterns($title, $candidate);
    if ($summary !== null) return $summary;

    $summary = summarise_by_candidate_patterns($candidate);
    if ($summary !== null) return $summary;

    $sentence = normalize_excerpt(extract_first_sentence($candidate));
    if ($sentence === '') return null;
    if (preg_match('/^[a-z]/', $sentence)) return null;
    if (preg_match('/^(And|But|So|Then|Because|Or|Also|Anyways|Yep|Hi ho)\b/', $sentence)) return null;
    if (preg_match('/^\d+[\.\)]/', $sentence)) return null;
    if (looks_like_contact_or_listing($sentence)) return null;
    if (preg_match('/\b(anyways|lemme|yep|hi ho|lol)\b/i', $sentence)) return null;
    if (mb_strlen($sentence) < 70 || mb_strlen($sentence) > 240) return null;
    if (!preg_match('/[.!?]$/', $sentence)) $sentence .= '.';

    return $sentence;
}

function title_is_weak(string $title): bool
{
    $title = trim($title);
    if ($title === '') return true;
    if (preg_match('/^[a-z0-9_\-]+\.(txt|doc|nfo)$/i', $title)) return true;
    if (preg_match('/^[A-Z0-9_\-]{6,}$/', $title)) return true;
    return false;
}

function upgrade_title_if_safe(string $title, string $body, ?string $sourceKey = null): ?string
{
    $original = trim($title);
    if ($original === '') return null;

    $sourceProbe = strtolower((string)$sourceKey . ' ' . $original);

    if (preg_match('/wwiv\s*news|wwiv(\d{2})(\d{2})/i', $sourceProbe, $m)) {
        if (isset($m[1], $m[2])) {
            $yy = (int)$m[1];
            $mm = (int)$m[2];
            if ($mm >= 1 && $mm <= 12) {
                $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                return 'WWIV News (' . $months[$mm] . ' 19' . str_pad((string)$yy, 2, '0', STR_PAD_LEFT) . ')';
            }
        }
        return 'WWIV News';
    }

    if (!title_is_weak($original)) return null;
    if (preg_match('/\bfidonet\b/i', $body)) return 'FidoNet Document';
    if (preg_match('/\bfaq\b/i', $body)) return 'BBS FAQ';
    if (preg_match('/\bguide\b/i', $body)) return 'BBS Guide';
    if (preg_match('/\blog\b/i', $body)) return 'BBS Log';

    return null;
}

function description_already_good(?string $description): bool
{
    $description = trim((string)$description);
    if ($description === '') return false;
    if (preg_match('/^Historical .* document:/i', $description)) return false;
    if (preg_match('/^Imported from local textfiles dump:/i', $description)) return false;
    if (mb_strlen($description) < 90) return false;
    return true;
}

function fetch_candidate_chunk($db, int $lastId, int $chunkSize): array
{
    $sql = "
        SELECT id, title, slug, description, body_text, source_type, source_key
        FROM sites
        WHERE id > ?
          AND content_type = 'text'
          AND is_active = 1
          AND (
                description IS NULL
                OR description = ''
                OR description LIKE 'Historical % document:%'
                OR description LIKE 'Imported from local textfiles dump:%'
                OR LENGTH(description) < 120
              )
        ORDER BY id ASC
        LIMIT " . (int)$chunkSize;

    return db_fetch_all($db, $sql, [$lastId]);
}

$reviewed = 0;
$updated = 0;
$skipped = 0;
$lastId = 0;

while ($reviewed < $totalLimit) {
    $rows = fetch_candidate_chunk($db, $lastId, min($chunkSize, $totalLimit - $reviewed));
    if (empty($rows)) break;

    echo "Processing chunk after ID {$lastId} (" . count($rows) . " row(s))...\n";

    foreach ($rows as $row) {
        $reviewed++;

        $id = (int)($row['id'] ?? 0);
        $title = trim((string)($row['title'] ?? ''));
        $description = (string)($row['description'] ?? '');
        $body = (string)($row['body_text'] ?? '');
        $sourceKey = (string)($row['source_key'] ?? '');

        $lastId = $id;

        if ($id <= 0 || $body === '') {
            $skipped++;
            continue;
        }

        if (description_already_good($description)) {
            echo "ID {$id} skipped: description already looks curated\n";
            $skipped++;
            continue;
        }

        $bestParagraph = find_best_paragraph($body);
        if ($bestParagraph === null) {
            echo "ID {$id} skipped: no trustworthy paragraph found\n";
            $skipped++;
            continue;
        }

        $newDescription = build_curated_description($title, $bestParagraph);
        if ($newDescription === null) {
            echo "ID {$id} skipped: paragraph was still too weak\n";
            $skipped++;
            continue;
        }

        $newTitle = null;
        if ($upgradeTitles) {
            $newTitle = upgrade_title_if_safe($title, $body, $sourceKey);
            if ($newTitle !== null && trim($newTitle) === $title) {
                $newTitle = null;
            }
        }

        echo "ID {$id}\n";
        echo "OLD TITLE: " . ($title !== '' ? $title : '[null]') . "\n";
        echo "OLD DESC : " . ($description !== '' ? $description : '[null]') . "\n";
        echo "NEW DESC : {$newDescription}\n";
        if ($newTitle !== null) echo "NEW TITLE: {$newTitle}\n";
        echo str_repeat('-', 80) . "\n";

        if (!$dryRun) {
            db_update_row($db, $id, $newDescription, $newTitle);
            $updated++;
        }

        if ($reviewed >= $totalLimit) break;
    }

    unset($rows);
    gc_collect_cycles();

    echo "Chunk complete. Reviewed so far: {$reviewed}, Updated so far: " . ($dryRun ? '0 (dry-run)' : (string)$updated) . ", Skipped so far: {$skipped}\n\n";
}

echo "\nReviewed: {$reviewed}\n";
echo "Updated : " . ($dryRun ? '0 (dry-run preview only)' : (string)$updated) . "\n";
echo "Skipped : {$skipped}\n";
