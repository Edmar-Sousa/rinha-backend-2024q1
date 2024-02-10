<?php declare(strict_types=1);

namespace App\Controller;

use App\Model\ClientesModel;


class IndexController extends AbstractController
{
    public function index()
    {
        
        $clientes = ClientesModel::get();

        return [
            'clientes' => $clientes,
        ];
    }
}
