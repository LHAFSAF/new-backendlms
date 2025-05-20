<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuizResultController extends Controller
{
    // ➕ Enregistrer les réponses d'un étudiant à un quiz
    public function store(Request $request)
    {
        // 🧪 Log pour vérifier ce qu'on reçoit
        Log::info('📨 Réception des réponses', $request->all());

        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer' => 'required' // ← pas "string" si tu envoies un ID
        ]);

        $quiz = Quiz::with('questions.answers')->findOrFail($validated['quiz_id']);
        $total = $quiz->questions->count();
        $correct = 0;

        foreach ($quiz->questions as $question) {
            $userAnswer = collect($validated['answers'])->firstWhere('question_id', $question->id);

            if (!$userAnswer) {
                Log::warning("Aucune réponse fournie pour la question ID: {$question->id}");
                continue;
            }

            $answerId = $userAnswer['answer'];
            $correctAnswer = $question->answers->firstWhere('is_correct', true);

            if ($correctAnswer && intval($answerId) === intval($correctAnswer->id)) {
                $correct++;
            }
        }

        $score = round(($correct / max($total, 1)) * 100);
        $passed = $score >= ($quiz->threshold ?? 50);

        $result = QuizResult::create([
            'quiz_id' => $quiz->id,
            'user_id' => Auth::id() ?? 1, // ⚠️ pour test (à remplacer par vraie auth)
            'score' => $score,
            'passed' => $passed
        ]);

        return response()->json([
            'score' => $score,
            'passed' => $passed,
            'result_id' => $result->id
        ]);
    }

    // 🔍 Voir tous les résultats d’un quiz
    public function byQuiz($quizId)
    {
        $results = QuizResult::with('user')
            ->where('quiz_id', $quizId)
            ->latest()
            ->get();

        return response()->json($results);
    }

    public function myResult($quizId)
{
    $result = QuizResult::where('user_id', auth()->id())
        ->where('quiz_id', $quizId)
        ->latest()
        ->first();

    return response()->json($result);
}

}
