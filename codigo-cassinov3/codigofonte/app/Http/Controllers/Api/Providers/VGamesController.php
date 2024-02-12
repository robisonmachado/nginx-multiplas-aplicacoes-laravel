<?php

namespace App\Http\Controllers\Api\Providers;

use App\Helpers\Core as Helper;
use App\Http\Controllers\Controller;
use App\Models\GameExclusive;
use App\Models\Wallet;
use Illuminate\Http\Request;

class VGamesController extends Controller
{
    /**
     * vGame Provider
     * Store a newly created resource in storage.
     */
    public function vgameProvider(Request $request, $token, $action)
    {
        $tokenOpen = Helper::DecToken($token);
        $validEndpoints = ['session', 'icons', 'spin', 'freenum'];

        if (in_array($action, $validEndpoints)) {
            if(isset($tokenOpen['status']) && $tokenOpen['status'])
            {
                $game = GameExclusive::whereActive(1)->where('uuid', $tokenOpen['game'])->first();
                if(!empty($game)) {
                    $controller = Helper::createController($game->uuid);

                    switch ($action) {
                        case 'session':
                            return $controller->session($token);
                        case 'spin':
                            return $controller->spin($request, $token);
                        case 'freenum':
                            return $controller->freenum($request, $token);
                        case 'icons':
                            return $controller->icons();
                    }
                }
            }
        } else {
            return response()->json([], 500);
        }
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('web.vgames.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug,$aposta = null)
    {
        if(auth()->check()) {
            $game = GameExclusive::whereActive(1)->where('uuid', $slug)->first();
            
            if(!empty($game)) {
                $velo = $game->velocidade??110;
                $game->increment('views', 1); // add mais uma visualização

                $token = Helper::MakeToken([
                    'id' => auth()->user()->id,
                    'game' => $slug
                ]);

                if($aposta){
                    $wallet = Wallet::where('user_id', auth()->user()->id)->first();
                    $novoSaldo = $wallet->balance - (float)$aposta;
                    Wallet::where('user_id', auth()->user()->id)->update(['balance' => $novoSaldo]);
                }


                return view('web.vgames.play', [
                    'game'    => $game,
                    'gameUrl' => url('/vgames/'.$slug.'/'),
                    'token'   => $token,
                    'velo'    => $velo,
                    'baseurl' => url('/'),
                    'aposta'  => $aposta??0
                ]);
            }

            return back()->with('error', 'UUID Errado');
        }

        return back()->with('error', 'Você precisa fazer login para jogar');
    }

    public function subprocess(Request $request)
    {
        $valor = $request->input('val');
        $token = $request->input('token');
        $bet   = $request->input('bet');

        $decToken = Helper::DecToken($token);

        if($token && isset($decToken['status']) && $decToken['status'] && $decToken['id']){
            $wallet = Wallet::where('user_id', $decToken['id'])->first();
            $novoSaldo = $wallet->balance + $valor;
            $wallet = Wallet::where('user_id', $decToken['id'])->update(['balance' => $novoSaldo]);
        }

        return response()->json(['msg'=>true,'wallet'=>$wallet??null], 200);
    }
}
