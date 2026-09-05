<?php
declare(strict_types=1);

namespace Faizal\LaundryLms\Models;

use Faizal\LaundryLms\Core\Database;

class Settings {
    public static function get(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM business_settings WHERE id = 1");
        $settings = $stmt->fetch();

        // If no settings exist yet, create the default row
        if (!$settings) {
            $db->query("INSERT INTO business_settings (id, business_name) VALUES (1, 'My Laundry')");
            return self::get();
        }

        return $settings;
    }

    public static function update(array $data): void {
        $db = Database::getConnection();
        $sql = "UPDATE business_settings SET 
                business_name = :business_name,
                whatsapp_number = :whatsapp_number,
                working_hours = :working_hours,
                pickup_charge = :pickup_charge,
                delivery_charge = :delivery_charge,
                free_pickup_limit = :free_pickup_limit,
                address = :address
                WHERE id = 1";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'business_name' => $data['business_name'] ?? '',
            'whatsapp_number' => $data['whatsapp_number'] ?? '',
            'working_hours' => $data['working_hours'] ?? '',
            'pickup_charge' => (float)($data['pickup_charge'] ?? 0),
            'delivery_charge' => (float)($data['delivery_charge'] ?? 0),
            'free_pickup_limit' => (float)($data['free_pickup_limit'] ?? 0),
            'address' => $data['address'] ?? ''
        ]);
    }
}