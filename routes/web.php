<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\Investments\AdvisorController;
use App\Http\Controllers\Investments\AssetController;
use App\Http\Controllers\Investments\OperationController;
use App\Http\Controllers\Investments\PortfolioController;
use App\Http\Controllers\Investments\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'workspace'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Espaços (pessoal, família, empresa)
    Route::get('/espacos', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::get('/espacos/novo', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('/espacos', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/espacos/{workspace}/ativar', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::get('/espaco/configuracoes', [WorkspaceController::class, 'settings'])->name('workspaces.settings');
    Route::put('/espaco/configuracoes', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('/espaco', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::post('/espaco/membros', [WorkspaceController::class, 'addMember'])->name('workspaces.members.store');
    Route::put('/espaco/membros/{user}', [WorkspaceController::class, 'updateMember'])->name('workspaces.members.update');
    Route::delete('/espaco/membros/{user}', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.destroy');

    // Finanças
    Route::resource('contas', AccountController::class)->names('accounts')->parameters(['contas' => 'account']);
    Route::resource('categorias', CategoryController::class)->except('show')->names('categories')->parameters(['categorias' => 'category']);

    Route::get('/lancamentos/exportar', [TransactionController::class, 'export'])->name('transactions.export');
    Route::patch('/lancamentos/{transaction}/situacao', [TransactionController::class, 'toggleStatus'])->name('transactions.toggle');
    Route::resource('lancamentos', TransactionController::class)->except('show')->names('transactions')->parameters(['lancamentos' => 'transaction']);

    Route::get('/orcamentos', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/orcamentos', [BudgetController::class, 'store'])->name('budgets.store');
    Route::post('/orcamentos/copiar', [BudgetController::class, 'copyPrevious'])->name('budgets.copy');

    Route::post('/metas/{goal}/aporte', [GoalController::class, 'contribute'])->name('goals.contribute');
    Route::resource('metas', GoalController::class)->except('show')->names('goals')->parameters(['metas' => 'goal']);

    Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');

    // Investimentos
    Route::prefix('investimentos')->name('investments.')->group(function () {
        Route::get('/', PortfolioController::class)->name('index');
        Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/ativos/novo', [AssetController::class, 'create'])->name('assets.create');
        Route::post('/ativos', [AssetController::class, 'store'])->name('assets.store');
        Route::get('/ativos/{asset}', [AssetController::class, 'show'])->name('assets.show');
        Route::get('/ativos/{asset}/editar', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('/ativos/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::patch('/ativos/{asset}/cotacao', [AssetController::class, 'updatePrice'])->name('assets.price');
        Route::delete('/ativos/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
        Route::post('/ativos/{asset}/operacoes', [OperationController::class, 'store'])->name('operations.store');
        Route::delete('/ativos/{asset}/operacoes/{operation}', [OperationController::class, 'destroy'])->name('operations.destroy');

        Route::get('/assistente', [AdvisorController::class, 'index'])->name('advisor');
        Route::post('/assistente', [AdvisorController::class, 'store'])->name('advisor.store');
        Route::get('/assistente/{conversation}', [AdvisorController::class, 'show'])->name('advisor.show');
        Route::post('/assistente/{conversation}', [AdvisorController::class, 'reply'])->name('advisor.reply');
        Route::delete('/assistente/{conversation}', [AdvisorController::class, 'destroy'])->name('advisor.destroy');
    });
});
