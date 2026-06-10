<?php

function ensure_article_source_columns(mysqli $conn): void
{
    $columns = [];
    $result = $conn->query('SHOW COLUMNS FROM articles');
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = true;
    }

    $changes = [
        'source_type' => "ALTER TABLE articles ADD COLUMN source_type enum('internal','external') NOT NULL DEFAULT 'internal' AFTER status",
        'external_url' => 'ALTER TABLE articles ADD COLUMN external_url text DEFAULT NULL AFTER source_type',
        'source_name' => 'ALTER TABLE articles ADD COLUMN source_name varchar(160) DEFAULT NULL AFTER external_url',
        'embed_url' => 'ALTER TABLE articles ADD COLUMN embed_url text DEFAULT NULL AFTER source_name',
    ];

    foreach ($changes as $column => $sql) {
        if (empty($columns[$column])) {
            $conn->query($sql);
        }
    }
}

function normalize_article_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function article_source_name(array $article): string
{
    $sourceName = trim((string) ($article['source_name'] ?? ''));
    if ($sourceName !== '') {
        return $sourceName;
    }

    $externalUrl = trim((string) ($article['external_url'] ?? ''));
    if ($externalUrl === '') {
        return 'TemanPulih';
    }

    $host = parse_url($externalUrl, PHP_URL_HOST);
    return $host ? preg_replace('/^www\./', '', $host) : 'Sumber eksternal';
}
