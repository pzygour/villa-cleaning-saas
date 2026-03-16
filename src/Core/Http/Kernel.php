<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Application\Services\BookingService;
use App\Application\Services\CleaningScheduleService;
use App\Application\Services\PropertyCleaningSettingsService;
use App\Application\Services\PropertyService;
use App\Application\Services\RoomService;
use App\Application\Validators\BookingValidator;
use App\Application\Validators\CleaningSettingsValidator;
use App\Application\Validators\PropertyValidator;
use App\Application\Validators\RoomValidator;
use App\Core\Database\ConnectionFactory;
use App\Core\Database\TransactionManager;
use App\Core\Exception\ValidationException;
use App\Core\Logging\LoggerInterface;
use App\Http\Controller\BookingController;
use App\Http\Controller\CleaningScheduleController;
use App\Http\Controller\PropertyCleaningSettingsController;
use App\Http\Controller\PropertyController;
use App\Http\Controller\RoomController;
use App\Http\Controller\SetupCatalogController;
use App\Http\Response\HtmlResponse;
use App\Http\Response\JsonResponse;
use App\Http\Response\ResponseInterface;
use App\Infrastructure\Logging\FileLogger;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBathroomTypeRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBedTypeRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBookingRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlCleaningEventRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlPropertyCleaningSettingsRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlPropertyRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlRoomRepository;
use Throwable;

final class Kernel
{
    private readonly BookingController $bookingController;
    private readonly PropertyController $propertyController;
    private readonly RoomController $roomController;
    private readonly SetupCatalogController $setupCatalogController;
    private readonly PropertyCleaningSettingsController $cleaningSettingsController;
    private readonly CleaningScheduleController $cleaningScheduleController;
    private readonly LoggerInterface $logger;

    public function __construct()
    {
        $db = ConnectionFactory::fromConfig(require __DIR__ . '/../../../config/database.php');
        $this->logger = new FileLogger((require __DIR__ . '/../../../config/logging.php')['file']);

        $transactionManager = new TransactionManager($db);

        $propertyRepository = new MySqlPropertyRepository($db);
        $roomRepository = new MySqlRoomRepository($db);
        $bookingRepository = new MySqlBookingRepository($db);
        $cleaningEventRepository = new MySqlCleaningEventRepository($db);
        $cleaningSettingsRepository = new MySqlPropertyCleaningSettingsRepository($db);
        $bedTypeRepository = new MySqlBedTypeRepository($db);
        $bathroomTypeRepository = new MySqlBathroomTypeRepository($db);

        $this->propertyController = new PropertyController(new PropertyService($propertyRepository, new PropertyValidator()));
        $this->roomController = new RoomController(new RoomService($roomRepository, new RoomValidator()));
        $this->bookingController = new BookingController(new BookingService($bookingRepository, new BookingValidator()));
        $this->setupCatalogController = new SetupCatalogController($bedTypeRepository, $bathroomTypeRepository);
        $this->cleaningSettingsController = new PropertyCleaningSettingsController(
            new PropertyCleaningSettingsService($cleaningSettingsRepository, new CleaningSettingsValidator())
        );
        $this->cleaningScheduleController = new CleaningScheduleController(
            new CleaningScheduleService($bookingRepository, $cleaningEventRepository, $cleaningSettingsRepository, $transactionManager),
            $cleaningEventRepository
        );
    }

    public function handle(array $server): ResponseInterface
    {
        try {
            $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
            $path = parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
            $payload = $this->jsonBody();
            $query = $_GET;

            if ($path === '/') {
                return new HtmlResponse('<h1>Milestone 1 backend is running.</h1>');
            }

            return $this->route($method, $path, $query, $payload);
        } catch (ValidationException $exception) {
            return new JsonResponse(['error' => 'validation_error', 'details' => $exception->errors()], 422);
        } catch (Throwable $throwable) {
            $this->logger->error('Unhandled exception', ['message' => $throwable->getMessage()]);

            return new JsonResponse(['error' => 'server_error'], 500);
        }
    }

    private function route(string $method, string $path, array $query, array $payload): ResponseInterface
    {
        if ($method === 'GET' && $path === '/properties') {
            return new JsonResponse($this->propertyController->index());
        }
        if ($method === 'POST' && $path === '/properties') {
            return new JsonResponse($this->propertyController->create($payload), 201);
        }
        if (preg_match('#^/properties/([a-f0-9\-]+)$#', $path, $matches) === 1) {
            if ($method === 'PUT') {
                return new JsonResponse($this->propertyController->update($matches[1], $payload));
            }
            if ($method === 'DELETE') {
                return new JsonResponse($this->propertyController->delete($matches[1]));
            }
        }

        if ($method === 'POST' && $path === '/rooms') {
            return new JsonResponse($this->roomController->create($payload), 201);
        }
        if (preg_match('#^/rooms/([a-f0-9\-]+)/beds$#', $path, $matches) === 1 && $method === 'PUT') {
            return new JsonResponse($this->roomController->setBeds($matches[1], $payload));
        }
        if (preg_match('#^/rooms/([a-f0-9\-]+)/bathrooms$#', $path, $matches) === 1 && $method === 'PUT') {
            return new JsonResponse($this->roomController->setBathrooms($matches[1], $payload));
        }
        if (preg_match('#^/rooms/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'PUT') {
            return new JsonResponse($this->roomController->update($matches[1], $payload));
        }
        if ($method === 'GET' && $path === '/rooms') {
            return new JsonResponse($this->roomController->listByProperty((string) ($query['property_id'] ?? '')));
        }

        if ($method === 'GET' && $path === '/setup/bed-types') {
            return new JsonResponse($this->setupCatalogController->bedTypes());
        }
        if ($method === 'GET' && $path === '/setup/bathroom-types') {
            return new JsonResponse($this->setupCatalogController->bathroomTypes());
        }

        if ($method === 'POST' && $path === '/bookings') {
            return new JsonResponse($this->bookingController->create($payload), 201);
        }
        if (preg_match('#^/bookings/([a-f0-9\-]+)/cancel$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->bookingController->cancel($matches[1]));
        }
        if (preg_match('#^/bookings/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'PUT') {
            return new JsonResponse($this->bookingController->update($matches[1], $payload));
        }
        if ($method === 'GET' && $path === '/bookings') {
            return new JsonResponse($this->bookingController->list((string) ($query['property_id'] ?? ''), (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }

        if (preg_match('#^/properties/([a-f0-9\-]+)/cleaning-settings$#', $path, $matches) === 1) {
            if ($method === 'GET') {
                return new JsonResponse($this->cleaningSettingsController->show($matches[1]));
            }
            if ($method === 'PUT') {
                return new JsonResponse($this->cleaningSettingsController->upsert($matches[1], $payload));
            }
        }

        if (preg_match('#^/properties/([a-f0-9\-]+)/cleaning-events/regenerate$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->cleaningScheduleController->regenerate($matches[1], $payload));
        }
        if (preg_match('#^/properties/([a-f0-9\-]+)/cleaning-events$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->cleaningScheduleController->list($matches[1], (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }

        return new JsonResponse(['error' => 'not_found'], 404);
    }

    private function jsonBody(): array
    {
        $content = file_get_contents('php://input');
        if ($content === false || trim($content) === '') {
            return $_POST;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
