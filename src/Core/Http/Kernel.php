<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Application\Services\BathroomTypeItemRuleService;
use App\Application\Services\BedTypeItemRuleService;
use App\Application\Services\BookingService;
use App\Application\Services\AuthService;
use App\Application\Services\CleanerOperationsService;
use App\Application\Services\CleaningEventAssignmentService;
use App\Application\Services\CleaningScheduleQueryService;
use App\Application\Services\CleaningScheduleService;
use App\Application\Services\GuestItemRuleService;
use App\Application\Services\InventoryLedgerService;
use App\Application\Services\InventoryLocationService;
use App\Application\Services\InventoryQueryService;
use App\Application\Services\InventoryReservationService;
use App\Application\Services\ItemCatalogService;
use App\Application\Services\LaundryHandoverService;
use App\Application\Services\LaundryQueryService;
use App\Application\Services\PropertyCleaningSettingsService;
use App\Application\Services\PropertyService;
use App\Application\Services\RequirementCalculationService;
use App\Application\Services\RequirementTotalsQueryService;
use App\Application\Services\RoomService;
use App\Application\Services\UserQueryService;
use App\Application\Validators\BookingValidator;
use App\Application\Validators\CleaningAssignmentValidator;
use App\Application\Validators\CleaningSettingsValidator;
use App\Application\Validators\InventoryLocationValidator;
use App\Application\Validators\InventoryTransactionValidator;
use App\Application\Validators\ItemCatalogValidator;
use App\Application\Validators\ItemRuleValidator;
use App\Application\Validators\LaundryHandoverValidator;
use App\Application\Validators\PropertyValidator;
use App\Application\Validators\RoomValidator;
use App\Core\Database\ConnectionFactory;
use App\Core\Database\TransactionManager;
use App\Core\Exception\DomainException;
use App\Core\Exception\NotFoundException;
use App\Core\Exception\ValidationException;
use App\Core\Http\Security\SessionSecurity;
use App\Core\Http\Security\LoginThrottle;
use App\Core\Logging\LoggerInterface;
use App\Http\Controller\BathroomTypeItemRuleController;
use App\Http\Controller\BedTypeItemRuleController;
use App\Http\Controller\BookingController;
use App\Http\Controller\CleaningAssignmentController;
use App\Http\Controller\CleaningScheduleController;
use App\Http\Controller\GuestItemRuleController;
use App\Http\Controller\InventoryLocationController;
use App\Http\Controller\InventoryQueryController;
use App\Http\Controller\InventoryReservationController;
use App\Http\Controller\InventoryTransactionController;
use App\Http\Controller\ItemCatalogController;
use App\Http\Controller\LaundryHandoverController;
use App\Http\Controller\LaundryQueryController;
use App\Http\Controller\PropertyCleaningSettingsController;
use App\Http\Controller\PropertyController;
use App\Http\Controller\RequirementController;
use App\Http\Controller\RequirementTotalsController;
use App\Http\Controller\RoomController;
use App\Http\Controller\ScheduleQueryController;
use App\Http\Controller\SetupCatalogController;
use App\Http\Response\HtmlResponse;
use App\Http\Response\ApiPayload;
use App\Http\Response\JsonResponse;
use App\Http\Response\ResponseInterface;
use App\Infrastructure\Logging\FileLogger;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBathroomTypeItemRuleRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBathroomTypeRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBedTypeItemRuleRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBedTypeRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlBookingRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlCleaningEventAssignmentRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlCleaningEventRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlCleaningEventRequirementRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlCleaningScheduleQueryRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlGuestItemRuleRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlInventoryLedgerRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlInventoryLocationRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlInventoryQueryRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlInventoryReservationRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlItemCatalogRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlLaundryHandoverRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlLaundryQueryRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlPropertyCleaningSettingsRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlPropertyRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlRequirementSourceRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlRequirementTotalsQueryRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlRoomRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlUserAuthRepository;
use App\Infrastructure\Persistence\MySql\Repository\MySqlUserQueryRepository;
use Throwable;

final class Kernel
{
    private BookingController $bookingController;
    private PropertyController $propertyController;
    private RoomController $roomController;
    private SetupCatalogController $setupCatalogController;
    private PropertyCleaningSettingsController $cleaningSettingsController;
    private CleaningScheduleController $cleaningScheduleController;
    private ItemCatalogController $itemCatalogController;
    private BedTypeItemRuleController $bedTypeItemRuleController;
    private BathroomTypeItemRuleController $bathroomTypeItemRuleController;
    private GuestItemRuleController $guestItemRuleController;
    private RequirementController $requirementController;
    private CleaningAssignmentController $assignmentController;
    private ScheduleQueryController $scheduleQueryController;
    private RequirementTotalsController $requirementTotalsController;
    private InventoryLocationController $inventoryLocationController;
    private InventoryTransactionController $inventoryTransactionController;
    private InventoryReservationController $inventoryReservationController;
    private InventoryQueryController $inventoryQueryController;
    private LaundryHandoverController $laundryHandoverController;
    private LaundryQueryController $laundryQueryController;
    private AuthService $authService;
    private LoggerInterface $logger;
    private bool $debug = false;
    private int $sessionTimeoutSeconds = 3600;
    private int $loginMaxAttempts = 5;
    private int $loginLockoutSeconds = 300;
    private string $basePath = '';

    public function __construct()
    {
        $appConfig = require __DIR__ . '/../../../config/app.php';
        $db = ConnectionFactory::fromConfig(require __DIR__ . '/../../../config/database.php');
        $this->logger = new FileLogger((require __DIR__ . '/../../../config/logging.php')['file']);
        $this->debug = (bool) ($appConfig['debug'] ?? false);
        $this->sessionTimeoutSeconds = max(0, (int) ($appConfig['session_timeout_seconds'] ?? 3600));
        $this->loginMaxAttempts = max(1, (int) ($appConfig['login_max_attempts'] ?? 5));
        $this->loginLockoutSeconds = max(30, (int) ($appConfig['login_lockout_seconds'] ?? 300));
        $transactionManager = new TransactionManager($db);

        $propertyRepository = new MySqlPropertyRepository($db);
        $roomRepository = new MySqlRoomRepository($db);
        $bookingRepository = new MySqlBookingRepository($db);
        $cleaningEventRepository = new MySqlCleaningEventRepository($db);
        $cleaningSettingsRepository = new MySqlPropertyCleaningSettingsRepository($db);
        $bedTypeRepository = new MySqlBedTypeRepository($db);
        $bathroomTypeRepository = new MySqlBathroomTypeRepository($db);

        $itemCatalogRepository = new MySqlItemCatalogRepository($db);
        $bedTypeItemRuleRepository = new MySqlBedTypeItemRuleRepository($db);
        $bathroomTypeItemRuleRepository = new MySqlBathroomTypeItemRuleRepository($db);
        $guestItemRuleRepository = new MySqlGuestItemRuleRepository($db);
        $requirementsRepository = new MySqlCleaningEventRequirementRepository($db);
        $requirementSourceRepository = new MySqlRequirementSourceRepository($db);
        $assignmentRepository = new MySqlCleaningEventAssignmentRepository($db);
        $scheduleQueryRepository = new MySqlCleaningScheduleQueryRepository($db);
        $requirementTotalsRepository = new MySqlRequirementTotalsQueryRepository($db);
        $userQueryRepository = new MySqlUserQueryRepository($db);
        $userAuthRepository = new MySqlUserAuthRepository($db);

        $inventoryLocationRepository = new MySqlInventoryLocationRepository($db);
        $inventoryLedgerRepository = new MySqlInventoryLedgerRepository($db);
        $inventoryReservationRepository = new MySqlInventoryReservationRepository($db);
        $inventoryQueryRepository = new MySqlInventoryQueryRepository($db);
        $laundryHandoverRepository = new MySqlLaundryHandoverRepository($db);
        $laundryQueryRepository = new MySqlLaundryQueryRepository($db);

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

        $itemRuleValidator = new ItemRuleValidator();
        $this->itemCatalogController = new ItemCatalogController(new ItemCatalogService($itemCatalogRepository, new ItemCatalogValidator()));
        $this->bedTypeItemRuleController = new BedTypeItemRuleController(new BedTypeItemRuleService($bedTypeItemRuleRepository, $itemRuleValidator));
        $this->bathroomTypeItemRuleController = new BathroomTypeItemRuleController(new BathroomTypeItemRuleService($bathroomTypeItemRuleRepository, $itemRuleValidator));
        $this->guestItemRuleController = new GuestItemRuleController(new GuestItemRuleService($guestItemRuleRepository, $itemRuleValidator));
        $this->requirementController = new RequirementController(new RequirementCalculationService(
            $requirementSourceRepository,
            $bedTypeItemRuleRepository,
            $bathroomTypeItemRuleRepository,
            $guestItemRuleRepository,
            $requirementsRepository,
            $transactionManager
        ));

        $this->assignmentController = new CleaningAssignmentController(
            new CleaningEventAssignmentService($assignmentRepository, new CleaningAssignmentValidator(), $transactionManager),
            new UserQueryService($userQueryRepository)
        );

        $scheduleQueryService = new CleaningScheduleQueryService($scheduleQueryRepository);
        $this->scheduleQueryController = new ScheduleQueryController(
            $scheduleQueryService,
            new CleanerOperationsService($scheduleQueryService)
        );

        $this->requirementTotalsController = new RequirementTotalsController(new RequirementTotalsQueryService($requirementTotalsRepository));

        $inventoryLedgerService = new InventoryLedgerService($inventoryLedgerRepository, new InventoryTransactionValidator(), $transactionManager);
        $this->inventoryLocationController = new InventoryLocationController(new InventoryLocationService($inventoryLocationRepository, new InventoryLocationValidator()));
        $this->inventoryTransactionController = new InventoryTransactionController($inventoryLedgerService);
        $this->inventoryReservationController = new InventoryReservationController(new InventoryReservationService($inventoryReservationRepository, $inventoryLedgerService, $transactionManager));
        $this->inventoryQueryController = new InventoryQueryController(new InventoryQueryService($inventoryQueryRepository));
        $this->laundryHandoverController = new LaundryHandoverController(
            new LaundryHandoverService($laundryHandoverRepository, $inventoryLedgerService, new LaundryHandoverValidator(), $transactionManager)
        );
        $this->laundryQueryController = new LaundryQueryController(new LaundryQueryService($laundryQueryRepository));
        $this->authService = new AuthService($userAuthRepository);
    }

    public function handle(array $server): ResponseInterface
    {
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $path = $this->normalizePath($server);
        $requestContext = $this->requestContext($method, $path);

        try {
            $this->startSessionIfNeeded($server);
            if ($this->sessionExpired()) {
                $this->logger->info('Security event: session_expired', $requestContext);
                return $this->authErrorResponse(401, 'session_expired', 'Session expired. Please sign in again.');
            }

            $payload = $this->jsonBody();
            $query = $_GET;

            if ($path === '/') {
                return new HtmlResponse('<h1>Milestone 5 backend is running.</h1>');
            }

            $csrfError = $this->validateCsrfIfRequired($method, $server, $payload);
            if ($csrfError !== null) {
                $this->logger->info('Security event: csrf_invalid', $requestContext);
                return $csrfError;
            }

            if ($this->isPublicRoute($method, $path)) {
                return $this->publicRoute($method, $path, $payload);
            }

            $authz = $this->authorizeApiRequest($method, $path);
            if ($authz !== null) {
                return $authz;
            }

            return $this->route($method, $path, $query, $payload);
        } catch (ValidationException $exception) {
            $this->logger->error('Validation exception', $requestContext + ['errors' => $exception->errors()]);
            return new JsonResponse(ApiPayload::error('validation_error', 'Validation failed', $exception->errors()), 422);
        } catch (NotFoundException $exception) {
            $this->logger->error('Not found exception', $requestContext + ['message' => $exception->getMessage()]);
            return new JsonResponse(ApiPayload::error('not_found', $exception->getMessage()), 404);
        } catch (Throwable $throwable) {
            $this->logger->error('Unhandled exception', $requestContext + ['message' => $throwable->getMessage()]);

            $publicMessage = $this->debug
                ? 'Unexpected server error: ' . $throwable->getMessage()
                : 'Unexpected server error';

            return new JsonResponse(ApiPayload::error('server_error', $publicMessage), 500);
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

        if ($method === 'GET' && $path === '/items') {
            return new JsonResponse($this->itemCatalogController->index($query['item_type'] ?? null));
        }
        if ($method === 'POST' && $path === '/items') {
            return new JsonResponse($this->itemCatalogController->create($payload), 201);
        }
        if (preg_match('#^/items/([a-f0-9\-]+)$#', $path, $matches) === 1) {
            if ($method === 'PUT') {
                return new JsonResponse($this->itemCatalogController->update($matches[1], $payload));
            }
            if ($method === 'DELETE') {
                return new JsonResponse($this->itemCatalogController->delete($matches[1]));
            }
        }

        if ($method === 'GET' && $path === '/rules/bed-type-items') {
            return new JsonResponse($this->bedTypeItemRuleController->index($query['trigger_type'] ?? null));
        }
        if ($method === 'POST' && $path === '/rules/bed-type-items') {
            return new JsonResponse($this->bedTypeItemRuleController->create($payload), 201);
        }
        if (preg_match('#^/rules/bed-type-items/([a-f0-9\-]+)$#', $path, $matches) === 1) {
            if ($method === 'PUT') {
                return new JsonResponse($this->bedTypeItemRuleController->update($matches[1], $payload));
            }
            if ($method === 'DELETE') {
                return new JsonResponse($this->bedTypeItemRuleController->delete($matches[1]));
            }
        }

        if ($method === 'GET' && $path === '/rules/bathroom-type-items') {
            return new JsonResponse($this->bathroomTypeItemRuleController->index($query['trigger_type'] ?? null));
        }
        if ($method === 'POST' && $path === '/rules/bathroom-type-items') {
            return new JsonResponse($this->bathroomTypeItemRuleController->create($payload), 201);
        }
        if (preg_match('#^/rules/bathroom-type-items/([a-f0-9\-]+)$#', $path, $matches) === 1) {
            if ($method === 'PUT') {
                return new JsonResponse($this->bathroomTypeItemRuleController->update($matches[1], $payload));
            }
            if ($method === 'DELETE') {
                return new JsonResponse($this->bathroomTypeItemRuleController->delete($matches[1]));
            }
        }

        if ($method === 'GET' && $path === '/rules/guest-items') {
            return new JsonResponse($this->guestItemRuleController->index($query['trigger_type'] ?? null));
        }
        if ($method === 'POST' && $path === '/rules/guest-items') {
            return new JsonResponse($this->guestItemRuleController->create($payload), 201);
        }
        if (preg_match('#^/rules/guest-items/([a-f0-9\-]+)$#', $path, $matches) === 1) {
            if ($method === 'PUT') {
                return new JsonResponse($this->guestItemRuleController->update($matches[1], $payload));
            }
            if ($method === 'DELETE') {
                return new JsonResponse($this->guestItemRuleController->delete($matches[1]));
            }
        }

        if (preg_match('#^/requirements/events/([a-f0-9\-]+)/recalculate$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->requirementController->recalculateEvent($matches[1]));
        }
        if (preg_match('#^/requirements/properties/([a-f0-9\-]+)/recalculate$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->requirementController->recalculatePropertyRange($matches[1], $payload));
        }

        if (preg_match('#^/cleaning-events/([a-f0-9\-]+)/assignments$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->assignmentController->assign($matches[1], $payload));
        }
        if (preg_match('#^/cleaning-events/([a-f0-9\-]+)/assignments/([a-f0-9\-]+)/status$#', $path, $matches) === 1 && $method === 'PATCH') {
            return new JsonResponse($this->assignmentController->updateStatus($matches[1], $matches[2], $payload));
        }
        if ($method === 'GET' && $path === '/users/cleaners') {
            return new JsonResponse($this->assignmentController->activeCleaners());
        }

        if (preg_match('#^/schedule/property/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->scheduleQueryController->byProperty($matches[1], (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }
        if ($method === 'GET' && $path === '/schedule/all') {
            return new JsonResponse($this->scheduleQueryController->all((string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }
        if (preg_match('#^/schedule/cleaner/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->scheduleQueryController->byCleaner($matches[1], (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }
        if ($method === 'GET' && $path === '/schedule/day') {
            return new JsonResponse($this->scheduleQueryController->byDay((string) ($query['date'] ?? '')));
        }
        if (preg_match('#^/cleaners/([a-f0-9\-]+)/operations$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->scheduleQueryController->cleanerOperations($matches[1], (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }

        if ($method === 'GET' && $path === '/requirements/totals/events') {
            return new JsonResponse($this->requirementTotalsController->byEvents((string) ($query['event_ids'] ?? '')));
        }
        if ($method === 'GET' && $path === '/requirements/totals/day') {
            return new JsonResponse($this->requirementTotalsController->byDay($query['property_id'] ?? null, (string) ($query['date'] ?? '')));
        }
        if (preg_match('#^/requirements/totals/property/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->requirementTotalsController->byPropertyRange($matches[1], (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }

        if ($method === 'GET' && $path === '/inventory/locations') {
            return new JsonResponse($this->inventoryLocationController->index($query['location_type'] ?? null));
        }
        if ($method === 'POST' && $path === '/inventory/locations') {
            return new JsonResponse($this->inventoryLocationController->create($payload), 201);
        }
        if (preg_match('#^/inventory/locations/([a-f0-9\-]+)$#', $path, $matches) === 1) {
            if ($method === 'PUT') {
                return new JsonResponse($this->inventoryLocationController->update($matches[1], $payload));
            }
            if ($method === 'DELETE') {
                return new JsonResponse($this->inventoryLocationController->delete($matches[1]));
            }
        }

        if ($method === 'POST' && $path === '/inventory/transactions') {
            return new JsonResponse($this->inventoryTransactionController->post($payload), 201);
        }

        if (preg_match('#^/inventory/reservations/events/([a-f0-9\-]+)/reserve$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->inventoryReservationController->reserve($matches[1], $payload));
        }
        if (preg_match('#^/inventory/reservations/events/([a-f0-9\-]+)/unreserve$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->inventoryReservationController->unreserve($matches[1], $payload));
        }

        if (preg_match('#^/inventory/balances/location/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->inventoryQueryController->balancesByLocation($matches[1]));
        }
        if (preg_match('#^/inventory/balances/item/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->inventoryQueryController->balancesByItem($matches[1]));
        }
        if (preg_match('#^/inventory/movements/location/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->inventoryQueryController->movementsByLocation($matches[1], (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }
        if (preg_match('#^/inventory/movements/item/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->inventoryQueryController->movementsByItem($matches[1], (string) ($query['from_date'] ?? ''), (string) ($query['to_date'] ?? '')));
        }
        if (preg_match('#^/inventory/availability/events/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->inventoryQueryController->eventAvailability($matches[1], (string) ($query['location_id'] ?? '')));
        }

        if ($method === 'POST' && $path === '/laundry/handovers') {
            return new JsonResponse($this->laundryHandoverController->create($payload), 201);
        }
        if (preg_match('#^/laundry/handovers/([a-f0-9\-]+)/returns$#', $path, $matches) === 1 && $method === 'POST') {
            return new JsonResponse($this->laundryHandoverController->processReturn($matches[1], $payload));
        }
        if ($method === 'GET' && $path === '/laundry/handovers/open') {
            return new JsonResponse($this->laundryQueryController->openHandovers());
        }
        if (preg_match('#^/laundry/handovers/property/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->laundryQueryController->handoversByPropertyDateRange(
                $matches[1],
                (string) ($query['from_date'] ?? ''),
                (string) ($query['to_date'] ?? '')
            ));
        }
        if (preg_match('#^/laundry/handovers/([a-f0-9\-]+)/pending-returns$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->laundryQueryController->pendingReturnQuantities($matches[1]));
        }
        if (preg_match('#^/laundry/handovers/([a-f0-9\-]+)$#', $path, $matches) === 1 && $method === 'GET') {
            return new JsonResponse($this->laundryQueryController->handoverDetail($matches[1]));
        }

        return new JsonResponse(ApiPayload::error('not_found', 'Route not found'), 404);
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

    private function startSessionIfNeeded(array $server): void
    {
        SessionSecurity::start($server);
    }

    private function sessionExpired(): bool
    {
        $expired = SessionSecurity::isExpired($this->sessionTimeoutSeconds);
        if ($expired) {
            SessionSecurity::clear();
            return true;
        }

        SessionSecurity::touchActivity();

        return false;
    }

    private function validateCsrfIfRequired(string $method, array $server, array $payload): ?ResponseInterface
    {
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        if (!SessionSecurity::validateCsrfToken($server, $payload)) {
            return new JsonResponse(ApiPayload::error('csrf_invalid', 'CSRF token mismatch or missing'), 419);
        }

        return null;
    }

    private function currentUser(): ?array
    {
        $id = (string) ($_SESSION['user_id'] ?? '');
        if ($id === '') {
            return null;
        }

        $user = $this->authService->currentUser($id);
        if ($user === null) {
            SessionSecurity::clear();
            return null;
        }

        return $user;
    }

    private function isPublicRoute(string $method, string $path): bool
    {
        return ($method === 'POST' && in_array($path, ['/auth/login', '/auth/logout'], true))
            || ($method === 'GET' && $path === '/auth/me');
    }

    private function publicRoute(string $method, string $path, array $payload): ResponseInterface
    {
        if ($method === 'POST' && $path === '/auth/login') {
            $clientKey = LoginThrottle::clientKey($_SERVER);
            if (LoginThrottle::isLocked($clientKey)) {
                $remaining = LoginThrottle::lockRemainingSeconds($clientKey);
                $this->logger->info('Security event: login_throttled', [
                    'client_key' => $clientKey,
                    'remaining_seconds' => $remaining,
                ]);

                return $this->authErrorResponse(429, 'too_many_attempts', 'Too many login attempts. Try again later.');
            }

            try {
                $user = $this->authService->login((string) ($payload['email'] ?? ''), (string) ($payload['password'] ?? ''));
                SessionSecurity::rotateAfterLogin();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                SessionSecurity::touchActivity();
                LoginThrottle::clear($clientKey);
                $this->logger->info('Security event: login_success', [
                    'user_id' => (string) ($user['id'] ?? ''),
                    'role' => (string) ($user['role'] ?? ''),
                    'client_key' => $clientKey,
                ]);

                return new JsonResponse(ApiPayload::success($user, 'Login successful'));
            } catch (DomainException $exception) {
                LoginThrottle::recordFailure($clientKey, $this->loginMaxAttempts, $this->loginLockoutSeconds);
                $this->logger->info('Security event: login_failed', [
                    'client_key' => $clientKey,
                    'attempt_status' => LoginThrottle::status($clientKey),
                ]);
                return $this->authErrorResponse(401, 'unauthorized', $exception->getMessage());
            }
        }

        if ($method === 'POST' && $path === '/auth/logout') {
            $userId = (string) ($_SESSION['user_id'] ?? '');
            SessionSecurity::clear();
            $this->logger->info('Security event: logout', ['user_id' => $userId]);

            return new JsonResponse(ApiPayload::success(['logged_out' => true], 'Logout successful'));
        }

        if ($method === 'GET' && $path === '/auth/me') {
            $user = $this->currentUser();
            if ($user === null) {
                return $this->authErrorResponse(401, 'unauthorized', 'Authentication required');
            }

            return new JsonResponse(ApiPayload::success($user));
        }

        return new JsonResponse(ApiPayload::error('not_found', 'Route not found'), 404);
    }

    private function authorizeApiRequest(string $method, string $path): ?ResponseInterface
    {
        $user = $this->currentUser();
        if ($user === null) {
            return $this->authErrorResponse(401, 'unauthorized', 'Authentication required');
        }

        $role = (string) ($user['role'] ?? '');
        if ($role === '') {
            return $this->authErrorResponse(403, 'forbidden', 'Role is not allowed');
        }

        if ($role === 'owner') {
            return null;
        }

        if ($role === 'manager') {
            if ($this->isOwnerOnlyRoute($method, $path)) {
                return $this->authErrorResponse(403, 'forbidden', 'Insufficient privileges');
            }

            return null;
        }

        if ($role === 'cleaner') {
            $cleanerId = (string) ($user['id'] ?? '');
            if ($this->canCleanerAccess($method, $path, $cleanerId)) {
                return null;
            }

            return $this->authErrorResponse(403, 'forbidden', 'Cleaner role cannot access this endpoint');
        }

        return $this->authErrorResponse(403, 'forbidden', 'Role is not allowed');
    }

    private function isOwnerOnlyRoute(string $method, string $path): bool
    {
        // Current MVP has no owner-only routes yet; keep explicit hook for future additions.
        // Example future policy: return $method === 'DELETE' && str_starts_with($path, '/users/');
        return false;
    }

    private function canCleanerAccess(string $method, string $path, string $cleanerId): bool
    {
        if ($method === 'GET' && preg_match('#^/schedule/cleaner/([a-f0-9\-]+)$#', $path, $matches) === 1) {
            return $matches[1] === $cleanerId;
        }

        if ($method === 'GET' && preg_match('#^/cleaners/([a-f0-9\-]+)/operations$#', $path, $matches) === 1) {
            return $matches[1] === $cleanerId;
        }

        if ($method === 'PATCH' && preg_match('#^/cleaning-events/([a-f0-9\-]+)/assignments/([a-f0-9\-]+)/status$#', $path, $matches) === 1) {
            return $matches[2] === $cleanerId;
        }

        return false;
    }

    private function authErrorResponse(int $statusCode, string $error, string $message): JsonResponse
    {
        if ($statusCode === 403) {
            $this->logger->info('Security event: forbidden_access', [
                'error' => $error,
                'message' => $message,
            ]);
        }

        return new JsonResponse(ApiPayload::error($error, $message), $statusCode);
    }

    private function normalizePath(array $server): string
    {
        $rawPath = parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $scriptName = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? '/index.php'));
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        $this->basePath = $scriptDir === '/' ? '' : $scriptDir;

        $path = $rawPath;
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
            $path = $path === false ? '/' : $path;
        }

        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
            $path = $path === false ? '/' : $path;
        }

        $path = '/' . ltrim($path, '/');

        return $path === '/index.php' || $path === '//' ? '/' : $path;
    }

    private function requestContext(string $method, string $path): array
    {
        $sessionUserId = '';
        if (isset($_SESSION) && is_array($_SESSION)) {
            $sessionUserId = (string) ($_SESSION['user_id'] ?? '');
        }

        return [
            'method' => $method,
            'path' => $path,
            'base_path' => $this->basePath,
            'user_id' => $sessionUserId,
        ];
    }
}
