<?php

declare(strict_types=1);

namespace App\Support;

final class InventoryBiCalculator
{
    public static function percentageChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) {
            return abs($current) < 0.00001 ? 0.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{average_active_qty_out: float, counts: array{fast: int, slow: int, non_moving: int}, rows: array<int, array<string, mixed>>}
     */
    public static function classifyProductMovements(array $rows): array
    {
        $activeQuantities = [];

        foreach ($rows as $row) {
            $quantity = (float) ($row['total_qty_out'] ?? 0);

            if ($quantity > 0) {
                $activeQuantities[] = $quantity;
            }
        }

        $average = $activeQuantities === []
            ? 0.0
            : array_sum($activeQuantities) / count($activeQuantities);

        $counts = [
            'fast' => 0,
            'slow' => 0,
            'non_moving' => 0,
        ];

        foreach ($rows as &$row) {
            $quantity = (float) ($row['total_qty_out'] ?? 0);

            if ($quantity <= 0) {
                $classification = 'non_moving';
                $classificationLabel = 'Tidak Bergerak';
            } elseif ($quantity > $average) {
                $classification = 'fast';
                $classificationLabel = 'Cepat Bergerak';
            } else {
                $classification = 'slow';
                $classificationLabel = 'Lambat Bergerak';
            }

            $row['classification'] = $classification;
            $row['classification_label'] = $classificationLabel;
            $counts[$classification]++;
        }
        unset($row);

        return [
            'average_active_qty_out' => round($average, 2),
            'counts' => $counts,
            'rows' => $rows,
        ];
    }
}