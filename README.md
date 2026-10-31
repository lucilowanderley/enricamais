# ENRICA+

Sistema de gestão financeira **pessoal, familiar e empresarial**, com módulo de **investimentos** e **assistente de IA** (Claude, da Anthropic) para orientações de investimento.

Construído em **Laravel 13 (PHP 8.3+)**, Blade, Tailwind CSS 4, Alpine.js e Chart.js. Funciona com SQLite (padrão) ou MySQL/MariaDB — roda direto no **Laragon**.

## Funcionalidades

**Espaços (multiempresa / multifamília)**
- Espaços do tipo *Pessoal*, *Família* e *Empresarial*, cada um com dados totalmente isolados.
- Troca rápida entre espaços na barra lateral.
- Membros com papéis: proprietário, administrador, membro e somente leitura (ideal para contador/consultor).
- Categorias padrão adequadas a cada tipo (ex.: CMV, Pró-labore, DAS no empresarial).

**Finanças**
- Contas: corrente, poupança, dinheiro, cartão de crédito (com limite), investimento. Saldo calculado em tempo real e extrato mensal com saldo acumulado.
- Lançamentos: receitas, despesas e transferências; situação efetivada/pendente (contas a pagar e receber); parcelamento automático; favorecido/cliente; busca e filtros; exportação CSV (abre no Excel).
- Categorias e subcategorias com cores.
- Orçamentos mensais por categoria (orçado x realizado, incluindo subcategorias) e cópia do mês anterior.
- Metas financeiras com progresso e aporte mensal necessário.
- Relatórios: fluxo de caixa (6/12/24 meses), DRE gerencial/demonstrativo por período, gastos por categoria, evolução do saldo consolidado.
- Valores aceitos no formato brasileiro (`1.234,56`).

**Investimentos**
- Cadastro de ativos por classe: renda fixa, Tesouro Direto, ações, FIIs, ETFs, fundos, internacional, cripto, previdência.
- Operações de compra/aporte, venda/resgate e proventos/rendimentos.
- Posição por **custo médio ponderado**, resultado não realizado, lucro realizado, proventos e retorno total.
- Perfil de investidor (conservador, moderado, arrojado), horizonte, aporte mensal, reserva de emergência e **alocação-alvo** por classe.
- Comparação atual x alvo com indicação de **quanto aportar em cada classe** para rebalancear.

**Assistente de investimentos com IA**
- Chat com o Claude que recebe um retrato resumido das finanças do espaço: fluxo de caixa médio, taxa de poupança, reserva de emergência, carteira, alocação, metas e perfil.
- Análises rápidas: diagnóstico completo, rebalanceamento, reserva de emergência, renda fixa, independência financeira e corte de despesas.
- Privacidade: descrições de lançamentos, nomes e documentos **não** são enviados à IA.
- Usa o SDK oficial `anthropic-ai/sdk`, modelo `claude-opus-5-5` por padrão, com raciocínio adaptativo, cache de prompt e *fallback* automático no servidor caso o modelo recuse um pedido.
- As conversas são privadas de quem as criou.

## Instalação (Laragon / local)

```bash
git clone https://github.com/lucilowanderley/enricamais.git
cd enricamais
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

### Banco de dados

- **SQLite (mais simples):** crie o arquivo vazio `database/database.sqlite` e mantenha `DB_CONNECTION=sqlite`.
- **MySQL do Laragon:** crie o banco `enricamais` e ajuste no `.env`:
  ```
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=enricamais
  DB_USERNAME=root
  DB_PASSWORD=
  ```

```bash
php artisan migrate --seed   # --seed cria dados de demonstração
php artisan serve            # ou acesse http://enricamais.test pelo Laragon
```

Usuário de demonstração: **demo@enrica.app** / senha **password** (com um espaço de família e um empresarial já preenchidos).

### Ativar o assistente de IA

1. Crie uma chave em [platform.claude.com](https://platform.claude.com/).
2. No `.env`:
   ```
   ANTHROPIC_API_KEY=sk-ant-...
   ANTHROPIC_MODEL=claude-opus-5-5
   ANTHROPIC_EFFORT=high      # low | medium | high | xhigh | max
   ```
3. `php artisan config:clear`

As respostas podem levar alguns segundos; o tempo limite da requisição é ampliado automaticamente nessa tela.

## Testes

```bash
php artisan test
```

Cobrem cadastro/login, saldos e transferências, parcelamento, isolamento entre espaços, permissões de membros, orçamentos, relatórios, custo médio da carteira, rebalanceamento e o fluxo do assistente de IA (com cliente simulado, sem chamar a API).

## Estrutura

| Pasta | Conteúdo |
|---|---|
| `app/Models` | Modelos; `Concerns/BelongsToWorkspace` isola os dados por espaço |
| `app/Services` | Regras de negócio: relatórios, carteira, lançamentos, espaços |
| `app/Services/Ai` | Integração com o Claude e montagem do contexto financeiro |
| `app/Http/Middleware/SetCurrentWorkspace.php` | Define o espaço ativo e aplica o modo somente leitura |
| `resources/views` | Telas em Blade |

> As orientações do assistente são educacionais, geradas por IA, e não substituem um profissional certificado.
