<?php
/**
 * Controller: HomeController
 * File: app/controllers/HomeController.php
 */

require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../../config/database.php';

class HomeController
{
    public function index(): void
    {
        $restoModel = new Restaurant();
        $restaurant = $restoModel->getById(1);

        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM menu_items WHERE restaurant_id = 1 ORDER BY id ASC");
        $featuredDonuts = $stmt ? $stmt->fetchAll() : [];

        include __DIR__ . '/../views/home/index.php';
    }
}
