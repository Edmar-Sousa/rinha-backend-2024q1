<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;


use App\Controller\TransactionController;


Router::post('/clientes/{id}/transacoes', [TransactionController::class, 'index']);
Router::get('/clientes/{id}/extrato', [TransactionController::class, 'show']);


