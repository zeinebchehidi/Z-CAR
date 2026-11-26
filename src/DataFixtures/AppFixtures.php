<?php

namespace App\DataFixtures;

use App\Entity\Car;
use App\Entity\Category;
use App\Entity\Payment;
use App\Entity\Reservation;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\FuelType;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Enum\ReservationStatus;
use App\Enum\Transmission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Sample data for development and the presentation.
 * Load it with: php bin/console doctrine:fixtures:load
 * (this empties the database first).
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $clients = $this->loadUsers($manager);
        $categories = $this->loadCategories($manager);
        $cars = $this->loadCars($manager, $categories);
        $this->loadReservations($manager, $clients, $cars);
        $this->loadReviews($manager, $clients, $cars);

        $manager->flush();
    }

    /**
     * 1 admin + 3 clients. Returns the clients.
     *
     * @return User[]
     */
    private function loadUsers(ObjectManager $manager): array
    {
        $admin = new User();
        $admin->setEmail('admin@carrental.test')
            ->setFirstName('Admin')
            ->setLastName('Z-Car')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // [email, first name, last name, phone, driver license]
        $clientData = [
            ['sara@carrental.test', 'Sara', 'Ben Ali', '+216 20 123 456', 'TN-DL-482913'],
            ['youssef@carrental.test', 'Youssef', 'Trabelsi', '+216 22 654 321', 'TN-DL-771045'],
            // No phone / license yet: shows that they are filled in on the profile page.
            ['lina@carrental.test', 'Lina', 'Haddad', null, null],
        ];

        $clients = [];
        foreach ($clientData as [$email, $firstName, $lastName, $phone, $license]) {
            $client = new User();
            $client->setEmail($email)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setPhone($phone)
                ->setDriverLicenseNumber($license)
                ->setPassword($this->passwordHasher->hashPassword($client, 'client123'));
            $manager->persist($client);
            $clients[] = $client;
        }

        return $clients;
    }

    /**
     * @return array<string, Category> indexed by name
     */
    private function loadCategories(ObjectManager $manager): array
    {
        $categoryData = [
            'Economy' => 'Small and cheap cars, perfect for the city.',
            'Compact' => 'Comfortable cars for everyday trips.',
            'SUV' => 'Spacious cars for families and long trips.',
            'Luxury' => 'Premium cars for special occasions.',
        ];

        $categories = [];
        foreach ($categoryData as $name => $description) {
            $category = new Category();
            $category->setName($name)->setDescription($description);
            $manager->persist($category);
            $categories[$name] = $category;
        }

        return $categories;
    }

    /**
     * 12 cars. The photo of car N is fixtures/images/car-NN.jpg (optional).
     *
     * @param array<string, Category> $categories
     *
     * @return Car[]
     */
    private function loadCars(ObjectManager $manager, array $categories): array
    {
        // [brand, model, year, plate, price per day, fuel, transmission, seats, category, available]
        $carData = [
            ['Renault', 'Clio', 2022, '201 TU 1001', '35.00', FuelType::Petrol, Transmission::Manual, 5, 'Economy', true],
            ['Suzuki', 'Ignis', 2024, '201 TU 1002', '32.00', FuelType::Petrol, Transmission::Manual, 5, 'Economy', true],
            ['Kia', 'Picanto', 2021, '201 TU 1003', '30.00', FuelType::Petrol, Transmission::Automatic, 4, 'Economy', true],
            ['Volkswagen', 'Polo', 2022, '201 TU 1004', '45.00', FuelType::Diesel, Transmission::Manual, 5, 'Compact', true],
            ['Volkswagen', 'Polo', 2024, '201 TU 1005', '50.00', FuelType::Petrol, Transmission::Automatic, 5, 'Compact', true],
            // In maintenance: cannot be booked.
            ['Hyundai', 'i30', 2021, '201 TU 1006', '50.00', FuelType::Petrol, Transmission::Manual, 5, 'Compact', false],
            ['Dacia', 'Duster', 2023, '201 TU 1007', '65.00', FuelType::Diesel, Transmission::Manual, 5, 'SUV', true],
            ['Kia', 'Sportage', 2024, '201 TU 1008', '85.00', FuelType::Hybrid, Transmission::Automatic, 5, 'SUV', true],
            ['Peugeot', '5008', 2023, '201 TU 1009', '90.00', FuelType::Diesel, Transmission::Automatic, 7, 'SUV', true],
            ['Mercedes', 'EQE', 2024, '201 TU 1010', '150.00', FuelType::Electric, Transmission::Automatic, 5, 'Luxury', true],
            ['BMW', 'X7', 2023, '201 TU 1011', '200.00', FuelType::Diesel, Transmission::Automatic, 7, 'Luxury', true],
            ['Tesla', 'Model 3', 2024, '201 TU 1012', '120.00', FuelType::Electric, Transmission::Automatic, 5, 'Luxury', true],
        ];

        $cars = [];
        foreach ($carData as $index => [$brand, $model, $year, $plate, $price, $fuel, $transmission, $seats, $category, $available]) {
            $car = new Car();
            $car->setBrand($brand)
                ->setModel($model)
                ->setYear($year)
                ->setPlateNumber($plate)
                ->setPricePerDay($price)
                ->setFuelType($fuel)
                ->setTransmission($transmission)
                ->setSeats($seats)
                ->setCategory($categories[$category])
                ->setIsAvailable($available)
                ->setImage($this->copyCarImage($index + 1));
            $manager->persist($car);
            $cars[] = $car;
        }

        return $cars;
    }

    /**
     * Copies fixtures/images/car-NN.jpg to public/uploads/cars/ (which is not in git).
     * Returns the filename to store in Car.image, or null if there is no photo.
     */
    private function copyCarImage(int $number): ?string
    {
        $filename = sprintf('car-%02d.jpg', $number);
        $source = $this->projectDir.'/fixtures/images/'.$filename;

        if (!file_exists($source)) {
            return null;
        }

        $filesystem = new Filesystem();
        $filesystem->copy($source, $this->projectDir.'/public/uploads/cars/'.$filename, true);

        return $filename;
    }

    /**
     * Reservations in every status, each with its payment.
     * Dates are relative to today, so the data stays valid whenever it is loaded.
     *
     * @param User[] $clients
     * @param Car[]  $cars
     */
    private function loadReservations(ObjectManager $manager, array $clients, array $cars): void
    {
        [$sara, $youssef, $lina] = $clients;

        // Pending: the client has not chosen how to pay yet.
        $this->addReservation($manager, $sara, $cars[1], '+5 days', '+8 days', ReservationStatus::Pending, PaymentStatus::Pending, null);
        // Pending: cash chosen, waiting for an admin to validate it.
        $this->addReservation($manager, $youssef, $cars[6], '+10 days', '+14 days', ReservationStatus::Pending, PaymentStatus::Pending, PaymentMethod::Cash);

        // Confirmed: paid by card, in the future.
        $this->addReservation($manager, $lina, $cars[9], '+3 days', '+5 days', ReservationStatus::Confirmed, PaymentStatus::Paid, PaymentMethod::Card);
        // Confirmed: the client has the car right now (active reservation).
        $this->addReservation($manager, $sara, $cars[4], '-1 day', '+2 days', ReservationStatus::Confirmed, PaymentStatus::Paid, PaymentMethod::Card);

        // Completed: in the past, paid. These allow reviews (business rule 7).
        $this->addReservation($manager, $sara, $cars[0], '-30 days', '-26 days', ReservationStatus::Completed, PaymentStatus::Paid, PaymentMethod::Card);
        $this->addReservation($manager, $youssef, $cars[7], '-20 days', '-17 days', ReservationStatus::Completed, PaymentStatus::Paid, PaymentMethod::Cash);
        $this->addReservation($manager, $lina, $cars[11], '-15 days', '-13 days', ReservationStatus::Completed, PaymentStatus::Paid, PaymentMethod::Card);
        $this->addReservation($manager, $youssef, $cars[0], '-10 days', '-8 days', ReservationStatus::Completed, PaymentStatus::Paid, PaymentMethod::Card);

        // Cancelled after a card payment: the payment was refunded (business rule 6).
        $this->addReservation($manager, $lina, $cars[3], '+7 days', '+9 days', ReservationStatus::Cancelled, PaymentStatus::Refunded, PaymentMethod::Card);
        // Cancelled before paying.
        $this->addReservation($manager, $sara, $cars[8], '+20 days', '+25 days', ReservationStatus::Cancelled, PaymentStatus::Pending, null);
    }

    private function addReservation(
        ObjectManager $manager,
        User $client,
        Car $car,
        string $start,
        string $end,
        ReservationStatus $status,
        PaymentStatus $paymentStatus,
        ?PaymentMethod $method,
    ): void {
        $startDate = new \DateTimeImmutable('today '.$start);
        $endDate = new \DateTimeImmutable('today '.$end);

        // Business rule 3: number of days (start and end included) x price per day.
        $days = $startDate->diff($endDate)->days + 1;
        $totalPrice = number_format($days * (float) $car->getPricePerDay(), 2, '.', '');

        $reservation = new Reservation();
        $reservation->setUser($client)
            ->setCar($car)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setTotalPrice($totalPrice)
            ->setStatus($status);

        $payment = new Payment();
        $payment->setAmount($totalPrice)
            ->setStatus($paymentStatus)
            ->setMethod($method);

        // Paid (or paid then refunded): paid 2 days before the start, but never in the future.
        if ($paymentStatus !== PaymentStatus::Pending) {
            $payment->setPaidAt(min($startDate->modify('-2 days'), new \DateTimeImmutable()));
        }

        // setPayment() links both sides; cascade persist saves the payment too.
        $reservation->setPayment($payment);
        $manager->persist($reservation);
    }

    /**
     * Reviews only for cars the client has a completed reservation for,
     * one per client and car. Some are still waiting for admin approval.
     *
     * @param User[] $clients
     * @param Car[]  $cars
     */
    private function loadReviews(ObjectManager $manager, array $clients, array $cars): void
    {
        [$sara, $youssef, $lina] = $clients;

        // [client, car, rating, comment, approved]
        $reviewData = [
            [$sara, $cars[0], 5, 'Very economical and easy to park. Perfect for the city.', true],
            [$youssef, $cars[7], 4, 'Comfortable SUV, a bit expensive but worth it.', true],
            [$lina, $cars[11], 5, 'Amazing electric car, super quiet and fast.', false],
            [$youssef, $cars[0], 3, 'Good car but the air conditioning was weak.', false],
        ];

        foreach ($reviewData as [$client, $car, $rating, $comment, $approved]) {
            $review = new Review();
            $review->setUser($client)
                ->setCar($car)
                ->setRating($rating)
                ->setComment($comment)
                ->setIsApproved($approved);
            $manager->persist($review);
        }
    }
}
