<?php
function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function money(float $n): string
{
    return number_format($n, 2);
}

function current_year(): int
{
    $row = Db::one("SELECT year FROM financial_years WHERE is_current = 1 LIMIT 1");
    return $row ? (int)$row['year'] : (int)date('Y');
}
