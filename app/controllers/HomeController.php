<?php
/**
 * ============================================================================
 * QueueBites – Home Controller
 * File: app/controllers/HomeController.php
 * Deskripsi: Mengelola tampilan halaman utama (Landing Page) dan daftar restoran.
 * ============================================================================
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../models/Announcement.php';
require_once __DIR__ . '/../../config/database.php';

class HomeController extends BaseController
{
    protected Restaurant $restaurantModel;
    protected Announcement $announcementModel;

    public function __construct()
    {
        $this->restaurantModel = new Restaurant();
        $this->announcementModel = new Announcement();
    }

    /**
     * Menampilkan halaman utama (Landing Page)
     * - Mengambil informasi restoran utama dari Model Restaurant
     * - Menampilkan daftar restoran beserta status antrean
     * - Mengambil daftar menu donut favorit dari database
     */
    public function index(): void
    {
        // 1. Ambil data restoran utama (Mochi Mood - ID 1)
        $restaurant = $this->restaurantModel->getById(1);

        // Jika ID 1 belum ada di database, ambil restoran pertama yang tersedia
        if (!$restaurant) {
            $allResto = $this->restaurantModel->getAll();
            $restaurant = !empty($allResto) ? $allResto[0] : [
                'id'            => 1,
                'name'          => 'Mochi Mood Bakery',
                'description'   => 'A Little Mochi, A Lot of Happiness ♡',
                'address'       => 'Jl. Senopati No. 42, Kebayoran Baru, Jakarta Selatan',
                'opening_hours' => '10:00 - 22:00',
                'status'        => 'open',
            ];
        }

        // 2. Ambil seluruh daftar restoran yang sedang buka/tersedia beserta jumlah antrean
        $restaurants = $this->restaurantModel->getWithWaitingCount();

        // 3. Ambil menu unggulan restoran (Prepared Statement)
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM menu_items WHERE restaurant_id = :resto_id ORDER BY id ASC");
        $stmt->execute([':resto_id' => $restaurant['id']]);
        $featuredDonuts = $stmt->fetchAll();

        // 4. Ambil pengumuman aktif jika ada
        $activeAnnouncement = $this->announcementModel->getActive();

        // 5. Render ke View
        $this->view('home/index', [
            'restaurant'         => $restaurant,
            'restaurants'        => $restaurants,
            'featuredDonuts'     => $featuredDonuts,
            'activeAnnouncement' => $activeAnnouncement,
        ]);
    }
}
