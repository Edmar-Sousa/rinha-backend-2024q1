<?php declare(strict_types=1);


namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Contract\RequestInterface;

use Hyperf\DbConnection\Db;

use App\Request\TransactionRequest;
use Carbon\Carbon;

class TransactionController extends AbstractController
{


    public function index(TransactionRequest $request, ResponseInterface $response)
    {

        $transactionData = $request->all();
        $clientId = $request->route('id');

        $client = Db::table('clients')
            ->where('id', $clientId)
            ->first();

        if (empty($client))
            return $response->withStatus(404);


        $beforeBalance = $transactionData['tipo'] == 'd' ? 
            $client->balance - $transactionData['valor'] : 
            $client->balance + $transactionData['valor'];


        if ($transactionData['tipo'] == 'd' && $beforeBalance < -$client->limit)
            return $response->withStatus(422);
        
        else if ($transactionData['tipo'] == 'c' && $beforeBalance > $client->limit)
            return $response->withStatus(422);


        Db::beginTransaction();

        Db::table('transactions')
            ->insert([
                'value' => $transactionData['valor'],
                'type'  => $transactionData['tipo'],
                'description' => $transactionData['descricao'],
                'client_id'   => $clientId,
            ]);

        Db::table('clients')
            ->where('id', $clientId)
            ->update([
                'balance' => $beforeBalance
            ]);

        Db::commit();

        return [
            'limite' => $client->limit,
            'saldo'  => $beforeBalance,
        ];

    }



    public function show(RequestInterface $request, ResponseInterface $response)
    {

        $clientId = $request->route('id');


        $client = Db::table('clients')
            ->where('id', $clientId)
            ->select([
                'limit',
                'balance',
            ])
            ->first();

        if (empty($client))
            return $response->withStatus(404);


        $transactions = Db::table('transactions')
            ->where('client_id', $clientId)
            ->select([ 
                'value',
                'type',
                'description',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $transactionsClient = [];

        foreach ($transactions as $transaction)
        {
            $transactionsClient[] = [
                'valor' => $transaction->value,
                'tipo' => $transaction->type,
                'descricao' => $transaction->description,
                'realizada_em' => $transaction->created_at
            ];
        }
        
        return [
            'saldo' => [
                'total' => $client->balance,
                'data_extrato' => Carbon::now()->toISOString(),
                'limite' => $client->limit,
            ],

            'ultimas_transacoes' => $transactionsClient,
        ];

    }
}