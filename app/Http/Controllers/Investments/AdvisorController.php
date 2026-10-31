<?php

namespace App\Http\Controllers\Investments;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\Ai\AdvisorException;
use App\Services\Ai\InvestmentAdvisor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvisorController extends Controller
{
    public function index(Request $request, InvestmentAdvisor $advisor): View
    {
        return view('investments.advisor', [
            'configured' => $advisor->isConfigured(),
            'conversations' => $this->conversations($request),
            'conversation' => null,
            'quickPrompts' => InvestmentAdvisor::QUICK_PROMPTS,
        ]);
    }

    public function show(Request $request, AiConversation $conversation, InvestmentAdvisor $advisor): View
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);

        return view('investments.advisor', [
            'configured' => $advisor->isConfigured(),
            'conversations' => $this->conversations($request),
            'conversation' => $conversation->load('messages'),
            'quickPrompts' => InvestmentAdvisor::QUICK_PROMPTS,
        ]);
    }

    public function store(Request $request, InvestmentAdvisor $advisor): RedirectResponse
    {
        $question = $this->question($request);
        @set_time_limit(300);

        try {
            $conversation = $advisor->startConversation($request->user()->id, $question);
        } catch (AdvisorException $e) {
            return back()->withInput()->withErrors(['question' => $e->getMessage()]);
        }

        return redirect()->route('investments.advisor.show', $conversation);
    }

    public function reply(Request $request, AiConversation $conversation, InvestmentAdvisor $advisor): RedirectResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
        $question = $this->question($request);
        @set_time_limit(300);

        try {
            $advisor->ask($conversation, $question);
        } catch (AdvisorException $e) {
            return back()->withInput()->withErrors(['question' => $e->getMessage()]);
        }

        return redirect()->route('investments.advisor.show', $conversation);
    }

    public function destroy(Request $request, AiConversation $conversation): RedirectResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
        $conversation->delete();

        return redirect()->route('investments.advisor')->with('success', 'Conversa excluída.');
    }

    private function question(Request $request): string
    {
        if ($request->filled('quick') && isset(InvestmentAdvisor::QUICK_PROMPTS[$request->input('quick')])) {
            return InvestmentAdvisor::QUICK_PROMPTS[$request->input('quick')];
        }

        return $request->validate(['question' => ['required', 'string', 'min:3', 'max:4000']])['question'];
    }

    private function conversations(Request $request)
    {
        return AiConversation::query()->where('user_id', $request->user()->id)->latest('updated_at')->limit(30)->get();
    }
}
