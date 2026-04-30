<?php
/**
 * Génère les données de pagination et le HTML des contrôles.
 *
 * @param int    $total       Nombre total d'enregistrements
 * @param int    $per_page    Enregistrements par page
 * @param int    $current     Page courante (1-indexed)
 * @param string $base_url    URL de base avec les GET params existants (sans 'page')
 * @return array ['offset', 'html']
 */
function paginate(int $total, int $per_page, int $current, string $base_url): array
{
    $pages  = max(1, (int)ceil($total / $per_page));
    $current = max(1, min($current, $pages));
    $offset  = ($current - 1) * $per_page;

    if ($pages <= 1) {
        return ['offset' => 0, 'pages' => 1, 'current' => 1, 'total' => $total, 'html' => ''];
    }

    $sep = str_contains($base_url, '?') ? '&' : '?';

    $html  = '<nav class="pagination" aria-label="Pagination">';
    $html .= '<a href="' . $base_url . $sep . 'page=' . max(1, $current - 1) . '" class="page-btn' . ($current <= 1 ? ' disabled' : '') . '" aria-label="Précédent"><i class="fa-solid fa-chevron-left"></i></a>';

    $range = 2;
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === 1 || $i === $pages || abs($i - $current) <= $range) {
            $active = $i === $current ? ' active' : '';
            $html .= '<a href="' . $base_url . $sep . 'page=' . $i . '" class="page-btn' . $active . '">' . $i . '</a>';
        } elseif (abs($i - $current) === $range + 1) {
            $html .= '<span class="page-ellipsis">…</span>';
        }
    }

    $html .= '<a href="' . $base_url . $sep . 'page=' . min($pages, $current + 1) . '" class="page-btn' . ($current >= $pages ? ' disabled' : '') . '" aria-label="Suivant"><i class="fa-solid fa-chevron-right"></i></a>';
    $html .= '</nav>';

    return ['offset' => $offset, 'pages' => $pages, 'current' => $current, 'total' => $total, 'html' => $html];
}
