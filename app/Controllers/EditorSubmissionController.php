<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Models\Site;
use App\Models\Submission;

class EditorSubmissionController extends Controller
{
    public function dashboard(): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $categoryModel = new Category($this->db);
        $importModel = new ImportBatch($this->db);
        $auditLog = new AuditLog($this->db);

        $this->view('editor/dashboard', [
            'pageTitle' => 'Editor Dashboard',
            'pendingCount' => $submissionModel->pendingCount(),
            'siteCount' => $siteModel->editorCount(),
            'categoryCount' => count($categoryModel->allForEditor()),
            'importBatchCount' => count($importModel->all()),
            'duplicateCount' => $siteModel->duplicateGroupCount(),
            'checked24hCount' => $siteModel->checkedWithinHours(24),
            'failingCheckCount' => $siteModel->deadCountByLatestCheck(),
            'recentAudit' => $auditLog->recent(8),
            'recentSites' => $siteModel->recentUpdated(8),
        ]);
    }

    public function index(): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $source = trim((string) ($_GET['source'] ?? ''));
        $showWibyOnly = $source === 'wiby';

        $this->view('editor/submissions/index', [
            'pageTitle' => 'Moderate Submissions',
            'submissions' => $submissionModel->pendingList($showWibyOnly),
            'showWibyOnly' => $showWibyOnly,
        ]);
    }

    public function discover(): void
    {
        $this->requireEditor();

        $categoryModel = new Category($this->db);

        $this->view('editor/submissions/discover', [
            'pageTitle' => 'Discover Wiby Candidates',
            'categories' => $categoryModel->allActive(),
            'defaults' => [
                'query' => trim((string) ($_GET['q'] ?? 'bulletin board systems')),
                'limit' => max(1, min(25, (int) ($_GET['limit'] ?? 10))),
                'category_id' => (int) ($_GET['category_id'] ?? 0),
            ],
            'result' => null,
        ]);
    }

    public function runDiscovery(): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $submissionModel = new Submission($this->db);
        $categoryModel = new Category($this->db);

        $query = trim((string) ($_POST['query'] ?? ''));
        $limit = max(1, min(25, (int) ($_POST['limit'] ?? 10)));
        $categoryId = (int) ($_POST['category_id'] ?? 0);

        $categories = $categoryModel->allActive();

        if ($query === '') {
            flash('error', 'Enter a Wiby query first.');
            $this->redirect('/editor/submissions/discover');
        }

        if ($categoryId <= 0) {
            flash('error', 'Choose a target category first.');
            $this->redirect('/editor/submissions/discover');
        }

        $category = $categoryModel->findById($categoryId);
        if (!$category) {
            flash('error', 'Selected category was not found.');
            $this->redirect('/editor/submissions/discover');
        }

        $result = $this->discoverWibyCandidates($submissionModel, $query, $limit, $category);

        $this->view('editor/submissions/discover', [
            'pageTitle' => 'Discover Wiby Candidates',
            'categories' => $categories,
            'defaults' => [
                'query' => $query,
                'limit' => $limit,
                'category_id' => $categoryId,
            ],
            'result' => $result,
        ]);
    }

    public function show(int $id): void
    {
        $this->requireEditor();

        $submissionModel = new Submission($this->db);
        $categoryModel = new Category($this->db);
        $submission = $submissionModel->findById($id);

        if (!$submission) {
            $this->notFound('Submission not found.');
            return;
        }

        $this->view('editor/submissions/show', [
            'pageTitle' => 'Review Submission',
            'submission' => $submission,
            'categories' => $categoryModel->allActive(),
        ]);
    }

    public function approve(int $id): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $auditLog = new AuditLog($this->db);

        $submission = $submissionModel->findById($id);
        if (!$submission || $submission['status'] !== 'pending') {
            flash('error', 'Submission could not be approved.');
            $this->redirect('/editor/submissions');
        }

        $categoryId = (int) ($_POST['category_id'] ?? $submission['proposed_category_id']);
        if ($categoryId <= 0) {
            flash('error', 'Please choose a category before approving.');
            $this->redirect('/editor/submissions/' . $id);
        }

        $submission['proposed_category_id'] = $categoryId;
        $submission['description'] = sanitize_plain_text((string) ($submission['description'] ?? ''));
        $duplicate = $siteModel->findByNormalizedUrl(normalize_url($submission['url']));
        if ($duplicate) {
            flash('error', 'A live site with this normalized URL already exists.');
            $this->redirect('/editor/submissions/' . $id);
        }

        $userId = (int) current_user()['id'];

        try {
            $this->db->beginTransaction();
            $siteId = $siteModel->createFromSubmission($submission);
            $submissionModel->markApproved($id, $userId, $siteId);
            $auditLog->log($userId, 'submission', $id, 'approved', ['site_id' => $siteId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Submission approval failed. No changes were saved.');
            $this->redirect('/editor/submissions/' . $id);
        }

        flash('success', 'Submission approved and published.');
        $this->redirect('/editor/submissions');
    }

    public function reject(int $id): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $submissionModel = new Submission($this->db);
        $auditLog = new AuditLog($this->db);
        $submission = $submissionModel->findById($id);

        if (!$submission || $submission['status'] !== 'pending') {
            flash('error', 'Submission could not be rejected.');
            $this->redirect('/editor/submissions');
        }

        $userId = (int) current_user()['id'];

        try {
            $this->db->beginTransaction();
            $submissionModel->markRejected($id, $userId);
            $auditLog->log($userId, 'submission', $id, 'rejected');
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Submission rejection failed. No changes were saved.');
            $this->redirect('/editor/submissions');
        }

        flash('success', 'Submission rejected.');
        $this->redirect('/editor/submissions');
    }

    public function bulk(): void
    {
        $this->requireEditor();
        $this->verifyCsrf();

        $ids = array_values(array_filter(array_map('intval', $_POST['submission_ids'] ?? [])));
        $action = trim((string) ($_POST['bulk_action'] ?? ''));

        if (!$ids || !in_array($action, ['approve', 'reject'], true)) {
            flash('error', 'Choose at least one submission and a valid bulk action.');
            $this->redirect('/editor/submissions');
        }

        $submissionModel = new Submission($this->db);
        $siteModel = new Site($this->db);
        $auditLog = new AuditLog($this->db);
        $userId = (int) current_user()['id'];

        $processed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $submission = $submissionModel->findById($id);
            if (!$submission || $submission['status'] !== 'pending') {
                $skipped++;
                continue;
            }

            if ($action === 'reject') {
                try {
                    $this->db->beginTransaction();
                    $submissionModel->markRejected($id, $userId);
                    $auditLog->log($userId, 'submission', $id, 'rejected');
                    $this->db->commit();
                    $processed++;
                } catch (\Throwable $e) {
                    $this->db->rollBack();
                    $skipped++;
                }
                continue;
            }

            $categoryId = (int) $submission['proposed_category_id'];
            if ($categoryId <= 0) {
                $skipped++;
                continue;
            }

            $duplicate = $siteModel->findByNormalizedUrl(normalize_url($submission['url']));
            if ($duplicate) {
                $skipped++;
                continue;
            }

            $submission['proposed_category_id'] = $categoryId;
            $submission['description'] = sanitize_plain_text((string) ($submission['description'] ?? ''));

            try {
                $this->db->beginTransaction();
                $siteId = $siteModel->createFromSubmission($submission);
                $submissionModel->markApproved($id, $userId, $siteId);
                $auditLog->log($userId, 'submission', $id, 'approved', ['site_id' => $siteId, 'mode' => 'bulk']);
                $this->db->commit();
                $processed++;
            } catch (\Throwable $e) {
                $this->db->rollBack();
                $skipped++;
            }
        }

        flash('success', 'Bulk action complete. Processed: ' . $processed . '. Skipped: ' . $skipped . '.');
        $this->redirect('/editor/submissions');
    }

    private function discoverWibyCandidates(Submission $submissionModel, string $query, int $limit, array $category): array
    {
        $searchUrl = 'https://wiby.me/?q=' . urlencode($query);
        $wibyHtml = $this->fetchRemoteHtml($searchUrl);
        $profile = $this->buildDiscoveryProfile($query, $category);

        if ($wibyHtml === null) {
            return [
                'ok' => false,
                'query' => $query,
                'limit' => $limit,
                'category' => $category,
                'profile' => $profile,
                'search_url' => $searchUrl,
                'message' => 'Could not fetch Wiby search results.',
                'lines' => [],
                'inserted' => 0,
                'skipped_duplicate' => 0,
                'skipped_fetch' => 0,
                'skipped_invalid' => 0,
                'skipped_weak' => 0,
                'skipped_secondary' => 0,
                'skipped_file_links' => 0,
                'candidate_count' => 0,
            ];
        }

        $extraction = $this->extractWibyResultUrls($wibyHtml, $limit);
        $resultUrls = $extraction['urls'];
        $lines = $extraction['lines'];
        $inserted = 0;
        $skippedDuplicate = 0;
        $skippedFetch = 0;
        $skippedInvalid = 0;
        $skippedWeak = 0;
        $skippedSecondary = (int) ($extraction['skipped_secondary'] ?? 0);
        $skippedFileLinks = (int) ($extraction['skipped_file_links'] ?? 0);

        foreach ($resultUrls as $url) {
            $normalizedUrl = normalize_url($url);

            if ($normalizedUrl === '') {
                $lines[] = ['type' => 'warning', 'message' => 'Skipped invalid URL: ' . $url];
                $skippedInvalid++;
                continue;
            }

            if ($submissionModel->existsInSitesByNormalizedUrl($normalizedUrl)) {
                $lines[] = ['type' => 'warning', 'message' => 'Skipped existing site: ' . $normalizedUrl];
                $skippedDuplicate++;
                continue;
            }

            if ($submissionModel->existsInSubmissionsByNormalizedUrl($normalizedUrl)) {
                $lines[] = ['type' => 'warning', 'message' => 'Skipped existing submission: ' . $normalizedUrl];
                $skippedDuplicate++;
                continue;
            }

            $pageHtml = $this->fetchRemoteHtml($url);
            if ($pageHtml === null) {
                $lines[] = ['type' => 'warning', 'message' => 'Skipped fetch failure: ' . $url];
                $skippedFetch++;
                usleep(300000);
                continue;
            }

            [$title, $description] = $this->extractPageMeta($pageHtml, $url);
            $pageSnippet = first_meaningful_paragraph($pageHtml, 320);

            if ($description === '' || text_length($description) < 30) {
                $description = $pageSnippet;
            }

            if ($description === '') {
                $description = 'Auto-discovered candidate site pending editorial review.';
            }

            $evaluation = $this->evaluateDiscoveryCandidate(
                $url,
                $title,
                $description,
                $pageSnippet,
                $category,
                $profile
            );

            if (empty($evaluation['ok'])) {
                $reason = $evaluation['reason'] ?? 'weak topical match';
                $resourceType = $evaluation['resource_type'] ?? 'unclear';
                $score = (int) ($evaluation['score'] ?? 0);
                $lines[] = [
                    'type' => 'warning',
                    'message' => 'Skipped weak match (' . $resourceType . ', score ' . $score . '): ' . $normalizedUrl . ' — ' . $reason,
                ];
                $skippedWeak++;
                usleep(300000);
                continue;
            }

            $submissionModel->createAutoDiscovered([
                'proposed_category_id' => (int) $category['id'],
                'title' => $title,
                'url' => $url,
                'normalized_url' => $normalizedUrl,
                'description' => $description,
                'notes' => implode(PHP_EOL, [
                    'Auto-discovered via Wiby.',
                    'Query: ' . $query,
                    'Imported at: ' . date('Y-m-d H:i:s'),
                    'Source URL: ' . $searchUrl,
                    'Resource type: ' . ($evaluation['resource_type'] ?? 'unclear'),
                    'Relevance score: ' . (int) ($evaluation['score'] ?? 0),
                    'Matched signals: ' . (!empty($evaluation['matched']) ? implode(', ', $evaluation['matched']) : 'none'),
                    'Snippet: ' . text_substr($pageSnippet, 0, 240),
                ]),
            ]);

            $lines[] = [
                'type' => 'success',
                'message' => 'Inserted (' . ($evaluation['resource_type'] ?? 'unclear') . ', score ' . (int) ($evaluation['score'] ?? 0) . '): ' . $normalizedUrl,
            ];
            $inserted++;
            usleep(300000);
        }

        $message = $inserted > 0
            ? 'Wiby import complete. ' . $inserted . ' candidate(s) added to the submissions queue.'
            : 'Wiby import finished with no new candidates added.';

        return [
            'ok' => true,
            'query' => $query,
            'limit' => $limit,
            'category' => $category,
            'profile' => $profile,
            'search_url' => $searchUrl,
            'message' => $message,
            'lines' => $lines,
            'inserted' => $inserted,
            'skipped_duplicate' => $skippedDuplicate,
            'skipped_fetch' => $skippedFetch,
            'skipped_invalid' => $skippedInvalid,
            'skipped_weak' => $skippedWeak,
            'skipped_secondary' => $skippedSecondary,
            'skipped_file_links' => $skippedFileLinks,
            'candidate_count' => count($resultUrls),
        ];
    }

    private function buildDiscoveryProfile(string $query, array $category): array
    {
        $path = strtolower((string) ($category['path'] ?? ''));
        $queryLower = strtolower($query);
        $base = [
            'name' => 'General history',
            'required_any' => [],
            'positive' => $this->extractMeaningfulTerms($query),
            'resource_bias' => 'mixed',
            'minimum_score' => 2,
        ];

        if (str_contains($path, 'bbs') || preg_match('/\bbbs\b|bulletin board/i', $query)) {
            return [
                'name' => 'BBS / Bulletin boards',
                'required_any' => ['bbs', 'bulletin board', 'bulletin boards'],
                'positive' => ['bbs', 'bulletin board', 'bulletin boards', 'ansi', 'sysop', 'fidonet', 'telnet', 'dial-up', 'board', 'message board'],
                'resource_bias' => 'primary',
                'minimum_score' => 3,
            ];
        }

        if (str_contains($path, 'yahoo') || preg_match('/\byahoo\b/i', $queryLower)) {
            return [
                'name' => 'Yahoo history',
                'required_any' => ['yahoo'],
                'positive' => ['yahoo', 'geocities', 'directory', 'directories', 'altavista', 'search', 'portal'],
                'resource_bias' => 'mixed',
                'minimum_score' => 3,
            ];
        }

        if (str_contains($path, 'ftp') || preg_match('/\bftp\b|file sharing/i', $queryLower)) {
            return [
                'name' => 'FTP / file archives',
                'required_any' => ['ftp', 'file archive', 'archives'],
                'positive' => ['ftp', 'archive', 'archives', 'mirror', 'download', 'wuarchive', 'files'],
                'resource_bias' => 'primary',
                'minimum_score' => 3,
            ];
        }

        if (str_contains($path, 'usenet') || preg_match('/usenet|newsgroup/i', $queryLower)) {
            return [
                'name' => 'Usenet / newsgroups',
                'required_any' => ['usenet', 'newsgroup', 'newsgroups'],
                'positive' => ['usenet', 'newsgroup', 'newsgroups', 'nntp', 'alt.', 'comp.', 'rec.', 'newsreader'],
                'resource_bias' => 'primary',
                'minimum_score' => 3,
            ];
        }

        return $base;
    }

    private function extractMeaningfulTerms(string $query): array
    {
        $parts = preg_split('/[^a-z0-9]+/i', strtolower($query)) ?: [];
        $stop = ['the', 'and', 'for', 'with', 'from', 'into', 'this', 'that', 'these', 'those', 'about', 'history'];
        $terms = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || strlen($part) < 3 || in_array($part, $stop, true)) {
                continue;
            }
            $terms[] = $part;
        }

        return array_values(array_unique($terms));
    }

    private function evaluateDiscoveryCandidate(string $url, string $title, string $description, string $pageSnippet, array $category, array $profile): array
    {
        $combined = strtolower(implode(PHP_EOL, [$title, $description, $pageSnippet, $url, (string) ($category['path'] ?? '')]));
        $matched = [];
        $score = 0;

        foreach (($profile['positive'] ?? []) as $signal) {
            $signal = strtolower((string) $signal);
            if ($signal !== '' && str_contains($combined, $signal)) {
                $matched[] = $signal;
                $score += strlen($signal) >= 6 ? 2 : 1;
            }
        }

        $resourceType = $this->classifyDiscoveryResourceType($url, $title, $description, $pageSnippet);

        if (($resourceType === 'article/essay') && (($profile['resource_bias'] ?? 'mixed') === 'primary')) {
            $score -= 1;
        }

        $requiredAny = $profile['required_any'] ?? [];
        if (!empty($requiredAny)) {
            $hasRequired = false;
            foreach ($requiredAny as $required) {
                if (str_contains($combined, strtolower((string) $required))) {
                    $hasRequired = true;
                    break;
                }
            }

            if (!$hasRequired) {
                return [
                    'ok' => false,
                    'reason' => 'missing required topical signals',
                    'resource_type' => $resourceType,
                    'score' => $score,
                    'matched' => array_values(array_unique($matched)),
                ];
            }
        }

        $minimumScore = (int) ($profile['minimum_score'] ?? 2);
        if ($score < $minimumScore) {
            return [
                'ok' => false,
                'reason' => 'relevance score below threshold',
                'resource_type' => $resourceType,
                'score' => $score,
                'matched' => array_values(array_unique($matched)),
            ];
        }

        return [
            'ok' => true,
            'resource_type' => $resourceType,
            'score' => $score,
            'matched' => array_values(array_unique($matched)),
        ];
    }

    private function classifyDiscoveryResourceType(string $url, string $title, string $description, string $pageSnippet): string
    {
        $combined = strtolower(implode(PHP_EOL, [$url, $title, $description, $pageSnippet]));

        if (preg_match('~/post/|/blog/|/article/|/articles/|/essay/|/essays/~i', $url)
            || preg_match('/\b(article|essay|notes|blog|post|story|retrospective|history of)\b/i', $combined)) {
            return 'article/essay';
        }

        if (preg_match('/\b(archive|archives|mirror|repository|collection|index|directory|listing|catalog)\b/i', $combined)) {
            return 'archive/resource';
        }

        if (preg_match('/\b(homepage|official|service|bbs|site|portal|server|telnet)\b/i', $combined)) {
            return 'historical-site';
        }

        return 'unclear';
    }
    private function fetchRemoteHtml(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                    'User-Agent: InternetHistoryDirectoryBot/1.0 (+https://wiby.me/about/guide.html)',
                    'Accept: text/html,application/xhtml+xml',
                    'Connection: close',
                ]),
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            return null;
        }

        $html = trim((string) $html);

        return $html !== '' ? $html : null;
    }

    private function extractWibyResultUrls(string $html, int $limit = 25): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        if (!$dom->loadHTML($html)) {
            return [
                'urls' => [],
                'lines' => [],
                'skipped_secondary' => 0,
                'skipped_file_links' => 0,
            ];
        }

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//a[@href]');
        if (!$nodes) {
            return [
                'urls' => [],
                'lines' => [],
                'skipped_secondary' => 0,
                'skipped_file_links' => 0,
            ];
        }

        $urls = [];
        $lines = [];
        $seen = [];
        $skippedSecondary = 0;
        $skippedFileLinks = 0;

        foreach ($nodes as $node) {
            if (count($urls) >= $limit) {
                break;
            }

            $href = trim((string) $node->getAttribute('href'));
            $text = trim(preg_replace('/\s+/u', ' ', (string) ($node->textContent ?? '')));

            if ($href === '' || !preg_match('~^https?://~i', $href)) {
                continue;
            }

            if (stripos($href, 'wiby.me') !== false) {
                continue;
            }

            if ($text === '' || strcasecmp($text, 'Find more...') === 0) {
                continue;
            }

            if ($this->isBlockedDiscoveryUrl($href)) {
                $skippedFileLinks++;
                $lines[] = [
                    'type' => 'warning',
                    'message' => 'Skipped file link before scoring: ' . $href,
                ];
                continue;
            }

            if ($this->looksLikeSecondaryWibyLink($href, $text)) {
                $skippedSecondary++;
                continue;
            }

            $normalizedHref = normalize_url($href);
            if ($normalizedHref === '' || isset($seen[$normalizedHref])) {
                continue;
            }

            $seen[$normalizedHref] = true;
            $urls[] = $href;
        }

        return [
            'urls' => $urls,
            'lines' => $lines,
            'skipped_secondary' => $skippedSecondary,
            'skipped_file_links' => $skippedFileLinks,
        ];
    }

    private function looksLikeSecondaryWibyLink(string $href, string $text): bool
    {
        $cleanText = trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $cleanText = preg_replace('/\s+/u', ' ', $cleanText) ?: '';
        $cleanHref = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($cleanText === '') {
            return true;
        }

        if (preg_match('~^https?://~i', $cleanText)) {
            return true;
        }

        if (preg_match('~\.(txt|nfo|diz|asc|jpg|jpeg|png|gif|webp|svg|pdf|zip|rar|7z|tar|gz|bz2|mp3|ogg|wav|mp4|avi|mov|wmv|exe|msi|iso)$~i', $cleanText)) {
            return true;
        }

        $titleLikeText = strtolower(trim($cleanText, " \t\n\r\0\x0B/"));
        if ($titleLikeText === '' || $titleLikeText === strtolower($cleanHref)) {
            return true;
        }

        if (preg_match('~^(index of|parent directory|\.\./)~i', $cleanText)) {
            return true;
        }

        return false;
    }

    private function isBlockedDiscoveryUrl(string $url): bool
    {
        $path = strtolower(rawurldecode((string) parse_url($url, PHP_URL_PATH)));

        if ($path === '') {
            return false;
        }

        return (bool) preg_match(
            '~\.(txt|nfo|diz|asc|jpg|jpeg|png|gif|webp|svg|pdf|zip|rar|7z|tar|gz|bz2|mp3|ogg|wav|mp4|avi|mov|wmv|exe|msi|iso)$~i',
            $path
        );
    }

    private function extractPageMeta(string $html, string $fallbackUrl): array
    {
        $title = '';
        $description = '';

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        if ($dom->loadHTML($html)) {
            $xpath = new \DOMXPath($dom);
            $titleNodes = $xpath->query('//title');
            if ($titleNodes && $titleNodes->length > 0) {
                $title = trim((string) ($titleNodes->item(0)->textContent ?? ''));
            }

            $metaNodes = $xpath->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]/@content');
            if ($metaNodes && $metaNodes->length > 0) {
                $description = trim((string) ($metaNodes->item(0)->nodeValue ?? ''));
            }
        }

        if ($title === '') {
            $title = $fallbackUrl;
        }

        $title = sanitize_plain_text($title);
        $description = sanitize_plain_text($description);

        $title = preg_replace('/\s+/u', ' ', $title) ?: $fallbackUrl;
        $description = preg_replace('/\s+/u', ' ', $description) ?: '';

        return [
            text_substr(trim($title), 0, 191),
            text_substr(trim($description), 0, 4000),
        ];
    }
}
