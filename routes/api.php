<?php

use App\Http\Controllers\API\CamerasController;
use App\Http\Controllers\API\ChainsController;
use App\Http\Controllers\API\DepartmentsController;
use App\Http\Controllers\API\FranchisesController;
use App\Http\Controllers\API\GoodsController;
use App\Http\Controllers\API\IvideonController;
use App\Http\Controllers\API\Lcms\CoursesController;
use App\Http\Controllers\API\Lcms\LessonsController;
use App\Http\Controllers\API\MobileStaffController;
use App\Http\Controllers\API\PermissionsController;
use App\Http\Controllers\API\RecordsController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\RolesController;
use App\Http\Controllers\API\IvideonWebhookController;
use App\Http\Controllers\API\StaffCommentController;
use App\Http\Controllers\API\TypeFormWebhookController;
use App\Http\Controllers\API\YcTransactionController;
use App\Http\Middleware\TerminalEnabled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PersonsController;
use App\Http\Controllers\API\LoadDBController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\SocialController;
use App\Http\Controllers\API\RecordsWsController;
use App\Http\Controllers\API\YcWebhookController;
use App\Http\Controllers\API\TerminalController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/tokens/create', function (Request $request) {
    $token = \App\Models\User::find(4)->createToken('test');

    return ['token' => $token->plainTextToken];
});

//Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/invite/register', [AuthController::class, 'registerInvite']);
Route::post('/invite', [AuthController::class, 'invite']);

Route::get('/social-auth/login/{provider}', [SocialController::class, 'redirectToProvider']);
Route::get('/social-auth/callback/{provider}', [SocialController::class, 'callbackFromProvider']);
Route::post('/social-auth/attach', [SocialController::class, 'attach'])->middleware(['auth:sanctum']);


Route::group(['middleware' => ['auth:sanctum', TerminalEnabled::class]], function () {
    Route::post('/terminal/send-sms-strizh', [TerminalController::class, 'sendSmsStrizhevsky']);
    Route::get('/yc-categories', [TerminalController::class, 'listCategories']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/block-access/{id}', [AuthController::class, 'blockAccess']);

    //Persons
    Route::get('/persons', [PersonsController::class, 'personAll']);
    Route::get('/persons/edit/{id}', [PersonsController::class, 'show']);
    Route::post('/persons/update/{id}', [PersonsController::class, 'update']);//обновдение персонала
    Route::post('/create-person/{id}', [PersonsController::class, 'CreatePerson']);//сохранение персонала из ивидеон
    Route::post('/create-personal', [PersonsController::class, 'createPersonal']);//создание нового персонала
    Route::get('/list-staff-yc', [PersonsController::class, 'listStaffYc']);//список мастеров салона
    Route::post('/persons/from-service/list', [PersonsController::class, 'chainUsersFromService']);//список сотрудников из интграционной системы
    Route::post('/persons/from-service/store', [PersonsController::class, 'chainUsersFromServiceStore']);//массовое добавление сотрудников из интграционной системы
    Route::get('/persons/student/list', [PersonsController::class, 'studentList']);//список студентов франшизы

    //users
    Route::get('/users', [PersonsController::class, 'userAll']);
    Route::get('/users/edit/{id}', [PersonsController::class, 'showUser']);
//    Route::post('/users/update/{id}', [PersonsController::class, 'updateUser']);
    Route::post('/create-user-terminal', [PersonsController::class, 'createUserFromTerminal']);//добавление клинта с терминала, если неопознан
    Route::delete('/users/{id}', [PersonsController::class, 'destroy']);

    //franchises
    Route::get('/franchises',[FranchisesController::class, 'index']);//список всех франшиз

    //chains

//    Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/chains',[ChainsController::class, 'index']);//список cетей
    Route::post('/chains/check-access',[ChainsController::class, 'checkAccess']);//проверка токена для интеграционной системы
    Route::post('/chains',[ChainsController::class, 'store']);//добавить cеть
    Route::get('/chains/{chain}',[ChainsController::class, 'show']);//показать cеть
    Route::patch('/chains/{chain}',[ChainsController::class, 'update']);//редактировать cеть
    Route::delete('/chains/{chain}',[ChainsController::class, 'destroy']);//удалить cеть
//    });
    //departments
    Route::get('/departments', [DepartmentsController::class, 'index']);
    Route::get('/departments/{department}', [DepartmentsController::class, 'show']);
    Route::post('/departments', [DepartmentsController::class, 'store']);
    Route::patch('/departments/{department}', [DepartmentsController::class, 'update']);
    Route::delete('/departments/{department}', [DepartmentsController::class, 'destroy']);
    Route::get('/department/accounts', [DepartmentsController::class, 'ycAccounts']);
    Route::get('/cameras-by-department/{id}', [CamerasController::class, 'camerasByDepartment']);
    Route::get('/department/timezone', [DepartmentsController::class, 'listTimezone']); //timezones для салонов
    Route::get('/department/cities', [DepartmentsController::class, 'listCities']); //Список городов из Airtable
    Route::get('/department/yc', [DepartmentsController::class, 'departmentsFromYc']); //салоны доступные франшизы
    Route::post('/departments/from-service/list',[DepartmentsController::class, 'chainDepartmentsFromService']);//проверка токена для интеграционной системы, вывод точек
    Route::post('/departments/from-service/store',[DepartmentsController::class, 'chainDepartmentsFromServiceStore']);//массовое добавление точек из интеграционной системы

    //roles
    Route::get('/roles',[RolesController::class, 'index']);
    Route::get('/roles/{roles}', [RolesController::class, 'show']);
    Route::post('/roles', [RolesController::class, 'store']);
    Route::post('/roles/{roles}', [RolesController::class, 'update']);
    Route::delete('/roles/{roles}', [RolesController::class, 'destroy']);
    Route::get('/role-labels', [RolesController::class, 'labelIndex']);
    Route::get('/role-labels/{id}', [RolesController::class, 'labelShow']);
    Route::post('/role-labels', [RolesController::class, 'labelStore']);
    Route::put('/role-labels', [RolesController::class, 'labelUpdate']);
    Route::delete('/role-labels/{id}', [RolesController::class, 'labelDestroy']);

    //permissions
    Route::get('/permissions',[PermissionsController::class, 'index']);
    Route::get('/permissions/{permissions}', [PermissionsController::class, 'show']);
    Route::post('/permissions', [PermissionsController::class, 'store']);
    Route::patch('/permissions/{permissions}', [PermissionsController::class, 'update']);
    Route::delete('/permissions/{permissions}', [PermissionsController::class, 'destroy']);

    //records
    Route::post('/users-one-record', [RecordsController::class, 'personOneRecord']);//клиент с одной записью за все время
    Route::post('/users-one-record/{record_id}', [RecordsController::class, 'personOneRecordUpdate']);
    Route::get('/services-yc', [RecordsController::class, 'servicesYc']);//услуги из yc
    Route::get('/services-select-yc', [RecordsController::class, 'servicesYcSelect']);//ближайшее свободное время онлайн
    Route::get('/exact-services-selec-yd', [RecordsController::class, 'exactServicesYcSelect']);//более точное свободное время
    Route::post('/add-record-yc', [RecordsController::class, 'addRecordYc']);//запись на услугу
    Route::delete('/del-record-yc', [RecordsController::class, 'delRecordYc']);//удалить запись
    Route::get('unpaid-records', [RecordsController::class, 'unPaidRecords']);//неоплаченные записи
    Route::get('refresh-records', [RecordsController::class, 'refreshRecords']);// обновить список неоплаченных записей
    Route::post('import-records', [RecordsController::class, 'importRecords']);//импортировать записи  из yc если нет в bis, кнопка обновить на терминале
    Route::get('/records/quality-control', [RecordsController::class, 'qualityControl']);//контроль качества записей
    Route::get('/records/quality-control/analytics', [RecordsController::class, 'qualityControlAnalytics']);//аналитика контроль качества записей
    Route::get('/records/show-typeform/{id}', [RecordsController::class, 'showTypeFormWithRecord']);//вывод результатов теста TypeForm
    Route::patch('/records/update-typeform_status/{id}', [RecordsController::class, 'updateTypeformStatus']);//обновление записи
    Route::get('/records/{id}', [RecordsController::class, 'show']);//обновление записи

    //загрузка одного id_ivideon с Ivideon, которого нет в базе
    Route::get('/load-id-ivideon/{id}', [IvideonController::class, 'LoadIvideonPerson']);
    //Загрузка всех новых id_ivideon с Ivideon, которых нет в базе
    Route::get('/load-id-ivideon', [IvideonController::class, 'LoadIdIvideon']);

    //Для обратной свзяи от вебсокетов колл-центра (после 1 визита)
    Route::post('/set-in-work-one-record', [RecordsWsController::class, 'setInWork']);
    Route::post('/close-in-work-one-record', [RecordsWsController::class, 'closeInWork']);

    //поиск
    Route::get('/users/search-by-phone', [PersonsController::class, 'showUserByPhone']);
    Route::get('/users/search', [PersonsController::class, 'showUserSearch']);

    //отчеты
    Route::get('/profit-report', [ReportController::class, 'profitReport']);
    Route::get('/reports/analytics/group', [ReportController::class, 'llReport']);//отчет с Yclients по LL (парсер)

    //мобильное приложение мастера
    Route::get('/mobile-all-records-staff', [MobileStaffController::class, 'allRecordsStaff']);//список записей мастера
    Route::get('/mobile-all-records-client/{user_id}', [MobileStaffController::class, 'allRecordsClient']);//список записей клиента
    Route::get('/mobile-record-client/{record_id}', [MobileStaffController::class, 'RecordClient']);//запись клиента с фотографиями
    Route::post('/mobile-record-client/{record_id}', [MobileStaffController::class, 'storeRecordClient']);//сохранить клиента с фотографиями
    Route::get('/mobile-list-records-client/{record_id}', [MobileStaffController::class, 'listRecordClient']);//лента записей клиента, группировка по дате и вывод активной записи
    Route::get('/mobile-next-records-client/{record_id}', [MobileStaffController::class, 'nextRecordClient']);//показать еще записи клиента
    Route::post('/mobile/load-avatar', [MobileStaffController::class, 'loadavatar']);//загрузить аватар мастера
    Route::get('/mobile/analytics', [MobileStaffController::class, 'analytics']);//аналитика мастера

    //оплата с терминала
    Route::post('/yc-transaction/terminal/create', [YcTransactionController::class, 'createTerminalTransaction']);//оплата услуги
    Route::post('/yc-transaction/terminal/bindgood', [YcTransactionController::class, 'bindGoodRecordTransaction']);//оплпта услуг и товара, привязка к одной запси
    Route::post('/yc-transaction/terminal/record-good', [YcTransactionController::class, 'GoodRecordTransaction']);
    Route::post('/yc-transaction/terminal/new-bindgood', [YcTransactionController::class, 'newBindGoodRecordTransaction']); //новый метод продажи товара и услуги

    //Товары Yc
    Route::get('/goods/all-goods-yc', [GoodsController::class, 'ycGoods']);//все товары салона
    Route::post('/goods/transaction/create', [GoodsController::class, 'createGoodTransaction']);//продажа товара без услуги

    //Отзывы
    Route::get('/comment/staff', [StaffCommentController::class, 'staffComments']);

    //Курсы
    Route::get('/lcms/course', [CoursesController::class, 'index']);//все курсы админ
    Route::post('/lcms/course', [CoursesController::class, 'store']);//добавить курс админ
    Route::get('/lcms/course/{course}', [CoursesController::class, 'show']);//показать курс с уроками админ
    Route::patch('/lcms/course/{course}', [CoursesController::class, 'update']);//изменить курс админ
    Route::patch('/lcms/course/published/{course}', [CoursesController::class, 'published']);//опубликовать курс с уроками
    Route::patch('/lcms/course/lessons-order/{course}', [CoursesController::class, 'lessonsOrder']);//изменить порядок уроков
    Route::get('/lcms/course-study', [CoursesController::class, 'study']);//курсы студента
    Route::get('/lcms/course-study/{course}', [CoursesController::class, 'studyShow']);//курсы студента
    Route::delete('/lcms/course/{course}', [CoursesController::class, 'destroy']);//удалить курс

    //Уроки
    Route::post('/lcms/lesson', [LessonsController::class, 'store']);//добавить урок
    Route::get('/lcms/lesson/{lesson}', [LessonsController::class, 'show']);//показать урок
    Route::patch('/lcms/lesson/{lesson}', [LessonsController::class, 'update']);//редактировать урок
    Route::patch('/lcms/lesson/published/{lesson}', [LessonsController::class, 'published']);//опубликовать урок
    Route::post('/lcms/lesson-study/begin/{lesson}', [LessonsController::class, 'studybegin']);//начать прохождение урока
    Route::patch('/lcms/lesson/end/{lesson}', [LessonsController::class, 'studyend']);//урок пройден
    Route::delete('/lcms/lesson/{lesson}', [LessonsController::class, 'destroy']);//удалить урок
    Route::post('/lcms/lesson/{lesson}/test', [LessonsController::class, 'storeTest']);//добавить тест к уроку
    Route::delete('/lcms/file/{file}', [LessonsController::class, 'fileDestroy']);//удалить файл
    Route::post('/lcms/file-download', [LessonsController::class, 'getDownload']);//скачать файл



});


//отчет по всем сотрудникам (начало работы), с группировкой по салонам
Route::get('/create-report-salon', [PersonsController::class, 'createReportSalon']);
Route::get('/create-report/{id}', [PersonsController::class, 'createReport']);
Route::get('/create-reportAll', [PersonsController::class, 'createReportAll']);
Route::get('/load', [LoadDBController::class, 'load']);
Route::get('/load_camera', [LoadDBController::class, 'loadCamera']);


//отчеты
//Route::get('/break-ivideon', [LoadDBController::class, 'BreakIvideon']);

Route::get('/load-ycrecords', [LoadDBController::class, 'LoadFromYcRecords']);
Route::get('/load-new-ycclient', [LoadDBController::class, 'LoadFromYcNewClients']);
Route::get('/load-ycrecords', [LoadDBController::class, 'LoadFromYcRecords']);



//записи
Route::get('/records', [RecordsController::class, 'recordsAll']);
Route::get('/user-records', [RecordsController::class, 'userRecord']);



Route::post('/add_faces', [LoadDBController::class, 'addfaces']);
Route::get('/dates-staff', [LoadDBController::class, 'datesStaff']);

//YC Webhook
Route::post('/yc-webhook', [YcWebhookController::class, 'hook']);

//Ivideon Webhook
Route::post('/ivideon-webhook', [IvideonWebhookController::class, 'hook']);

//TypeForm WebHook
Route::post('/typeform-webhook', [TypeFormWebhookController::class, 'hook']);

//Транзакции YC
Route::post('/yc-transaction', [YcTransactionController::class, 'import']);

Route::get('/comment/import', [StaffCommentController::class, 'import']);

//от Андрея
Route::post('/fromYC', [YcTransactionController::class, 'load']);

