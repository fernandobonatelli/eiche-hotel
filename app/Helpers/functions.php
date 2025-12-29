<?php
/**
 * Eiche Hotel - Funções Auxiliares
 * Compatível com PHP 8.x
 * 
 * @version 2.0
 */

declare(strict_types=1);

namespace Eiche\Helpers;

/**
 * Formata valor monetário brasileiro
 */
function formatMoney(float $value, bool $showSymbol = true): string
{
    $formatted = number_format($value, 2, ',', '.');
    return $showSymbol ? 'R$ ' . $formatted : $formatted;
}

/**
 * Converte data do formato brasileiro para ISO
 * @param string $date Data no formato dd/mm/yyyy
 * @return string Data no formato yyyy-mm-dd
 */
function dateToIso(string $date): string
{
    if (empty($date) || strlen($date) < 10) {
        return '';
    }
    
    $parts = explode('/', $date);
    if (count($parts) !== 3) {
        return $date;
    }
    
    return sprintf('%s-%s-%s', $parts[2], $parts[1], $parts[0]);
}

/**
 * Converte data do formato ISO para brasileiro
 * @param string $date Data no formato yyyy-mm-dd
 * @return string Data no formato dd/mm/yyyy
 */
function dateToBr(string $date): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    
    $dateTime = \DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));
    return $dateTime ? $dateTime->format('d/m/Y') : $date;
}

/**
 * Converte data/hora do formato ISO para brasileiro
 */
function dateTimeToBr(string $dateTime): string
{
    if (empty($dateTime)) {
        return '';
    }
    
    $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
    return $dt ? $dt->format('d/m/Y H:i') : $dateTime;
}

/**
 * Retorna o dia da semana
 */
function dayOfWeek(string $date, bool $short = false): string
{
    $days = [
        0 => ['Domingo', 'Dom'],
        1 => ['Segunda-feira', 'Seg'],
        2 => ['Terça-feira', 'Ter'],
        3 => ['Quarta-feira', 'Qua'],
        4 => ['Quinta-feira', 'Qui'],
        5 => ['Sexta-feira', 'Sex'],
        6 => ['Sábado', 'Sáb']
    ];
    
    $dateTime = new \DateTime($date);
    $dayNum = (int) $dateTime->format('w');
    
    return $days[$dayNum][$short ? 1 : 0];
}

/**
 * Retorna o nome do mês
 */
function monthName(int $month, string $format = 'full'): string
{
    $months = [
        1 => ['Janeiro', 'Jan', 'JANEIRO'],
        2 => ['Fevereiro', 'Fev', 'FEVEREIRO'],
        3 => ['Março', 'Mar', 'MARÇO'],
        4 => ['Abril', 'Abr', 'ABRIL'],
        5 => ['Maio', 'Mai', 'MAIO'],
        6 => ['Junho', 'Jun', 'JUNHO'],
        7 => ['Julho', 'Jul', 'JULHO'],
        8 => ['Agosto', 'Ago', 'AGOSTO'],
        9 => ['Setembro', 'Set', 'SETEMBRO'],
        10 => ['Outubro', 'Out', 'OUTUBRO'],
        11 => ['Novembro', 'Nov', 'NOVEMBRO'],
        12 => ['Dezembro', 'Dez', 'DEZEMBRO']
    ];
    
    if ($month < 1 || $month > 12) {
        return 'Indefinido';
    }
    
    $index = match($format) {
        'short' => 1,
        'upper' => 2,
        default => 0
    };
    
    return $months[$month][$index];
}

/**
 * Calcula diferença em dias entre duas datas
 */
function diffDays(string $date1, string $date2): int
{
    $d1 = new \DateTime($date1);
    $d2 = new \DateTime($date2);
    return (int) $d1->diff($d2)->days;
}

/**
 * Adiciona dias a uma data
 */
function addDays(string $date, int $days): string
{
    $dateTime = new \DateTime($date);
    $dateTime->modify("+{$days} days");
    return $dateTime->format('Y-m-d');
}

/**
 * Adiciona meses a uma data
 */
function addMonths(string $date, int $months): string
{
    $dateTime = new \DateTime($date);
    $dateTime->modify("+{$months} months");
    return $dateTime->format('Y-m-d');
}

/**
 * Remove acentos de uma string
 */
function removeAccents(string $text): string
{
    $search = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü','ç',
               'Á','À','Ã','Â','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Õ','Ô','Ö','Ú','Ù','Û','Ü','Ç'];
    $replace = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c',
                'A','A','A','A','A','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','C'];
    
    return str_replace($search, $replace, $text);
}

/**
 * Gera um slug a partir de um texto
 */
function slugify(string $text): string
{
    $text = removeAccents($text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Corta texto e adiciona reticências
 */
function truncate(string $text, int $length, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Formata CPF
 */
function formatCpf(string $cpf): string
{
    $cpf = preg_replace('/\D/', '', $cpf);
    
    if (strlen($cpf) !== 11) {
        return $cpf;
    }
    
    return sprintf('%s.%s.%s-%s',
        substr($cpf, 0, 3),
        substr($cpf, 3, 3),
        substr($cpf, 6, 3),
        substr($cpf, 9, 2)
    );
}

/**
 * Formata CNPJ
 */
function formatCnpj(string $cnpj): string
{
    $cnpj = preg_replace('/\D/', '', $cnpj);
    
    if (strlen($cnpj) !== 14) {
        return $cnpj;
    }
    
    return sprintf('%s.%s.%s/%s-%s',
        substr($cnpj, 0, 2),
        substr($cnpj, 2, 3),
        substr($cnpj, 5, 3),
        substr($cnpj, 8, 4),
        substr($cnpj, 12, 2)
    );
}

/**
 * Formata telefone
 */
function formatPhone(string $phone): string
{
    $phone = preg_replace('/\D/', '', $phone);
    $len = strlen($phone);
    
    if ($len === 10) {
        return sprintf('(%s) %s-%s',
            substr($phone, 0, 2),
            substr($phone, 2, 4),
            substr($phone, 6, 4)
        );
    }
    
    if ($len === 11) {
        return sprintf('(%s) %s-%s',
            substr($phone, 0, 2),
            substr($phone, 2, 5),
            substr($phone, 7, 4)
        );
    }
    
    return $phone;
}

/**
 * Valida CPF
 */
function validateCpf(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf);
    
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$t] != $d) {
            return false;
        }
    }
    
    return true;
}

/**
 * Valida CNPJ
 */
function validateCnpj(string $cnpj): bool
{
    $cnpj = preg_replace('/\D/', '', $cnpj);
    
    if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }
    
    $calc = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    
    for ($i = 0; $i < 12; $i++) {
        $sum += $cnpj[$i] * $calc[$i];
    }
    
    $rest = $sum % 11;
    $digit1 = $rest < 2 ? 0 : 11 - $rest;
    
    if ($cnpj[12] != $digit1) {
        return false;
    }
    
    array_unshift($calc, 6);
    $sum = 0;
    
    for ($i = 0; $i < 13; $i++) {
        $sum += $cnpj[$i] * $calc[$i];
    }
    
    $rest = $sum % 11;
    $digit2 = $rest < 2 ? 0 : 11 - $rest;
    
    return $cnpj[13] == $digit2;
}

/**
 * Valida email
 */
function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera token seguro
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Escape HTML para exibição segura
 */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Limpa entrada de dados
 */
function sanitize(mixed $data): mixed
{
    if (is_array($data)) {
        return array_map(fn($item) => sanitize($item), $data);
    }
    
    if (is_string($data)) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Converte horas para segundos
 */
function hoursToSeconds(string $time): int
{
    $parts = explode(':', $time);
    $count = count($parts);
    
    if ($count === 1) {
        return (int) $parts[0];
    }
    
    if ($count === 2) {
        return ((int) $parts[0] * 60) + (int) $parts[1];
    }
    
    if ($count === 3) {
        return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
    }
    
    return 0;
}

/**
 * Converte segundos para formato de hora
 */
function secondsToTime(int $seconds): string
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

/**
 * Gera thumbnail de imagem
 */
function createThumbnail(string $source, string $destination, int $maxWidth): bool
{
    if (!file_exists($source)) {
        return false;
    }
    
    $info = getimagesize($source);
    if ($info === false) {
        return false;
    }
    
    [$width, $height] = $info;
    $mime = $info['mime'];
    
    if ($width <= $maxWidth) {
        return copy($source, $destination);
    }
    
    $newHeight = (int) (($maxWidth / $width) * $height);
    
    $thumb = imagecreatetruecolor($maxWidth, $newHeight);
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if ($image === false) {
        return false;
    }
    
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
    
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($thumb, $destination, 90);
            break;
        case 'image/png':
            $result = imagepng($thumb, $destination, 9);
            break;
        case 'image/gif':
            $result = imagegif($thumb, $destination);
            break;
        case 'image/webp':
            $result = imagewebp($thumb, $destination, 90);
            break;
        default:
            $result = false;
    }
    
    imagedestroy($image);
    imagedestroy($thumb);
    
    return $result;
}

/**
 * Completa string com caracteres
 */
function padString(string $text, int $length, string $char = ' ', string $position = 'left'): string
{
    $currentLength = strlen($text);
    
    if ($currentLength >= $length) {
        return substr($text, 0, $length);
    }
    
    $padding = str_repeat($char, $length - $currentLength);
    
    return $position === 'left' ? $padding . $text : $text . $padding;
}

/**
 * Ofusca um ID numérico (para URLs)
 */
function obfuscateId(int $id): string
{
    $prefix = rand(10000, 99999);
    $suffix = rand(10000, 99999);
    return $prefix . $id . $suffix;
}

/**
 * Desofusca um ID
 */
function deobfuscateId(string $obfuscated): int
{
    if (strlen($obfuscated) <= 10) {
        return (int) $obfuscated;
    }
    
    $value = substr($obfuscated, 5, -5);
    return (int) $value;
}

/**
 * Verifica se é uma requisição AJAX
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Retorna resposta JSON
 */
function jsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redireciona para outra página
 */
function redirect(string $url, int $statusCode = 302): never
{
    http_response_code($statusCode);
    header("Location: {$url}");
    exit;
}

/**
 * Flash message - define uma mensagem para a próxima requisição
 */
function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Flash message - recupera e limpa a mensagem
 */
function getFlash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

