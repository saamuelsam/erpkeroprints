<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nome' => 'Papelaria',           'descricao' => 'Materiais gerais de papelaria'],
            ['nome' => 'Cadernos',             'descricao' => 'Cadernos e blocos de anotação'],
            ['nome' => 'Canetas e Lapiseiras', 'descricao' => 'Canetas, lapiseiras e marcadores'],
            ['nome' => 'Folhas e Papéis',      'descricao' => 'Resmas A4, sulfite e papéis especiais'],
            ['nome' => 'Grampeadores',         'descricao' => 'Grampeadores e grampos'],
            ['nome' => 'Materiais da Gráfica', 'descricao' => 'Insumos para serviços gráficos'],
            ['nome' => 'Papel Couché',         'descricao' => 'Papel couché brilho e fosco'],
            ['nome' => 'Papel Fotográfico',    'descricao' => 'Papel fotográfico em diversas gramaturas'],
            ['nome' => 'Tintas e Toners',      'descricao' => 'Tintas, cartuchos e toners'],
            ['nome' => 'Banner',               'descricao' => 'Materiais e serviços de banner'],
            ['nome' => 'Lona',                 'descricao' => 'Lonas frontlit, backlit e blackout'],
            ['nome' => 'Adesivos e Vinil',     'descricao' => 'Adesivos, vinil e pelliculagem'],
            ['nome' => 'Encadernação',         'descricao' => 'Materiais para encadernação e espiral'],
            ['nome' => 'Plastificação',        'descricao' => 'Plástico para plastificação a frio e quente'],
            ['nome' => 'Serviços',             'descricao' => 'Serviços gerais sem material específico'],
        ];

        foreach ($categorias as $cat) {
            Categoria::firstOrCreate(['nome' => $cat['nome']], $cat);
        }
    }
}
