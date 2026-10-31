<?php

namespace App\Services;

use App\Enums\WorkspaceType;

class DefaultCategories
{
    /** @return array<string, array<string, array{color: string, children?: list<string>}>> */
    public static function for(WorkspaceType $type): array
    {
        return $type === WorkspaceType::Business ? self::business() : self::household();
    }

    private static function household(): array
    {
        return [
            'income' => [
                'Salário' => ['color' => '#16a34a'],
                'Renda extra' => ['color' => '#22c55e', 'children' => ['Freelance', 'Vendas']],
                'Rendimentos' => ['color' => '#0ea5e9', 'children' => ['Juros', 'Dividendos', 'Aluguéis']],
                'Outras receitas' => ['color' => '#84cc16'],
            ],
            'expense' => [
                'Moradia' => ['color' => '#6366f1', 'children' => ['Aluguel / Financiamento', 'Condomínio', 'Energia', 'Água', 'Internet / Telefone', 'Manutenção']],
                'Alimentação' => ['color' => '#f97316', 'children' => ['Supermercado', 'Restaurantes', 'Delivery']],
                'Transporte' => ['color' => '#0ea5e9', 'children' => ['Combustível', 'Aplicativos / Táxi', 'Transporte público', 'Manutenção do veículo', 'IPVA / Seguro']],
                'Saúde' => ['color' => '#ef4444', 'children' => ['Plano de saúde', 'Farmácia', 'Consultas e exames']],
                'Educação' => ['color' => '#8b5cf6', 'children' => ['Escola / Faculdade', 'Cursos', 'Livros']],
                'Lazer' => ['color' => '#ec4899', 'children' => ['Viagens', 'Assinaturas', 'Passeios']],
                'Compras' => ['color' => '#f59e0b', 'children' => ['Vestuário', 'Eletrônicos', 'Casa']],
                'Filhos' => ['color' => '#14b8a6'],
                'Pets' => ['color' => '#a16207'],
                'Impostos e taxas' => ['color' => '#64748b', 'children' => ['IR', 'IPTU', 'Tarifas bancárias']],
                'Investimentos' => ['color' => '#0d9488'],
                'Outras despesas' => ['color' => '#94a3b8'],
            ],
        ];
    }

    private static function business(): array
    {
        return [
            'income' => [
                'Receita de vendas' => ['color' => '#16a34a', 'children' => ['Produtos', 'Serviços']],
                'Receitas financeiras' => ['color' => '#0ea5e9'],
                'Outras receitas' => ['color' => '#84cc16'],
            ],
            'expense' => [
                'Custos (CMV / CSP)' => ['color' => '#f97316', 'children' => ['Matéria-prima', 'Mercadorias', 'Terceirizados']],
                'Pessoal' => ['color' => '#6366f1', 'children' => ['Salários', 'Encargos', 'Benefícios', 'Pró-labore']],
                'Administrativas' => ['color' => '#8b5cf6', 'children' => ['Aluguel', 'Energia / Água', 'Internet / Telefone', 'Contabilidade', 'Material de escritório']],
                'Comerciais' => ['color' => '#ec4899', 'children' => ['Marketing', 'Comissões', 'Fretes']],
                'Tecnologia' => ['color' => '#0ea5e9', 'children' => ['Software / SaaS', 'Equipamentos']],
                'Impostos' => ['color' => '#64748b', 'children' => ['Simples Nacional / DAS', 'ISS', 'ICMS', 'Outros tributos']],
                'Despesas financeiras' => ['color' => '#ef4444', 'children' => ['Juros', 'Tarifas bancárias', 'Taxas de cartão']],
                'Investimentos' => ['color' => '#0d9488'],
                'Outras despesas' => ['color' => '#94a3b8'],
            ],
        ];
    }
}
