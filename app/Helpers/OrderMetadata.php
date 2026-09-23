<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Helpers;

class OrderMetadata {
    private const DELIMITER_START = '<!-- LMS_METADATA_V1';
    private const DELIMITER_END   = '-->';

    /**
     * Extracts customer note and structured system metadata from special_instructions.
     *
     * @param string|null $rawInstructions
     * @return array{customer_note: string, metadata: array}
     */
    public static function extract(?string $rawInstructions): array {
        if ($rawInstructions === null || trim($rawInstructions) === '') {
            return [
                'customer_note' => '',
                'metadata'      => []
            ];
        }

        $startPos = strrpos($rawInstructions, self::DELIMITER_START);
        if ($startPos === false) {
            return [
                'customer_note' => trim($rawInstructions),
                'metadata'      => []
            ];
        }

        $customerNote = trim(substr($rawInstructions, 0, $startPos));
        $metaBlock = substr($rawInstructions, $startPos + strlen(self::DELIMITER_START));
        $endPos = strrpos($metaBlock, self::DELIMITER_END);

        if ($endPos === false) {
            // Malformed delimiter: treat entire text as customer note safely
            return [
                'customer_note' => trim($rawInstructions),
                'metadata'      => []
            ];
        }

        $jsonStr = trim(substr($metaBlock, 0, $endPos));
        $data = json_decode($jsonStr, true);

        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            // Malformed JSON: fallback safely without crashing
            return [
                'customer_note' => trim($rawInstructions),
                'metadata'      => []
            ];
        }

        return [
            'customer_note' => $customerNote,
            'metadata'      => $data
        ];
    }

    /**
     * Composes raw special_instructions string by joining customer note and metadata block.
     *
     * @param string $customerNote
     * @param array $metadata
     * @return string
     */
    public static function compose(string $customerNote, array $metadata): string {
        $customerNote = trim($customerNote);

        if (empty($metadata)) {
            return $customerNote;
        }

        $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        if ($json === false) {
            return $customerNote;
        }

        $block = self::DELIMITER_START . "\n" . $json . "\n" . self::DELIMITER_END;

        if ($customerNote === '') {
            return $block;
        }

        return $customerNote . "\n\n" . $block;
    }

    /**
     * Computes deterministic SHA-256 revision hash for inspection state.
     *
     * @param array $inspectionData
     * @return string
     */
    public static function generateRevisionHash(array $inspectionData): string {
        if (!empty($inspectionData['items']) && is_array($inspectionData['items'])) {
            $canonicalItems = [];
            foreach ($inspectionData['items'] as $item) {
                $canonicalItems[] = [
                    'item_id'       => (int)($item['item_id'] ?? 0),
                    'work_type_id'  => (int)($item['work_type_id'] ?? 0),
                    'quantity'      => round((float)($item['quantity'] ?? 0), 2),
                    'unit_price'    => round((float)($item['unit_price'] ?? 0), 2),
                    'pricing_type'  => (string)($item['pricing_type'] ?? 'per_item'),
                    'subtotal'      => round((float)($item['subtotal'] ?? 0), 2),
                    'item_notes'    => trim((string)($item['item_notes'] ?? ''))
                ];
            }
            usort($canonicalItems, fn($a, $b) => ($a['item_id'] <=> $b['item_id']) ?: ($a['work_type_id'] <=> $b['work_type_id']));

            $canonical = [
                'items'            => $canonicalItems,
                'items_subtotal'   => round((float)($inspectionData['items_subtotal'] ?? 0), 2),
                'calculated_total' => round((float)($inspectionData['calculated_total'] ?? 0), 2),
                'pickup_fee'       => round((float)($inspectionData['pickup_fee'] ?? 0), 2),
                'delivery_fee'     => round((float)($inspectionData['delivery_fee'] ?? 0), 2),
                'garment_notes'    => trim((string)($inspectionData['garment_notes'] ?? ''))
            ];
            ksort($canonical);
            return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        $canonical = [
            'item_id'          => (int)($inspectionData['item_id'] ?? 0),
            'work_type_id'     => (int)($inspectionData['work_type_id'] ?? 0),
            'quantity'         => round((float)($inspectionData['quantity'] ?? 0), 2),
            'unit_price'       => round((float)($inspectionData['unit_price'] ?? 0), 2),
            'item_subtotal'    => round((float)($inspectionData['item_subtotal'] ?? 0), 2),
            'calculated_total' => round((float)($inspectionData['calculated_total'] ?? 0), 2),
            'garment_notes'    => trim((string)($inspectionData['garment_notes'] ?? '')),
        ];
        ksort($canonical);
        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
