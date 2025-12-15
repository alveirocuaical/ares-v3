<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\Tenant\User;
use Modules\Factcolombia1\Models\Tenant\Company;
use Modules\Factcolombia1\Models\TenantService\Company as CompanyService;

class LoginResController extends Controller
{
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        $credentials = request(['email', 'password']);

        $company = Company::active();

        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), 'code' => Response::HTTP_UNPROCESSABLE_ENTITY], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if(!auth()->attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Credenciales invalidas', 'code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();

        $companyService = CompanyService::where('user_id', $user->id)->first();
        $token = $companyService ? $companyService->api_token : null;

        return [
            'success' => true,
            'name' => $user->name,
            'email' => $user->email,
            'establishment_id' => auth()->user()->establishment->id,
            'seriedefault' => $user->series_id ?? null,
            'token' => $user->api_token,
            'restaurant_role_id' => $user->restaurant_role_id,
            'restaurant_role_code' => $user->restaurant_role_id ? $user->restaurant_role->code : null,
            'identification_number' => $company->identification_number,
            //'app_logo' => $company->app_logo ?? '',
            //'app_logo_base64' => '',//base64_encode(file_get_contents(config('app.url').'/storage/uploads/logos/'.$company->app_logo)),
            'company' => [
                'name' => $company->name,
                'address' => auth()->user()->establishment->getAddressFullAttribute(),
                'phone' => auth()->user()->establishment->telephone,
                'email' => auth()->user()->establishment->email,
                'url_logo' => ($company->logo)?asset('storage/uploads/logos/'.$company->logo):'',
                'logo_base64' => $company->logo ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('storage/uploads/logos/' . $company->logo))) : '',
            ],
            //'app_configuration' => $this->getAppConfiguration()?? '',
            'sellerId' => $user->id,
        ];
    }

    public function loginToken($token)
    {
        $user = User::where('api_token', $token)->first();
        if($user) {
            Auth::loginUsingId($user->id);
            if (Auth::guard('web')->check()) {
                return redirect('/dashboard');
            } else {
                return response()->json(['error' => 'Credenciales invalidas', 'code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
            }
        } else {
            return response()->json(['error' => 'Credenciales invalidas', 'code' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }
    }
}
