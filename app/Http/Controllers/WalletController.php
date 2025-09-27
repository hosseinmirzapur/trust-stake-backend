<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function balance()
    {
        // todo
    }

    public function deposit()
    {
        // todo
    }

    public function withdraw()
    {
        // todo
    }
}
