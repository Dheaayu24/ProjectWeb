<?php
/**
 * Controller: RestaurantController (Fokus Satu Resto: Mochi Mood)
 * File: app/controllers/RestaurantController.php
 */

require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../models/Queue.php';
require_once __DIR__ . '/../../config/database.php';

class RestaurantController
{
    public function index(): void
    {
        // Alihkan langsung ke halaman detail toko Mochi Mood
        header('Location: index.php?url=restaurants/detail');
        exit;
    }

    public function detail(): void
    {
        $restoModel = new Restaurant();
        $restaurant = $restoModel->getById(1);

        $queueModel = new Queue();
        $waitingCount = $queueModel->getWaitingCount(1);

        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM menu_items WHERE restaurant_id = 1 ORDER BY id ASC");
        $donuts = $stmt ? $stmt->fetchAll() : [];

        include __DIR__ . '/../views/restaurants/detail.php';
    }
}
