<?php declare(strict_types=1);


namespace App\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

use Hyperf\DbConnection\Db;

use Carbon\Carbon;

class TransactionController extends AbstractController
{

    protected ValidatorFactoryInterface $validationFactory;


    public function __construct(ValidatorFactoryInterface $validationFactory)
    {
        $this->validationFactory = $validationFactory;
    }


    public function index(RequestInterface $request, ResponseInterface $response)
    {

        $transactionData = $request->all();

        $validator = $this->validationFactory->make( 
            $request->all(), 
            [
                'valor' => 'required|integer',
                'tipo'  => 'required|in:c,d',
                'descricao' => 'required|string|min:1|max:10',
            ]);


        if ($validator->fails())
            return $response->withStatus(422);

        
        $clientId = $request->route('id');


        Db::beginTransaction();

        $client = Db::table('clients')
            ->where('id', $clientId)
            ->lockForUpdate()
            ->first();

        if (empty($client))
        {
            Db::rollBack();
            return $response->withStatus(404);
        }


        $beforeBalance = $transactionData['tipo'] == 'd' ? 
            $client->balance - $transactionData['valor'] : 
            $client->balance + $transactionData['valor'];


        if (abs($beforeBalance) > $client->limit)
        {
            Db::rollBack();
            return $response->withStatus(422);
        }


        Db::table('clients')
            ->where('id', $client->id)
            ->update([
                'balance' => $beforeBalance
            ]);

        Db::table('transactions')
            ->insert([
                'value' => $transactionData['valor'],
                'type' => $transactionData['tipo'],
                'description' => $transactionData['descricao'],
                'client_id' => $clientId
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
            ->limit(10)
            ->get();

        $transactionsClient = [];

        foreach ($transactions as $transaction)
        {
            $transactionsClient[] = [
                'valor' => $transaction->value,
                'tipo' => $transaction->type,
                'descricao' => $transaction->description,
                'realizada_em' => Carbon::parse($transaction->created_at)->toISOString(),
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